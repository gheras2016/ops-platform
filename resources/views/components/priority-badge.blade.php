@props(['priority'])
@php
    $color = $priority?->color ?: 'gray';
    $name = $priority?->name ?? 'غير محددة';
@endphp
<span {{ $attributes->merge(['class' => "badge badge-$color"]) }}><i class="fa-solid fa-flag"></i> {{ $name }}</span>
