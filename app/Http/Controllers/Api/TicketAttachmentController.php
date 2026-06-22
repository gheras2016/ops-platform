<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketAttachmentResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Photo / document attachments on a ticket for the mobile field client — the
 * technician documents the fault and the repair. Mirrors the web rules
 * (anyone who can view the ticket may attach/remove).
 */
class TicketAttachmentController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        return TicketAttachmentResource::collection(
            $ticket->attachments()->with('uploader')->latest()->get()
        );
    }

    /** Upload one or more files (multipart `files[]`). */
    public function store(Request $request, Ticket $ticket)
    {
        $this->authorize('comment', $ticket);

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        foreach ($request->file('files', []) as $file) {
            $path = $file->store("tickets/{$ticket->id}", 'public');
            $ticket->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return TicketAttachmentResource::collection(
            $ticket->attachments()->with('uploader')->latest()->get()
        )->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Ticket $ticket, TicketAttachment $attachment)
    {
        $this->authorize('comment', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['deleted' => true]);
    }
}
