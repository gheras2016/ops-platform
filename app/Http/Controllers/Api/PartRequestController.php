<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartRequestResource;
use App\Models\Ticket;
use App\Services\PartRequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Part requests raised against a ticket. This intermediate phase exposes the
 * "non-catalogue spare part" case: the technician asks for a part that is not in
 * the catalogue (name + description + quantity + reason). It reuses the existing
 * PartRequestWorkflowService, so the request flows into the head-approval /
 * warehouse-issue / procurement chain already built for the web.
 */
class PartRequestController extends Controller
{
    public function __construct(protected PartRequestWorkflowService $workflow)
    {
    }

    /** List the part requests raised on this ticket. */
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $requests = $ticket->partRequests()
            ->with(['items.sparePart', 'requester'])
            ->latest()
            ->get();

        return PartRequestResource::collection($requests);
    }

    /**
     * Raise a spare-part request tied to this ticket — either a catalogue part
     * (`spare_part_id`, to be issued from the warehouse) or a non-catalogue one
     * (`name` + `description`). Raising a request while working PAUSES the ticket
     * until the parts are approved and issued (handled by the workflow service).
     */
    public function store(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);

        $data = $request->validate([
            'spare_part_id' => ['nullable', 'integer', 'exists:spare_parts,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($data['spare_part_id']) && empty($data['name'])) {
            throw ValidationException::withMessages([
                'spare_part_id' => 'اختر قطعة من المخزون أو أدخل اسم قطعة غير موجودة.',
            ]);
        }

        $catalogue = ! empty($data['spare_part_id']);

        $partRequest = $this->workflow->create(
            $ticket,
            $request->user(),
            [[
                'spare_part_id' => $catalogue ? $data['spare_part_id'] : null,
                'custom_name' => $catalogue ? null : $data['name'],
                'description' => $data['description'] ?? null,
                'quantity' => $data['quantity'],
            ]],
            $data['reason'] ?? null,
        );

        $partRequest->load(['items.sparePart', 'requester']);

        return (new PartRequestResource($partRequest))->response()->setStatusCode(201);
    }
}
