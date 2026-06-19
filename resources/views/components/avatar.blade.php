@props(['user' => null, 'name' => null, 'size' => ''])
@php
    $display = $user?->name ?? $name ?? '؟';
    $parts = preg_split('/\s+/', trim($display));
    $initials = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1)) ?: '؟';
@endphp
<div {{ $attributes->merge(['class' => trim("avatar $size")]) }} title="{{ $display }}">{{ $initials }}</div>
