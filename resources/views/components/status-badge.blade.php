@props(['status'])
@php
    [$label, $color] = \App\Models\Ticket::STATUSES[$status] ?? [$status, 'gray'];
@endphp
<span {{ $attributes->merge(['class' => "badge badge-$color badge-dot"]) }}>{{ $label }}</span>
