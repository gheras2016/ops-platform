<?php

namespace App\Services;

use App\Models\Department;
use App\Models\PartRequest;
use App\Models\PurchaseApproval;
use App\Models\PurchaseRequest;
use App\Models\SparePart;
use App\Models\SpareCategory;
use App\Models\StockTransaction;
use App\Models\TicketSparePart;
use App\Models\User;
use App\Notifications\PurchaseRequestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Purchase-request lifecycle with a smart fixed approval chain:
 *   department head → up the department tree (parent heads) → finance → execute.
 * Levels are auto-skipped when a department has no head or is headed by the requester.
 * Execution is either stock replenishment or an urgent direct purchase charged to a ticket.
 */
class ProcurementService
{
    /*
    |--------------------------------------------------------------------------
    | Creation
    |--------------------------------------------------------------------------
    */

    /** A department head raises a manual purchase request. $items = [['spare_part_id'|'custom_name','quantity','unit_price'], ...] */
    public function createManual(User $requester, array $data, array $items): PurchaseRequest
    {
        $lines = $this->cleanItems($items);
        $this->guard($lines->isNotEmpty(), 'يجب إضافة صنف واحد على الأقل.');

        return DB::transaction(function () use ($requester, $data, $lines) {
            $pr = PurchaseRequest::create([
                'company_id' => $requester->company_id,
                'request_number' => $this->generateNumber($requester->company_id),
                'requested_by' => $requester->id,
                'department_id' => $data['department_id'] ?? $requester->department_id,
                'ticket_id' => $data['ticket_id'] ?? null,
                'fulfillment_type' => $data['fulfillment_type'] ?? PurchaseRequest::TYPE_STOCK,
                'status' => PurchaseRequest::STATUS_DRAFT,
                'justification' => $data['justification'] ?? null,
                'supplier' => $data['supplier'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $l) {
                $pr->items()->create($l);
            }

            return $pr;
        });
    }

    /** Warehouse converts a part-request shortage/custom lines into a purchase request. */
    public function convert(PartRequest $partRequest, User $warehouse, ?string $notes = null): PurchaseRequest
    {
        $this->guard($partRequest->canBeConverted(), 'لا يمكن تحويل هذا الطلب للشراء في حالته الحالية.');

        $existing = $partRequest->purchaseRequest;
        $this->guard(! ($existing && $existing->status !== PurchaseRequest::STATUS_REJECTED), 'يوجد طلب شراء مرتبط بهذا الطلب بالفعل.');

        $lines = $partRequest->items->filter(fn ($i) => $i->isCustom() || $i->outstanding() > 0);
        $this->guard($lines->isNotEmpty(), 'لا توجد أصناف بحاجة للشراء في هذا الطلب.');

        return DB::transaction(function () use ($partRequest, $warehouse, $notes, $lines) {
            $pr = PurchaseRequest::create([
                'company_id' => $partRequest->company_id,
                'request_number' => $this->generateNumber($partRequest->company_id),
                'requested_by' => $warehouse->id,
                'department_id' => $partRequest->department_id,
                'ticket_id' => $partRequest->ticket_id,
                'part_request_id' => $partRequest->id,
                'fulfillment_type' => PurchaseRequest::TYPE_STOCK,
                'status' => PurchaseRequest::STATUS_DRAFT,
                'notes' => $notes ?: 'تحويل نقص قطع غيار من طلب ' . $partRequest->request_number,
            ]);

            foreach ($lines as $item) {
                $pr->items()->create([
                    'spare_part_id' => $item->spare_part_id,
                    'custom_name' => $item->custom_name,
                    'part_request_item_id' => $item->id,
                    'quantity' => $item->isCustom() ? $item->qty_requested : $item->outstanding(),
                    'unit_price' => $item->sparePart?->unit_price,
                ]);
            }

            $partRequest->update(['status' => PartRequest::STATUS_PROCUREMENT]);

            $this->submit($pr, $warehouse);

            return $pr;
        });
    }

    /**
     * Auto-create a purchase request for the lines of a part request that cannot
     * be met from stock (custom/out-of-catalogue, or approved qty > on-hand).
     * Requested by the approving head, so the chain skips their level (already approved)
     * and goes straight to the higher management → finance. Returns null if nothing to procure.
     */
    public function procureShortfall(PartRequest $partRequest, User $requester): ?PurchaseRequest
    {
        // Don't duplicate an active procurement for this part request.
        $existing = $partRequest->purchaseRequest;
        if ($existing && $existing->status !== PurchaseRequest::STATUS_REJECTED) {
            return null;
        }

        $lines = $partRequest->items->filter(function ($i) {
            if ($i->isCustom()) {
                return (int) $i->qty_approved > 0;
            }

            return (int) $i->qty_approved > (int) ($i->sparePart?->quantity ?? 0);
        });
        if ($lines->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($partRequest, $requester, $lines) {
            $pr = PurchaseRequest::create([
                'company_id' => $partRequest->company_id,
                'request_number' => $this->generateNumber($partRequest->company_id),
                'requested_by' => $requester->id,
                'department_id' => $partRequest->department_id,
                'ticket_id' => $partRequest->ticket_id,
                'part_request_id' => $partRequest->id,
                'fulfillment_type' => PurchaseRequest::TYPE_STOCK,
                'status' => PurchaseRequest::STATUS_DRAFT,
                'notes' => 'توريد تلقائي لأصناف غير متوفرة/خارج القائمة من طلب ' . $partRequest->request_number,
            ]);

            foreach ($lines as $item) {
                $shortfall = $item->isCustom()
                    ? (int) $item->qty_approved
                    : ((int) $item->qty_approved - (int) ($item->sparePart?->quantity ?? 0));

                $pr->items()->create([
                    'spare_part_id' => $item->spare_part_id,
                    'custom_name' => $item->custom_name,
                    'part_request_item_id' => $item->id,
                    'quantity' => max(1, $shortfall),
                    'unit_price' => $item->sparePart?->unit_price,
                ]);
            }

            $this->submit($pr, $requester);

            return $pr;
        });
    }

    /** Move a draft into the approval chain. */
    public function submit(PurchaseRequest $pr, User $actor): PurchaseRequest
    {
        $this->guard($pr->status === PurchaseRequest::STATUS_DRAFT, 'الطلب ليس مسودة.');
        $this->startChain($pr);

        return $pr;
    }

    /*
    |--------------------------------------------------------------------------
    | Approval chain
    |--------------------------------------------------------------------------
    */

    /** Approve the current stage (department level or finance), advancing the chain. */
    public function approve(PurchaseRequest $pr, User $actor, ?string $note = null): PurchaseRequest
    {
        if ($pr->canDeptDecide()) {
            return $this->deptApprove($pr, $actor, $note);
        }
        if ($pr->canFinanceDecide()) {
            return $this->financeApprove($pr, $actor, $note);
        }
        $this->guard(false, 'لا يمكن اعتماد الطلب في حالته الحالية.');
    }

    protected function deptApprove(PurchaseRequest $pr, User $actor, ?string $note): PurchaseRequest
    {
        return DB::transaction(function () use ($pr, $actor, $note) {
            PurchaseApproval::create([
                'purchase_request_id' => $pr->id,
                'department_id' => $pr->current_dept_id,
                'stage' => 'dept',
                'approver_id' => $actor->id,
                'decision' => 'approved',
                'note' => $note,
                'decided_at' => now(),
            ]);

            $next = $this->nextDeptApprover($pr, $pr->current_dept_id);
            if ($next) {
                $pr->update(['status' => PurchaseRequest::STATUS_PENDING_DEPT, 'current_dept_id' => $next->id]);
                $this->notifyHead($pr, $next, 'submitted', "طلب شراء {$pr->request_number} بانتظار اعتمادك");
            } else {
                $pr->update(['status' => PurchaseRequest::STATUS_PENDING_FINANCE, 'current_dept_id' => null]);
                $this->notifyFinance($pr);
            }

            return $pr;
        });
    }

    protected function financeApprove(PurchaseRequest $pr, User $finance, ?string $note): PurchaseRequest
    {
        return DB::transaction(function () use ($pr, $finance, $note) {
            PurchaseApproval::create([
                'purchase_request_id' => $pr->id,
                'stage' => 'finance',
                'approver_id' => $finance->id,
                'decision' => 'approved',
                'note' => $note,
                'decided_at' => now(),
            ]);

            $pr->update([
                'status' => PurchaseRequest::STATUS_APPROVED,
                'approved_by' => $finance->id,
                'approved_at' => now(),
            ]);

            $this->notify($pr, $this->warehouseRecipients($pr->company_id), 'approved',
                "اعتُمد طلب الشراء {$pr->request_number} — جاهز للتنفيذ ({$pr->typeLabel()})");

            return $pr;
        });
    }

    public function reject(PurchaseRequest $pr, User $actor, string $reason): PurchaseRequest
    {
        $this->guard($pr->canDeptDecide() || $pr->canFinanceDecide(), 'لا يمكن رفض الطلب في حالته الحالية.');

        return DB::transaction(function () use ($pr, $actor, $reason) {
            PurchaseApproval::create([
                'purchase_request_id' => $pr->id,
                'department_id' => $pr->current_dept_id,
                'stage' => $pr->canFinanceDecide() ? 'finance' : 'dept',
                'approver_id' => $actor->id,
                'decision' => 'rejected',
                'note' => $reason,
                'decided_at' => now(),
            ]);

            $pr->update(['status' => PurchaseRequest::STATUS_REJECTED, 'rejected_reason' => $reason, 'current_dept_id' => null]);

            if ($partRequest = $pr->partRequest) {
                $hasIssued = $partRequest->items->contains(fn ($i) => $i->qty_issued > 0);
                $partRequest->update(['status' => $hasIssued ? PartRequest::STATUS_PARTIAL : PartRequest::STATUS_APPROVED]);
            }

            $this->notify($pr, [$pr->requested_by], 'rejected', "رُفض طلب الشراء {$pr->request_number}: {$reason}");

            return $pr;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Execution (stock receive OR direct purchase)
    |--------------------------------------------------------------------------
    */
    public function receive(PurchaseRequest $pr, User $warehouse): PurchaseRequest
    {
        $this->guard($pr->canBeReceived(), 'لا يمكن تنفيذ الطلب في حالته الحالية.');

        return $pr->isDirect()
            ? $this->fulfilDirect($pr, $warehouse)
            : $this->receiveToStock($pr, $warehouse);
    }

    protected function receiveToStock(PurchaseRequest $pr, User $warehouse): PurchaseRequest
    {
        return DB::transaction(function () use ($pr, $warehouse) {
            foreach ($pr->items as $line) {
                $part = $line->spare_part_id
                    ? SparePart::withoutGlobalScopes()->find($line->spare_part_id)
                    : $this->createCatalogPart($pr, $line);
                if (! $part) {
                    continue;
                }

                $part->increment('quantity', (int) $line->quantity);
                StockTransaction::create([
                    'company_id' => $pr->company_id, 'spare_part_id' => $part->id, 'type' => 'in',
                    'quantity' => (int) $line->quantity, 'related_ticket_id' => $pr->ticket_id, 'created_by' => $warehouse->id,
                ]);

                $this->linkBackToPartRequest($line, $part);
                if (! $line->spare_part_id) {
                    $line->update(['spare_part_id' => $part->id]);
                }
            }

            $this->closeReceive($pr, $warehouse, PartRequest::STATUS_APPROVED,
                "تم استلام قطع الطلب {$pr->request_number} وإدخالها للمخزون — يمكن صرفها الآن");

            return $pr;
        });
    }

    /** Urgent direct purchase: charged straight to the ticket, never enters the warehouse. */
    protected function fulfilDirect(PurchaseRequest $pr, User $warehouse): PurchaseRequest
    {
        return DB::transaction(function () use ($pr, $warehouse) {
            foreach ($pr->items as $line) {
                if ($pr->ticket_id) {
                    $part = $line->spare_part_id
                        ? SparePart::withoutGlobalScopes()->find($line->spare_part_id)
                        : $this->createCatalogPart($pr, $line); // referenced, but stock NOT incremented

                    if ($part) {
                        $tsp = TicketSparePart::firstOrNew(['ticket_id' => $pr->ticket_id, 'spare_part_id' => $part->id]);
                        $tsp->quantity_used = (int) $tsp->quantity_used + (int) $line->quantity;
                        $tsp->unit_cost = $line->unit_price ?? $part->unit_price;
                        $tsp->created_by = $warehouse->id;
                        $tsp->save();

                        $this->linkBackToPartRequest($line, $part, issued: true);
                        if (! $line->spare_part_id) {
                            $line->update(['spare_part_id' => $part->id]);
                        }
                    }
                }
            }

            // Direct purchase fulfils the part request outright.
            $this->closeReceive($pr, $warehouse, PartRequest::STATUS_ISSUED,
                "تم تنفيذ الشراء المباشر للطلب {$pr->request_number} وتحميله على التذكرة");

            return $pr;
        });
    }

    protected function closeReceive(PurchaseRequest $pr, User $warehouse, string $partRequestStatus, string $message): void
    {
        $pr->update(['status' => PurchaseRequest::STATUS_RECEIVED, 'received_at' => now()]);

        if ($partRequest = $pr->partRequest) {
            $partRequest->update(['status' => $partRequestStatus]);
            $this->notify($pr, [$partRequest->requested_by, ...$this->warehouseRecipients($pr->company_id)], 'received', $message);
        } else {
            $this->notify($pr, [$pr->requested_by], 'received', $message);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /** Next department head up the tree who must approve (skips empty/own-headed levels). */
    protected function nextDeptApprover(PurchaseRequest $pr, ?int $afterDeptId): ?Department
    {
        $deptId = is_null($afterDeptId)
            ? $pr->department_id
            : optional(Department::withoutGlobalScopes()->find($afterDeptId))->parent_id;

        $guard = 0;
        while ($deptId && $guard++ < 20) {
            $dept = Department::withoutGlobalScopes()->find($deptId);
            if (! $dept) {
                break;
            }
            if ($dept->head_id && (int) $dept->head_id !== (int) $pr->requested_by) {
                return $dept;
            }
            $deptId = $dept->parent_id;
        }

        return null;
    }

    protected function startChain(PurchaseRequest $pr): void
    {
        $next = $this->nextDeptApprover($pr, null);
        if ($next) {
            $pr->update(['status' => PurchaseRequest::STATUS_PENDING_DEPT, 'current_dept_id' => $next->id]);
            $this->notifyHead($pr, $next, 'submitted', "طلب شراء جديد {$pr->request_number} بانتظار اعتمادك");
        } else {
            $pr->update(['status' => PurchaseRequest::STATUS_PENDING_FINANCE, 'current_dept_id' => null]);
            $this->notifyFinance($pr);
        }
    }

    protected function linkBackToPartRequest($line, SparePart $part, bool $issued = false): void
    {
        if (! $line->partRequestItem) {
            return;
        }
        $item = $line->partRequestItem;
        $attrs = [];
        if ($item->isCustom()) {
            $attrs['spare_part_id'] = $part->id;
            $attrs['qty_approved'] = max((int) $item->qty_approved, (int) $line->quantity);
        }
        if ($issued) {
            $attrs['qty_issued'] = (int) $item->qty_issued + (int) $line->quantity;
            $attrs['qty_used'] = $attrs['qty_issued'];
            $attrs['unit_cost'] = $line->unit_price;
        }
        if ($attrs) {
            $item->update($attrs);
        }
    }

    protected function createCatalogPart(PurchaseRequest $pr, $line): SparePart
    {
        $category = SpareCategory::withoutGlobalScopes()
            ->where('company_id', $pr->company_id)
            ->where(fn ($q) => $q->where('department_id', $pr->department_id)->orWhereNull('department_id'))
            ->orderByRaw('department_id is null')
            ->first();

        return SparePart::create([
            'company_id' => $pr->company_id,
            'category_id' => $category?->id,
            'name' => $line->custom_name ?: ($line->sparePart?->name ?? 'قطعة مستوردة'),
            'part_number' => 'SP-NEW-' . strtoupper(Str::random(6)),
            'quantity' => 0,
            'min_stock' => 0,
            'unit_price' => $line->unit_price,
        ]);
    }

    protected function cleanItems(array $items)
    {
        return collect($items)
            ->map(fn ($l) => [
                'spare_part_id' => ((int) ($l['spare_part_id'] ?? 0)) ?: null,
                'custom_name' => trim((string) ($l['custom_name'] ?? '')) ?: null,
                'quantity' => (int) ($l['quantity'] ?? 0),
                'unit_price' => ($l['unit_price'] ?? null) !== null && $l['unit_price'] !== '' ? (float) $l['unit_price'] : null,
            ])
            ->filter(fn ($l) => ($l['spare_part_id'] || $l['custom_name']) && $l['quantity'] > 0)
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    protected function notifyHead(PurchaseRequest $pr, Department $dept, string $event, string $message): void
    {
        $this->notify($pr, [$dept->head_id], $event, $message);
    }

    protected function notifyFinance(PurchaseRequest $pr): void
    {
        $this->notify($pr, $this->financeRecipients($pr->company_id), 'finance_pending',
            "طلب شراء {$pr->request_number} بانتظار اعتماد المالية");
    }

    protected function financeRecipients(?int $companyId): array
    {
        return $this->usersWithRoles([User::ROLE_FINANCE_MANAGER, User::ROLE_COMPANY_ADMIN], $companyId);
    }

    protected function warehouseRecipients(?int $companyId): array
    {
        return $this->usersWithRoles([User::ROLE_WAREHOUSE_MANAGER, User::ROLE_COMPANY_ADMIN], $companyId);
    }

    protected function usersWithRoles(array $roles, ?int $companyId): array
    {
        return User::whereIn('id', function ($q) use ($roles) {
            $q->select('model_id')->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', $roles);
        })
            ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', $companyId))
            ->pluck('id')->all();
    }

    protected function notify(PurchaseRequest $pr, array $userIds, string $event, string $message): void
    {
        $ids = collect($userIds)->flatten()->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }
        $users = User::whereIn('id', $ids)->get();
        Notification::send($users, new PurchaseRequestNotification($pr, $event, $message));
    }

    protected function generateNumber(?int $companyId): string
    {
        $prefix = 'PO-' . ($companyId ?: 0) . '-' . now()->format('Ym') . '-';
        $count = PurchaseRequest::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('request_number', 'like', $prefix . '%')
            ->count();

        return $prefix . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function guard(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['procurement' => $message]);
        }
    }
}
