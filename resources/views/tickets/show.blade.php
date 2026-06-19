@extends('layouts.app')
@section('title', $ticket->ticket_number)
@section('page-title', 'تفاصيل التذكرة')
@section('page-sub', $ticket->ticket_number)

@section('content')
@php($user = auth()->user())
@php($canWork = $user->can('work', $ticket))
@php($canAssign = $user->can('assign', $ticket))
@php($canApprove = $user->can('approve', $ticket))

<div class="breadcrumb"><a href="{{ route('tickets.index') }}">التذاكر</a> <i class="fa-solid fa-chevron-left"></i> <span>{{ $ticket->ticket_number }}</span></div>

{{-- Hero --}}
<div class="ticket-hero mb-4">
    <div class="flex items-center justify-between" style="flex-wrap:wrap;gap:12px;">
        <div>
            <div class="num">{{ $ticket->ticket_number }}</div>
            <h2>{{ $ticket->title }}</h2>
            <div class="meta">
                <span><i class="fa-solid fa-sitemap"></i> {{ $ticket->department?->name ?? '—' }}</span>
                <span><i class="fa-solid fa-location-dot"></i> {{ $ticket->location?->full_path ?: $ticket->location?->name ?? '—' }}@if($ticket->location_detail) — {{ $ticket->location_detail }}@endif</span>
                <span><i class="fa-solid fa-user"></i> {{ $ticket->creator?->name ?? '—' }}</span>
                <span><i class="fa-solid fa-calendar"></i> {{ $ticket->created_at->format('Y-m-d') }}</span>
            </div>
        </div>
        <div style="text-align:left">
            <x-status-badge :status="$ticket->status" style="font-size:14px;padding:7px 16px;" />
            <div style="width:200px;margin-top:14px;">
                <div class="flex justify-between text-xs mb-2" style="color:#cbd5e1"><span>نسبة الإنجاز</span><span class="fw-700">{{ $ticket->progress }}%</span></div>
                <div class="progress" style="background:rgba(255,255,255,.15)"><div class="progress-bar {{ $ticket->progress>=100?'green':'' }}" style="width:{{ $ticket->progress }}%"></div></div>
            </div>
        </div>
    </div>
</div>

{{-- Action bar --}}
@php($hasActions = ($canAssign && $ticket->canBeAssigned()) || ($canWork && in_array($ticket->status,['assigned','accepted','in_progress','paused'])) || ($canApprove && $ticket->canBeApproved()))
@if($hasActions)
<div class="card card-body mb-4">
    <div class="flex items-center justify-between mb-2" style="flex-wrap:wrap;gap:12px;">
        <div class="fw-700"><i class="fa-solid fa-bolt soft-primary" style="padding:6px;border-radius:8px"></i> الإجراءات المتاحة</div>
        <div class="action-bar">
            {{-- Head: assign --}}
            @if($canAssign && $ticket->canBeAssigned())
                <button class="btn btn-primary" onclick="openModal('assignModal')"><i class="fa-solid fa-user-plus"></i> {{ $ticket->assigned_to ? 'إعادة الإسناد' : 'إسناد لفني' }}</button>
            @endif

            {{-- Technician --}}
            @if($canWork)
                @if($ticket->canBeAccepted())
                    <form action="{{ route('tickets.accept', $ticket) }}" method="POST">@csrf<button class="btn btn-primary"><i class="fa-solid fa-handshake"></i> قبول التذكرة</button></form>
                @endif
                @if($ticket->status === \App\Models\Ticket::STATUS_ACCEPTED)
                    <form action="{{ route('tickets.start', $ticket) }}" method="POST">@csrf<button class="btn btn-success"><i class="fa-solid fa-play"></i> بدء العمل</button></form>
                @endif
                @if($ticket->status === \App\Models\Ticket::STATUS_PAUSED)
                    <form action="{{ route('tickets.resume', $ticket) }}" method="POST">@csrf<button class="btn btn-success"><i class="fa-solid fa-play"></i> استئناف العمل</button></form>
                @endif
                @if($ticket->canBePaused())
                    <button class="btn btn-warning" onclick="openModal('pauseModal')"><i class="fa-solid fa-pause"></i> إيقاف مؤقت</button>
                @endif
                @if($ticket->canBeResolved())
                    <button class="btn btn-success" onclick="openModal('resolveModal')"><i class="fa-solid fa-flag-checkered"></i> إنهاء وتسليم</button>
                @endif
            @endif

            {{-- Head: approve / reject --}}
            @if($canApprove && $ticket->canBeApproved())
                <button class="btn btn-success" onclick="openModal('approveModal')"><i class="fa-solid fa-circle-check"></i> اعتماد الإنجاز</button>
                <button class="btn btn-danger" onclick="openModal('rejectModal')"><i class="fa-solid fa-rotate-left"></i> رفض وإعادة</button>
            @endif
        </div>
    </div>

    {{-- Inline progress slider for the working technician --}}
    @if($canWork && $ticket->status === \App\Models\Ticket::STATUS_IN_PROGRESS)
        <hr class="divider">
        <form action="{{ route('tickets.progress', $ticket) }}" method="POST" class="flex items-center gap-3" style="flex-wrap:wrap">
            @csrf
            <label class="form-label" style="margin:0">تحديث نسبة الإنجاز:</label>
            <input type="range" name="progress" min="0" max="100" step="5" value="{{ $ticket->progress }}" oninput="document.getElementById('pv').textContent=this.value+'%'" style="flex:1;min-width:200px;accent-color:var(--primary)">
            <span id="pv" class="fw-700" style="min-width:50px">{{ $ticket->progress }}%</span>
            <button class="btn btn-outline btn-sm"><i class="fa-solid fa-save"></i> حفظ</button>
        </form>
    @endif
</div>
@endif

<div class="detail-grid">
    {{-- Left: description, timeline, comments --}}
    <div>
        <div class="card card-body mb-4">
            <h3 class="fw-700 mb-3"><i class="fa-solid fa-align-right text-muted"></i> وصف المشكلة</h3>
            <p style="line-height:1.9; color:var(--text)">{{ $ticket->description ?: 'لا يوجد وصف.' }}</p>

            @if($ticket->resolution_note)
                <div class="alert alert-success mt-4"><i class="fa-solid fa-clipboard-check"></i><div><strong>ملاحظة الإنجاز:</strong> {{ $ticket->resolution_note }}</div></div>
            @endif
            @if($ticket->rejected_reason && !$ticket->isClosed())
                <div class="alert alert-warning mt-4"><i class="fa-solid fa-rotate-left"></i><div><strong>سبب الإعادة:</strong> {{ $ticket->rejected_reason }}</div></div>
            @endif
        </div>

        {{-- Used spare parts --}}
        @if($ticket->spareParts->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header"><h3><i class="fa-solid fa-gears text-muted"></i> قطع الغيار المستخدمة</h3></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>القطعة</th><th>الرقم</th><th>الكمية</th></tr></thead>
                <tbody>
                    @foreach($ticket->spareParts as $sp)
                        <tr>
                            <td class="cell-title">{{ $sp->displayName() }} @if($sp->isCustom())<span class="badge badge-amber">خارج الكاتالوج</span>@endif</td>
                            <td class="cell-sub">{{ $sp->sparePart?->part_number ?? '—' }}</td>
                            <td class="fw-700">{{ $sp->quantity_used }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
        @endif

        {{-- Attachments --}}
        <div class="card mb-4">
            <div class="card-header"><h3><i class="fa-solid fa-paperclip text-muted"></i> المرفقات</h3><span class="sub">{{ $ticket->attachments->count() }}</span></div>
            <div class="card-body">
                @if($ticket->attachments->isNotEmpty())
                    <div class="flex" style="flex-wrap:wrap;gap:12px">
                        @foreach($ticket->attachments as $att)
                            @php($isImg = \Illuminate\Support\Str::startsWith($att->mime, 'image/'))
                            <div class="card" style="width:140px;padding:10px;text-align:center;position:relative">
                                <a href="{{ Storage::url($att->path) }}" target="_blank">
                                    @if($isImg)
                                        <img src="{{ Storage::url($att->path) }}" style="width:100%;height:90px;object-fit:cover;border-radius:8px">
                                    @else
                                        <div style="height:90px;display:grid;place-items:center;background:var(--surface-2);border-radius:8px"><i class="fa-solid fa-file-lines" style="font-size:30px;color:var(--text-soft)"></i></div>
                                    @endif
                                    <div class="text-xs mt-2" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $att->original_name }}</div>
                                </a>
                                @can('comment', $ticket)
                                    <form action="{{ route('tickets.detach', [$ticket, $att]) }}" method="POST" onsubmit="return confirm('حذف المرفق؟')" style="position:absolute;top:6px;left:6px">@csrf @method('DELETE')<button class="icon-btn" style="width:26px;height:26px;font-size:11px;color:var(--danger)"><i class="fa-solid fa-xmark"></i></button></form>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-sm mb-3">لا توجد مرفقات.</p>
                @endif

                @can('comment', $ticket)
                    <form action="{{ route('tickets.attach', $ticket) }}" method="POST" enctype="multipart/form-data" class="flex gap-2 mt-3" style="flex-wrap:wrap">
                        @csrf
                        <input type="file" name="files[]" class="form-control" style="flex:1;min-width:200px" multiple required accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                        <button class="btn btn-outline"><i class="fa-solid fa-upload"></i> رفع</button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Spare-parts requests --}}
        @include('tickets._part_requests', ['ticket' => $ticket, 'spareParts' => $spareParts])

        {{-- Comments / follow-up --}}
        <div class="card mb-4">
            <div class="card-header"><h3><i class="fa-solid fa-comments text-muted"></i> المتابعة والتعليقات</h3><span class="sub">{{ $ticket->comments->count() }} تعليق</span></div>
            <div class="card-body">
                @forelse($ticket->comments as $c)
                    <div class="comment {{ $c->is_internal ? 'internal' : '' }}">
                        <x-avatar :user="$c->user" size="sm" />
                        <div class="comment-body">
                            <div class="comment-bubble">{{ $c->body }}</div>
                            <div class="comment-meta">
                                <span class="fw-600">{{ $c->user->name }}</span>
                                <span>· {{ $c->created_at->diffForHumans() }}</span>
                                @if($c->is_internal)<span class="badge badge-amber" style="padding:1px 7px">داخلي</span>@endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-sm text-center" style="padding:14px">لا توجد تعليقات بعد. كن أول من يتابع.</p>
                @endforelse

                <form action="{{ route('tickets.comment', $ticket) }}" method="POST" class="mt-3">
                    @csrf
                    <textarea name="body" class="form-control" rows="2" placeholder="اكتب تعليقًا أو استفسارًا للمتابعة..." required></textarea>
                    <div class="flex items-center justify-between mt-2">
                        @if($canWork || $canApprove)
                            <label class="switch"><input type="checkbox" name="is_internal" value="1"><span class="track"></span><span class="text-sm">ملاحظة داخلية</span></label>
                        @else <span></span> @endif
                        <button class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane"></i> إرسال</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: details + timeline --}}
    <div>
        <div class="card card-body mb-4">
            <h3 class="fw-700 mb-3">التفاصيل</h3>
            <div class="kv"><span class="k">الحالة</span><span class="v"><x-status-badge :status="$ticket->status" /></span></div>
            <div class="kv"><span class="k">الأولوية</span><span class="v"><x-priority-badge :priority="$ticket->priority" /></span></div>
            <div class="kv"><span class="k">مقدّم الطلب</span><span class="v">{{ $ticket->creator?->name ?? '—' }}</span></div>
            <div class="kv"><span class="k">الفني المسؤول</span><span class="v">{{ $ticket->technician?->name ?? 'غير مسند' }}</span></div>
            <div class="kv"><span class="k">أسندها</span><span class="v">{{ $ticket->assigner?->name ?? '—' }}</span></div>
            @if($ticket->due_at)<div class="kv"><span class="k">الموعد النهائي</span><span class="v {{ $ticket->isOverdue() ? '' : '' }}">{{ $ticket->due_at->format('Y-m-d') }}</span></div>@endif
            @if($ticket->started_at)<div class="kv"><span class="k">بدء العمل</span><span class="v">{{ $ticket->started_at->format('Y-m-d H:i') }}</span></div>@endif
            @if($ticket->closed_at)<div class="kv"><span class="k">الإغلاق</span><span class="v">{{ $ticket->closed_at->format('Y-m-d H:i') }}</span></div>@endif

            @if($ticket->spareParts->isNotEmpty())
                <div class="kv"><span class="k">تكلفة القطع</span><span class="v">{{ number_format($ticket->partsCost(), 2) }}</span></div>
            @endif

            <a href="{{ route('tickets.report', $ticket) }}" target="_blank" class="btn btn-outline btn-sm btn-block mt-3"><i class="fa-solid fa-file-pdf" style="color:var(--danger)"></i> تقرير البلاغ (PDF)</a>
            @can('update', $ticket)
                <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-light btn-sm btn-block mt-2"><i class="fa-solid fa-pen"></i> تعديل البيانات</a>
            @endcan
        </div>

        {{-- Timeline --}}
        <div class="card card-body">
            <h3 class="fw-700 mb-4"><i class="fa-solid fa-timeline text-muted"></i> سجل التتبّع</h3>
            <div class="timeline">
                @foreach($ticket->events as $e)
                    <div class="timeline-item">
                        <div class="timeline-dot bg-{{ $e->color() }}"><i class="fa-solid {{ $e->icon() }}"></i></div>
                        <div class="timeline-content">
                            <div class="timeline-title">{{ $e->label() }}</div>
                            <div class="timeline-meta">{{ $e->user?->name ?? 'النظام' }} · {{ $e->created_at->diffForHumans() }}</div>
                            @if($e->note)<div class="timeline-note">{{ $e->note }}</div>@endif
                            @if($e->type === 'paused' && ($e->meta['reason_label'] ?? false))
                                <div class="timeline-note"><i class="fa-solid fa-circle-info"></i> السبب: {{ $e->meta['reason_label'] }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@include('tickets._modals', ['ticket' => $ticket, 'technicians' => $technicians, 'spareParts' => $spareParts, 'priorities' => $priorities])
@endsection
