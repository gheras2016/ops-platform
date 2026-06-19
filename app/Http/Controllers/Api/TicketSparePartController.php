<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketSparePartResource;
use App\Models\Ticket;
use App\Models\TicketSparePart;
use App\Services\TicketWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Used spare parts on a ticket. The technician records parts while working;
 * catalogue parts are drawn from stock only when the ticket is closed. Custom
 * (non-catalogue) one-offs are recorded by name with no stock movement.
 */
class TicketSparePartController extends Controller
{
    public function __construct(protected TicketWorkflowService $workflow)
    {
    }

    /** List the parts used on a ticket. */
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        return TicketSparePartResource::collection(
            $ticket->spareParts()->with('creator')->latest()->get()
        );
    }

    /** Record a used part (catalogue by id, or a custom one-off by name). */
    public function store(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);
        $this->ensureEditable($ticket);

        $data = $request->validate([
            'spare_part_id' => ['nullable', 'integer', 'exists:spare_parts,id'],
            'custom_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (empty($data['spare_part_id']) && empty($data['custom_name'])) {
            throw ValidationException::withMessages([
                'spare_part_id' => 'اختر قطعة من المخزون أو أدخل اسم قطعة مخصّصة.',
            ]);
        }

        $line = $this->workflow->addUsedPart($ticket, $request->user(), $data);

        if (! $line) {
            throw ValidationException::withMessages(['spare_part_id' => 'تعذّر إضافة القطعة المحددة.']);
        }

        return (new TicketSparePartResource($line->load('creator')))->response()->setStatusCode(201);
    }

    /** Remove a used part — only while it is still pending (not yet drawn from stock). */
    public function destroy(Request $request, Ticket $ticket, TicketSparePart $sparePart)
    {
        $this->authorize('work', $ticket);
        abort_unless($sparePart->ticket_id === $ticket->id, 404);

        if ($sparePart->isDeducted()) {
            throw ValidationException::withMessages([
                'spare_part' => 'لا يمكن حذف قطعة تم خصمها من المخزون.',
            ]);
        }

        $sparePart->delete();

        return response()->json(['deleted' => true]);
    }

    /** Parts can only be edited while the ticket is open/being worked. */
    protected function ensureEditable(Ticket $ticket): void
    {
        if (in_array($ticket->status, [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن تعديل قطع الغيار بعد إغلاق التذكرة.',
            ]);
        }
    }
}
