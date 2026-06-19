<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketPauseLog;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Http\Request;

/**
 * Handles every ticket lifecycle transition. Each method authorizes via
 * TicketPolicy, delegates the state change to TicketWorkflowService, and
 * redirects back to the ticket with a flash message.
 */
class TicketActionController extends Controller
{
    public function __construct(protected TicketWorkflowService $workflow)
    {
    }

    /** Department head assigns the ticket to a technician. */
    public function assign(Request $request, Ticket $ticket)
    {
        $this->authorize('assign', $ticket);

        $data = $request->validate([
            'technician_id' => ['required', 'exists:users,id'],
            'priority_id' => ['nullable', 'exists:priorities,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'due_at' => ['nullable', 'date'],
        ]);

        $technician = User::findOrFail($data['technician_id']);
        abort_unless($technician->department_id === $ticket->department_id, 422, 'الفني ليس ضمن هذا القسم.');

        // The department head sets/adjusts priority + due date at assignment.
        $ticket->update(array_filter([
            'priority_id' => $data['priority_id'] ?? null,
            'due_at' => $data['due_at'] ?? null,
        ]));

        $this->workflow->assign($ticket, $technician, $request->user(), $data['note'] ?? null);

        return back()->with('success', 'تم إسناد التذكرة إلى ' . $technician->name);
    }

    public function accept(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);
        $this->workflow->accept($ticket, $request->user());

        return back()->with('success', 'تم قبول التذكرة، يمكنك بدء العمل الآن.');
    }

    public function start(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);
        $this->workflow->start($ticket, $request->user());

        return back()->with('success', 'تم بدء العمل على التذكرة.');
    }

    public function pause(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);

        $data = $request->validate([
            'reason_code' => ['required', 'in:' . implode(',', array_keys(TicketPauseLog::REASONS))],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->pause($ticket, $request->user(), $data['reason_code'], $data['reason'] ?? null);

        return back()->with('success', 'تم إيقاف التذكرة مؤقتًا مع تسجيل السبب.');
    }

    public function resume(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);
        $this->workflow->resume($ticket, $request->user());

        return back()->with('success', 'تم استئناف العمل على التذكرة.');
    }

    public function progress(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);

        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $this->workflow->setProgress($ticket, $request->user(), $data['progress']);

        return back()->with('success', 'تم تحديث نسبة الإنجاز.');
    }

    public function resolve(Request $request, Ticket $ticket)
    {
        $this->authorize('work', $ticket);

        $data = $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:2000'],
            'parts' => ['nullable', 'array'],
            'parts.*.spare_part_id' => ['nullable', 'exists:spare_parts,id'],
            'parts.*.custom_name' => ['nullable', 'string', 'max:255'],
            'parts.*.quantity' => ['nullable', 'integer', 'min:1'],
            'parts.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->workflow->resolve(
            $ticket,
            $request->user(),
            $data['resolution_note'] ?? null,
            $data['parts'] ?? []
        );

        return back()->with('success', 'تم تحديد التذكرة كمنجزة، بانتظار اعتماد رئيس القسم.');
    }

    public function approve(Request $request, Ticket $ticket)
    {
        $this->authorize('approve', $ticket);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->approve($ticket, $request->user(), $data['note'] ?? null);

        return back()->with('success', 'تم اعتماد إنجاز التذكرة وإغلاقها.');
    }

    public function reject(Request $request, Ticket $ticket)
    {
        $this->authorize('approve', $ticket);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->workflow->reject($ticket, $request->user(), $data['reason']);

        return back()->with('success', 'تمت إعادة التذكرة إلى الفني لإعادة المعالجة.');
    }

    public function cancel(Request $request, Ticket $ticket)
    {
        $this->authorize('cancel', $ticket);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->cancel($ticket, $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'تم إلغاء التذكرة.');
    }

    public function comment(Request $request, Ticket $ticket)
    {
        $this->authorize('comment', $ticket);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $this->workflow->comment($ticket, $request->user(), $data['body'], (bool) ($data['is_internal'] ?? false));

        return back()->with('success', 'تمت إضافة التعليق.');
    }

    /** Upload one or more attachments to a ticket. */
    public function attach(Request $request, Ticket $ticket)
    {
        $this->authorize('comment', $ticket);

        $request->validate([
            'files' => ['required', 'array'],
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

        return back()->with('success', 'تم رفع المرفقات بنجاح.');
    }

    public function detach(Request $request, Ticket $ticket, \App\Models\TicketAttachment $attachment)
    {
        $this->authorize('comment', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'تم حذف المرفق.');
    }
}
