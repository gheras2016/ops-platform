<?php

namespace App\Services;

use App\Models\SparePart;
use App\Models\StockTransaction;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketPauseLog;
use App\Models\TicketSparePart;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for ticket lifecycle transitions.
 *
 * Every method validates the current state, mutates the ticket, and records a
 * timeline event (TicketEvent). Pause/resume also maintain TicketPauseLog rows.
 * Controllers call these; they never flip `status` directly.
 */
class TicketWorkflowService
{
    /** Create a ticket (requester) and log the creation event. */
    public function create(array $data, User $requester): Ticket
    {
        return DB::transaction(function () use ($data, $requester) {
            $ticket = new Ticket($data);
            $ticket->company_id = $data['company_id'] ?? $requester->company_id;
            $ticket->created_by = $requester->id;
            $ticket->status = Ticket::STATUS_OPEN;
            $ticket->progress = 0;
            // Priority is decided by the department head at assignment; default to medium.
            if (empty($ticket->priority_id)) {
                $ticket->priority_id = \App\Models\Priority::where('level', 2)->value('id')
                    ?? \App\Models\Priority::orderBy('level')->value('id');
            }
            $ticket->ticket_number = $this->generateNumber($ticket->company_id);
            $ticket->save();

            $this->log($ticket, $requester, 'created', null, Ticket::STATUS_OPEN);

            return $ticket;
        });
    }

    /** Department head assigns the ticket to a technician. */
    public function assign(Ticket $ticket, User $technician, User $actor, ?string $note = null): Ticket
    {
        $this->guard($ticket->canBeAssigned(), 'لا يمكن إسناد التذكرة في حالتها الحالية.');

        return DB::transaction(function () use ($ticket, $technician, $actor, $note) {
            $from = $ticket->status;
            $reassigned = ! is_null($ticket->assigned_to) && $ticket->assigned_to !== $technician->id;

            $ticket->update([
                'assigned_to' => $technician->id,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'status' => Ticket::STATUS_ASSIGNED,
            ]);

            $this->log($ticket, $actor, 'assigned', $from, Ticket::STATUS_ASSIGNED, $note, [
                'technician_id' => $technician->id,
                'technician_name' => $technician->name,
                'reassigned' => $reassigned,
            ]);

            $this->notify($ticket, [$technician->id], 'assigned',
                "تم إسناد التذكرة {$ticket->ticket_number} إليك: {$ticket->title}", $actor);

            return $ticket;
        });
    }

    /** Technician accepts the assignment. */
    public function accept(Ticket $ticket, User $actor): Ticket
    {
        $this->guard($ticket->canBeAccepted(), 'لا يمكن قبول التذكرة في حالتها الحالية.');

        $from = $ticket->status;
        $ticket->update([
            'status' => Ticket::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
        $this->log($ticket, $actor, 'accepted', $from, Ticket::STATUS_ACCEPTED);
        $this->notify($ticket, [$ticket->created_by], 'accepted',
            "قبل الفني {$actor->name} التذكرة {$ticket->ticket_number}", $actor);

        return $ticket;
    }

    /** Technician starts (or resumes from accepted) work. */
    public function start(Ticket $ticket, User $actor): Ticket
    {
        $this->guard($ticket->canBeStarted(), 'لا يمكن بدء العمل في حالتها الحالية.');

        return DB::transaction(function () use ($ticket, $actor) {
            $from = $ticket->status;

            // If resuming from paused, close the open pause log.
            if ($from === Ticket::STATUS_PAUSED) {
                $this->closeOpenPause($ticket, $actor);
                $this->log($ticket, $actor, 'resumed', $from, Ticket::STATUS_IN_PROGRESS);
                $event = 'resumed';
                $message = "تم استئناف العمل على التذكرة {$ticket->ticket_number}";
            } else {
                $this->log($ticket, $actor, 'started', $from, Ticket::STATUS_IN_PROGRESS);
                $event = 'started';
                $message = "بدأ الفني العمل على التذكرة {$ticket->ticket_number}";
            }

            $ticket->update([
                'status' => Ticket::STATUS_IN_PROGRESS,
                'started_at' => $ticket->started_at ?? now(),
            ]);

            $this->notify($ticket, [$ticket->created_by], $event, $message, $actor);

            return $ticket;
        });
    }

    /** Technician pauses work with a reason (e.g. spare part issue). */
    public function pause(Ticket $ticket, User $actor, string $reasonCode, ?string $reason = null): Ticket
    {
        $this->guard($ticket->canBePaused(), 'لا يمكن إيقاف التذكرة في حالتها الحالية.');

        return DB::transaction(function () use ($ticket, $actor, $reasonCode, $reason) {
            $from = $ticket->status;

            TicketPauseLog::create([
                'ticket_id' => $ticket->id,
                'paused_by' => $actor->id,
                'reason_code' => $reasonCode,
                'reason' => $reason,
                'paused_at' => now(),
            ]);

            $ticket->update(['status' => Ticket::STATUS_PAUSED]);

            $label = TicketPauseLog::REASONS[$reasonCode] ?? $reasonCode;
            $this->log($ticket, $actor, 'paused', $from, Ticket::STATUS_PAUSED, $reason, [
                'reason_code' => $reasonCode,
                'reason_label' => $label,
            ]);

            $this->notify($ticket, [$ticket->created_by, $this->headId($ticket)], 'paused',
                "تم إيقاف التذكرة {$ticket->ticket_number} مؤقتًا ({$label})", $actor);

            return $ticket;
        });
    }

    /** Technician resumes a paused ticket. */
    public function resume(Ticket $ticket, User $actor): Ticket
    {
        $this->guard($ticket->status === Ticket::STATUS_PAUSED, 'التذكرة ليست متوقفة.');

        return $this->start($ticket, $actor);
    }

    /** Technician updates the progress percentage. */
    public function setProgress(Ticket $ticket, User $actor, int $progress): Ticket
    {
        $progress = max(0, min(100, $progress));
        $ticket->update(['progress' => $progress]);
        $this->log($ticket, $actor, 'progress', $ticket->status, $ticket->status, null, [
            'progress' => $progress,
        ]);

        return $ticket;
    }

    /**
     * Technician marks the ticket resolved (awaiting head approval).
     *
     * @param array $parts list of used parts to record (pending). Catalogue lines
     *                     ['spare_part_id'=>int,'quantity'=>int] and custom lines
     *                     ['custom_name'=>string,'quantity'=>int,'unit_cost'=>?float].
     *                     Stock is NOT touched here — catalogue parts are drawn from
     *                     stock only when the ticket is approved/closed (see approve()).
     */
    public function resolve(Ticket $ticket, User $actor, ?string $resolutionNote = null, array $parts = []): Ticket
    {
        $this->guard($ticket->canBeResolved(), 'لا يمكن حل التذكرة في حالتها الحالية.');

        return DB::transaction(function () use ($ticket, $actor, $resolutionNote, $parts) {
            $from = $ticket->status;
            $ticket->update([
                'status' => Ticket::STATUS_RESOLVED,
                'resolved_at' => now(),
                'progress' => 100,
                'resolution_note' => $resolutionNote,
            ]);

            foreach ($parts as $row) {
                $this->addUsedPart($ticket, $actor, $row);
            }

            $this->log($ticket, $actor, 'resolved', $from, Ticket::STATUS_RESOLVED, $resolutionNote);
            $this->notify($ticket, [$this->headId($ticket)], 'resolved',
                "التذكرة {$ticket->ticket_number} بانتظار اعتمادك", $actor);

            return $ticket;
        });
    }

    /**
     * Record a single used part against the ticket WITHOUT moving stock (pending).
     * Catalogue line: ['spare_part_id'=>int,'quantity'=>int]; cost is snapshotted
     * from the part. Custom line: ['custom_name'=>string,'quantity'=>int,'unit_cost'=>?float].
     * Returns the created row, or null when the line was invalid/empty.
     */
    public function addUsedPart(Ticket $ticket, User $actor, array $row): ?TicketSparePart
    {
        $partId = (int) ($row['spare_part_id'] ?? 0);
        $qty = (int) ($row['quantity'] ?? ($row['quantity_used'] ?? 0));
        $customName = trim((string) ($row['custom_name'] ?? '')) ?: null;

        if ($qty <= 0) {
            return null;
        }

        // Out-of-catalogue (custom) used part: record by name, never deducted.
        if ($partId <= 0) {
            if (! $customName) {
                return null;
            }
            $unitCost = ($row['unit_cost'] ?? null) !== null && $row['unit_cost'] !== ''
                ? (float) $row['unit_cost'] : null;

            return TicketSparePart::create([
                'ticket_id' => $ticket->id,
                'spare_part_id' => null,
                'custom_name' => $customName,
                'quantity_used' => $qty,
                'unit_cost' => $unitCost,
                'created_by' => $actor->id,
            ]);
        }

        $part = SparePart::withoutGlobalScopes()->find($partId);
        if (! $part || $part->company_id !== $ticket->company_id) {
            return null;
        }

        return TicketSparePart::create([
            'ticket_id' => $ticket->id,
            'spare_part_id' => $part->id,
            'quantity_used' => $qty,
            'unit_cost' => $part->unit_price,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * Draw every still-pending catalogue used-part from stock and log the movement,
     * then mark the line deducted. Custom lines and rows already deducted (e.g.
     * issued earlier by the warehouse) are skipped. Called at approval/close.
     */
    protected function deductUsedParts(Ticket $ticket, User $actor): void
    {
        $pending = TicketSparePart::where('ticket_id', $ticket->id)
            ->whereNotNull('spare_part_id')
            ->whereNull('deducted_at')
            ->get();

        foreach ($pending as $line) {
            $part = SparePart::withoutGlobalScopes()->lockForUpdate()->find($line->spare_part_id);
            if (! $part) {
                continue;
            }

            $qty = (int) $line->quantity_used;
            $part->decrement('quantity', min($qty, (int) $part->quantity));

            StockTransaction::create([
                'company_id' => $ticket->company_id,
                'spare_part_id' => $part->id,
                'type' => 'out',
                'quantity' => $qty,
                'related_ticket_id' => $ticket->id,
                'created_by' => $actor->id,
            ]);

            $line->update(['deducted_at' => now()]);
        }
    }

    /** Department head approves completion -> closed; used parts are drawn from stock. */
    public function approve(Ticket $ticket, User $actor, ?string $note = null): Ticket
    {
        $this->guard($ticket->canBeApproved(), 'لا يمكن اعتماد التذكرة في حالتها الحالية.');

        return DB::transaction(function () use ($ticket, $actor, $note) {
            $from = $ticket->status;
            $ticket->update([
                'status' => Ticket::STATUS_CLOSED,
                'closed_by' => $actor->id,
                'closed_at' => now(),
            ]);

            // Closing the ticket is when used spare parts actually leave the warehouse.
            $this->deductUsedParts($ticket, $actor);

            $this->log($ticket, $actor, 'approved', $from, Ticket::STATUS_CLOSED, $note);
            $this->notify($ticket, [$ticket->created_by, $ticket->assigned_to], 'approved',
                "تم اعتماد وإغلاق التذكرة {$ticket->ticket_number}", $actor);

            return $ticket;
        });
    }

    /** Department head rejects a resolved ticket -> back to the technician. */
    public function reject(Ticket $ticket, User $actor, string $reason): Ticket
    {
        $this->guard($ticket->canBeApproved(), 'لا يمكن رفض التذكرة في حالتها الحالية.');

        $from = $ticket->status;
        $ticket->update([
            'status' => Ticket::STATUS_IN_PROGRESS,
            'rejected_reason' => $reason,
            'resolved_at' => null,
        ]);
        $this->log($ticket, $actor, 'rejected', $from, Ticket::STATUS_IN_PROGRESS, $reason);
        $this->notify($ticket, [$ticket->assigned_to], 'rejected',
            "أُعيدت التذكرة {$ticket->ticket_number}: {$reason}", $actor);

        return $ticket;
    }

    /** Cancel a ticket (requester/admin) before completion. */
    public function cancel(Ticket $ticket, User $actor, ?string $reason = null): Ticket
    {
        $this->guard(! $ticket->isClosed(), 'التذكرة مغلقة بالفعل.');

        $from = $ticket->status;
        $ticket->update(['status' => Ticket::STATUS_CANCELLED]);
        $this->log($ticket, $actor, 'cancelled', $from, Ticket::STATUS_CANCELLED, $reason);

        return $ticket;
    }

    /** Add a follow-up comment and a timeline marker. */
    public function comment(Ticket $ticket, User $actor, string $body, bool $internal = false): void
    {
        $ticket->comments()->create([
            'user_id' => $actor->id,
            'body' => $body,
            'is_internal' => $internal,
        ]);

        $this->log($ticket, $actor, 'commented', $ticket->status, $ticket->status, mb_substr($body, 0, 140));

        // Notify the other participants (creator / technician / head), but not internal-only notes to the requester.
        $recipients = [$ticket->assigned_to, $this->headId($ticket)];
        if (! $internal) {
            $recipients[] = $ticket->created_by;
        }
        $this->notify($ticket, $recipients, 'commented',
            "تعليق جديد على التذكرة {$ticket->ticket_number} من {$actor->name}", $actor);
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */
    protected function closeOpenPause(Ticket $ticket, User $actor): void
    {
        TicketPauseLog::where('ticket_id', $ticket->id)
            ->whereNull('resumed_at')
            ->update([
                'resumed_by' => $actor->id,
                'resumed_at' => now(),
            ]);
    }

    protected function headId(Ticket $ticket): ?int
    {
        return $ticket->department?->head_id;
    }

    /** Send a TicketNotification to the given user ids, skipping the actor and duplicates. */
    protected function notify(Ticket $ticket, array $userIds, string $event, string $message, ?User $actor): void
    {
        $ids = collect($userIds)
            ->filter()
            ->reject(fn ($id) => $actor && (int) $id === $actor->id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $users = User::whereIn('id', $ids)->get();
        Notification::send($users, new TicketNotification($ticket, $event, $message, $actor));
    }

    protected function log(Ticket $ticket, ?User $actor, string $type, ?string $from, ?string $to, ?string $note = null, ?array $meta = null): TicketEvent
    {
        return TicketEvent::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id,
            'type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'meta' => $meta,
        ]);
    }

    protected function generateNumber(?int $companyId): string
    {
        // Per-company sequence; company id keeps numbers globally unique.
        $prefix = 'TKT-' . ($companyId ?: 0) . '-' . now()->format('Ym') . '-';
        $count = Ticket::withTrashed()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('ticket_number', 'like', $prefix . '%')
            ->count();

        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    protected function guard(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
