<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظام إدارة العمليات') — OPS Platform</title>

    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=3">
    {{-- Per-company visual identity (branding) overrides --}}
    <style id="company-theme">{!! \App\Support\Theme::cssFor(auth()->user()?->company) !!}</style>
    @stack('head')
</head>
<body>
<div class="app">
    @include('partials.sidebar')

    <div class="app-main">
        <header class="app-header">
            <button class="icon-btn menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')" aria-label="القائمة">
                <i class="fa-solid fa-bars"></i>
            </button>
            <button class="icon-btn collapse-toggle" onclick="toggleSidebar()" aria-label="طي القائمة" title="طي / فرد القائمة">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
            <div>
                <h1>@yield('page-title', 'لوحة التحكم')</h1>
                <div class="page-sub">@yield('page-sub', 'مرحبًا بك في منصة إدارة عمليات الصيانة')</div>
            </div>

            <form action="{{ route('tickets.index') }}" method="GET" class="header-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="ابحث عن تذكرة بالرقم أو العنوان..." value="{{ request('search') }}">
            </form>

            <div class="header-actions">
                <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> تذكرة جديدة
                </a>

                @php($unread = auth()->user()->unreadNotifications()->take(8)->get())
                @php($unreadCount = auth()->user()->unreadNotifications()->count())
                <div class="dropdown" style="position:relative">
                    <button class="icon-btn" onclick="this.parentElement.classList.toggle('open')" title="الإشعارات">
                        <i class="fa-solid fa-bell"></i>
                        @if($unreadCount)<span class="dot"></span>@endif
                    </button>
                    <div class="dropdown-menu notif-menu">
                        <div class="dropdown-head">
                            <span class="fw-700">الإشعارات</span>
                            @if($unreadCount)
                                <form action="{{ route('notifications.read-all') }}" method="POST">@csrf<button class="text-xs" style="color:var(--primary)">تعليم الكل كمقروء</button></form>
                            @endif
                        </div>
                        <div class="notif-list">
                            @forelse($unread as $n)
                                <a href="{{ route('notifications.read', $n->id) }}" class="notif-item">
                                    <div class="stat-icon soft-{{ $n->data['color'] ?? 'gray' }}" style="width:36px;height:36px;margin:0;font-size:13px;border-radius:10px"><i class="fa-solid {{ $n->data['icon'] ?? 'fa-bell' }}"></i></div>
                                    <div style="flex:1;min-width:0">
                                        <div class="text-sm fw-600" style="white-space:normal">{{ $n->data['message'] ?? '' }}</div>
                                        <div class="text-xs text-muted">{{ $n->created_at->diffForHumans() }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="empty" style="padding:28px 16px"><i class="fa-regular fa-bell" style="font-size:26px"></i><div class="text-sm">لا توجد إشعارات جديدة</div></div>
                            @endforelse
                        </div>
                        <a href="{{ route('notifications.index') }}" class="dropdown-foot">عرض كل الإشعارات</a>
                    </div>
                </div>

                <div class="user-chip">
                    <div class="avatar sm">{{ auth()->user()->initials() }}</div>
                    <div>
                        <div class="name">{{ auth()->user()->name }}</div>
                        <div class="role">{{ \App\Support\Roles::label(auth()->user()->roles->first()?->name) }}</div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="icon-btn" title="تسجيل الخروج"><i class="fa-solid fa-right-from-bracket"></i></button>
                </form>
            </div>
        </header>

        <main class="app-content">
            @include('partials.flash')
            @include('partials.subscription-banner')
            @yield('content')
        </main>
    </div>
</div>

@stack('modals')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    // CSRF for fetch
    window.csrf = document.querySelector('meta[name=csrf-token]').content;

    // Modal helpers
    function openModal(id){ document.getElementById(id)?.classList.add('open'); }
    function closeModal(id){ document.getElementById(id)?.classList.remove('open'); }
    document.addEventListener('click', e => {
        if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
        // close any open dropdown when clicking outside it
        document.querySelectorAll('.dropdown.open').forEach(d => { if (!d.contains(e.target)) d.classList.remove('open'); });
    });

    // Auto-dismiss toasts
    setTimeout(() => document.querySelectorAll('.toast').forEach(t => t.style.display = 'none'), 4500);

    // Collapsible sidebar (persisted). When collapsed, links show a native tooltip.
    function applySidebar(collapsed){
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        document.querySelectorAll('.sidebar-link').forEach(a => {
            const t = a.querySelector('.sidebar-link-text');
            a.title = collapsed && t ? t.textContent.trim() : '';
        });
        try { localStorage.setItem('opsSidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
    }
    function toggleSidebar(){ applySidebar(!document.body.classList.contains('sidebar-collapsed')); }
    try { if (localStorage.getItem('opsSidebarCollapsed') === '1' && window.innerWidth > 920) applySidebar(true); } catch (e) {}
</script>
@include('partials.password-toggle')
@stack('scripts')
</body>
</html>
