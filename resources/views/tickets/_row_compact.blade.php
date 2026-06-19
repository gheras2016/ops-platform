<a href="{{ route('tickets.show', $t) }}" class="flex items-center gap-3" style="padding:12px 4px; border-bottom:1px solid var(--border);">
    <div class="stat-icon soft-{{ $t->statusColor() }}" style="width:40px;height:40px;margin:0;font-size:15px;border-radius:11px;">
        <i class="fa-solid fa-ticket"></i>
    </div>
    <div style="flex:1; min-width:0;">
        <div class="cell-title" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $t->title }}</div>
        <div class="cell-sub">{{ $t->ticket_number }} · {{ $t->department?->name }} · {{ $t->created_at->diffForHumans() }}</div>
    </div>
    <div style="text-align:left; flex-shrink:0;">
        <x-status-badge :status="$t->status" />
        <div class="text-xs text-muted mt-1">{{ $t->progress }}%</div>
    </div>
</a>
