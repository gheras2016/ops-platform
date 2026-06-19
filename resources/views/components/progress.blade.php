@props(['value' => 0, 'showLabel' => true])
@php
    $value = max(0, min(100, (int) $value));
    $cls = $value >= 100 ? 'green' : ($value > 0 ? 'amber' : '');
@endphp
<div {{ $attributes }}>
    @if($showLabel)
        <div class="flex justify-between text-xs text-muted mb-2"><span>نسبة الإنجاز</span><span class="fw-700">{{ $value }}%</span></div>
    @endif
    <div class="progress"><div class="progress-bar {{ $cls }}" style="width: {{ $value }}%"></div></div>
</div>
