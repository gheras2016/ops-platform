@props(['icon' => 'fa-inbox', 'title' => 'لا توجد بيانات', 'sub' => null])
<div class="empty">
    <i class="fa-solid {{ $icon }}"></i>
    <h4>{{ $title }}</h4>
    @if($sub)<p>{{ $sub }}</p>@endif
    {{ $slot }}
</div>
