<?php

namespace App\Services;

use App\Models\PartRequest;
use App\Models\SparePart;
use App\Models\StockTransaction;
use App\Models\Ticket;
use App\Models\TicketSparePart;
use App\Models\User;
use App\Notifications\TicketNotification;
use App\Services\ProcurementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Spare-parts request lifecycle tied to a ticket:
 *   technician requests → head approves (reserves stock) → warehouse issues (consumes stock).
 *
 * Stock model: spare_parts.quantity = on-hand. Reservation is derived (not stored):
 * Σ(qty_approved − qty_issued) over active requests (see SparePart::reservedQty()).
 * Issuing deducts on-hand, writes a StockTransaction, and mirrors the consumption
 * into ticket_spare_parts so it shows on the ticket + future service PDF.
 */
class PartRequestWorkflowService
{
    /** Technician raises a request against a ticket. $lines = [['spare_part_id'=>, 'quantity'=>], ...] */
    public function create(Ticket $ticket, User $technician, array $lines, ?string $note = null): PartRequest
    {
        $clean = collect($lines)
            ->map(fn ($l) => [
                'spare_part_id' => ((int) ($l['spare_part_id'] ?? 0)) ?: null,
                'custom_name' => trim((string) ($l['custom_name'] ?? '')) ?: null,
                'description' => trim((string) ($l['description'] ?? '')) ?: null,
                'quantity' => (int) ($l['quantity'] ?? 0),
            ])
            ->filter(fn ($l) => ($l['spare_part_id'] || $l['custom_name']) && $l['quantity'] > 0)
            ->values();

        $this->guard($clean->isNotEmpty(), 'يجب إضافة قطعة غيار واحدة على الأقل.');

        return DB::transaction(function () use ($ticket, $technician, $clean, $note) {
            $request = PartRequest::create([
                'company_id' => $ticket->company_id,
                'request_number' => $this->generateNumber($ticket->company_id),
                'ticket_id' => $ticket->id,
                'department_id' => $ticket->department_id,
                'requested_by' => $technician->id,
                'status' => PartRequest::STATUS_PENDING,
                'note' => $note,
            ]);

            foreach ($clean as $line) {
                $request->items()->create([
                    'spare_part_id' => $line['spare_part_id'],
                    'custom_name' => $line['custom_name'],
                    'description' => $line['description'],
                    'qty_requested' => $line['quantity'],
                ]);
            }

            // Reuse the ticket pause workflow: a request means the tech is waiting on parts.
            if ($ticket->status === Ticket::STATUS_IN_PROGRESS) {
                app(TicketWorkflowService::class)->pause(
                    $ticket, $technician, 'spare_part',
                    'تم إنشاء طلب صرف إسبير رقم ' . $request->request_number
                );
            }

            $this->notify($ticket, [$this->headId($ticket)], 'part_requested',
                "طلب صرف إسبير ({$request->request_number}) بانتظار اعتمادك على التذكرة {$ticket->ticket_number}", $technician);

            return $request;
        });
    }

    /** Department head approves; $approved = [item_id => qty]. Defaults to requested qty. Reserves stock. */
    public function approve(PartRequest $request, User $head, array $approved = []): PartRequest
    {
        $this->guard($request->canBeApproved(), 'لا يمكن اعتماد الطلب في حالته الحالية.');

        return DB::transaction(function () use ($request, $head, $approved) {
            foreach ($request->items as $item) {
                $qty = isset($approved[$item->id]) ? max(0, (int) $approved[$item->id]) : (int) $item->qty_requested;
                $item->update(['qty_approved' => $qty]);
            }

            $request->update([
                'status' => PartRequest::STATUS_APPROVED,
                'approved_by' => $head->id,
                'approved_at' => now(),
            ]);

            $ticket = $request->ticket;
            $this->notify($ticket, [$this->warehouseRecipients($request), $request->requested_by], 'part_approved',
                "تم اعتماد طلب الإسبير {$request->request_number} — بانتظار الصرف من المخزون", $head);

            // Auto-route un-stockable lines (custom / shortfall) straight to procurement,
            // requested by the approving head so the purchase chain skips their level.
            app(ProcurementService::class)->procureShortfall($request->fresh('items'), $head);

            return $request->fresh('items');
        });
    }

    public function reject(PartRequest $request, User $head, string $reason): PartRequest
    {
        $this->guard($request->canBeApproved(), 'لا يمكن رفض الطلب في حالته الحالية.');

        $request->update([
            'status' => PartRequest::STATUS_REJECTED,
            'rejected_reason' => $reason,
            'approved_by' => $head->id,
            'approved_at' => now(),
        ]);

        $this->notify($request->ticket, [$request->requested_by], 'part_rejected',
            "تم رفض طلب الإسبير {$request->request_number}: {$reason}", $head);

        return $request;
    }

    /**
     * Warehouse issues parts. $issue = [item_id => qty]. Defaults to outstanding qty.
     * Deducts on-hand stock, logs a StockTransaction, and records consumption on the ticket.
     */
    public function issue(PartRequest $request, User $warehouse, array $issue = []): PartRequest
    {
        $this->guard($request->canBeIssued(), 'لا يمكن صرف الطلب في حالته الحالية.');

        return DB::transaction(function () use ($request, $warehouse, $issue) {
            $ticket = $request->ticket;
            $anyShort = false;

            foreach ($request->items as $item) {
                // Custom (non-catalog) lines cannot be issued from stock — they go to procurement.
                if ($item->isCustom()) {
                    continue;
                }

                $outstanding = $item->outstanding();
                if ($outstanding <= 0) {
                    continue;
                }

                $want = isset($issue[$item->id]) ? max(0, (int) $issue[$item->id]) : $outstanding;
                $want = min($want, $outstanding);

                $part = SparePart::withoutGlobalScopes()->lockForUpdate()->find($item->spare_part_id);
                $give = min($want, (int) $part->quantity);

                if ($give > 0) {
                    $part->decrement('quantity', $give);

                    StockTransaction::create([
                        'company_id' => $request->company_id,
                        'spare_part_id' => $part->id,
                        'type' => 'out',
                        'quantity' => $give,
                        'related_ticket_id' => $ticket->id,
                        'created_by' => $warehouse->id,
                    ]);

                    // Mirror into ticket consumption with a cost snapshot. Stock already
                    // left the warehouse here, so mark the line deducted (close() skips it).
                    // Accumulate only into a prior issued row — never merge with a pending
                    // technician-recorded line awaiting deduction at close.
                    $tsp = TicketSparePart::where('ticket_id', $ticket->id)
                        ->where('spare_part_id', $part->id)
                        ->whereNotNull('deducted_at')
                        ->first()
                        ?? new TicketSparePart(['ticket_id' => $ticket->id, 'spare_part_id' => $part->id]);
                    $tsp->quantity_used = (int) $tsp->quantity_used + $give;
                    $tsp->unit_cost = $part->unit_price;
                    $tsp->created_by = $warehouse->id;
                    $tsp->deducted_at = now();
                    $tsp->save();

                    $item->qty_issued = (int) $item->qty_issued + $give;
                    $item->qty_used = $item->qty_issued; // Phase 2: issued == used (reconciliation in a later phase)
                    $item->unit_cost = $part->unit_price;
                    $item->save();
                }

                if ($item->outstanding() > 0) {
                    $anyShort = true;
                }
            }

            $fullyIssued = $request->items->every(fn ($i) => $i->outstanding() <= 0);
            $request->update([
                'status' => $fullyIssued ? PartRequest::STATUS_ISSUED : PartRequest::STATUS_PARTIAL,
                'issued_by' => $warehouse->id,
                'issued_at' => now(),
            ]);

            $msg = $fullyIssued
                ? "تم صرف قطع الطلب {$request->request_number} — يمكنك استئناف العمل على التذكرة {$ticket->ticket_number}"
                : "تم صرف جزء من الطلب {$request->request_number} (نقص في المخزون) على التذكرة {$ticket->ticket_number}";
            $this->notify($ticket, [$request->requested_by], 'part_issued', $msg, $warehouse);

            return $request->fresh('items');
        });
    }

    public function cancel(PartRequest $request, User $actor): PartRequest
    {
        $this->guard($request->canBeCancelled(), 'لا يمكن إلغاء الطلب في حالته الحالية.');
        $request->update(['status' => PartRequest::STATUS_CANCELLED]);

        return $request;
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */
    protected function headId(Ticket $ticket): ?int
    {
        return $ticket->department?->head_id;
    }

    /** All warehouse managers (+ company admins) in the request's company. */
    protected function warehouseRecipients(PartRequest $request): array
    {
        return User::whereIn('id', function ($q) use ($request) {
            $q->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', [User::ROLE_WAREHOUSE_MANAGER, User::ROLE_COMPANY_ADMIN]);
        })
            ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', $request->company_id))
            ->pluck('id')->all();
    }

    protected function notify(Ticket $ticket, array $userIds, string $event, string $message, ?User $actor): void
    {
        $ids = collect($userIds)->flatten()->filter()
            ->reject(fn ($id) => $actor && (int) $id === $actor->id)
            ->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $users = User::whereIn('id', $ids)->get();
        Notification::send($users, new TicketNotification($ticket, $event, $message, $actor));
    }

    protected function generateNumber(?int $companyId): string
    {
        $prefix = 'PRQ-' . ($companyId ?: 0) . '-' . now()->format('Ym') . '-';
        $count = PartRequest::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('request_number', 'like', $prefix . '%')
            ->count();

        return $prefix . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function guard(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['part_request' => $message]);
        }
    }
}
