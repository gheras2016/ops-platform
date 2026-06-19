<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartRequestResource;
use App\Models\Ticket;
use App\Services\PartRequestWorkflowService;
use Illuminate\Http\Request;

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

    /** Raise a non-catalogue spare-part request tied to this ticket. */
    public function store(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $partRequest = $this->workflow->create(
            $ticket,
            $request->user(),
            [[
                'custom_name' => $data['name'],
                'description' => $data['description'] ?? null,
                'quantity' => $data['quantity'],
            ]],
            $data['reason'] ?? null,
        );

        $partRequest->load(['items.sparePart', 'requester']);

        return (new PartRequestResource($partRequest))->response()->setStatusCode(201);
    }
}
