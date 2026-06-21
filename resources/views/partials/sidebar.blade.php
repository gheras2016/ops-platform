@php($u = auth()->user())
<aside class="sidebar">
    <div class="sidebar-top">
        @if($u->company?->logoUrl())
            <div class="sidebar-logo-icon" style="background:#fff;padding:5px;overflow:hidden">
                <img src="{{ $u->company->logoUrl() }}" alt="logo" style="width:100%;height:100%;object-fit:contain">
            </div>
        @else
            <div class="sidebar-logo-icon">{{ mb_substr($u->company?->name ?? 'OP', 0, 2) }}</div>
        @endif
        <div>
            <div class="sidebar-logo-text">{{ $u->company?->name ?? 'OPS Platform' }}</div>
            <div class="sidebar-logo-sub">منصة إدارة العمليات</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-title">الرئيسية</div>
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-gauge-high sidebar-link-icon"></i>
            <span class="sidebar-link-text">لوحة التحكم</span>
        </a>
        <a href="{{ route('tickets.index') }}" class="sidebar-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
            <i class="fa-solid fa-ticket sidebar-link-icon"></i>
            <span class="sidebar-link-text">
                @if($u->isTechnician()) مهامي
                @elseif($u->isRequester()) بلاغاتي
                @else التذاكر @endif
            </span>
        </a>
        @if($u->isTechnician() || $u->isDepartmentHead() || $u->isAdmin())
            <a href="{{ route('tickets.board') }}" class="sidebar-link {{ request()->routeIs('tickets.board') ? 'active' : '' }}">
                <i class="fa-solid fa-table-columns sidebar-link-icon"></i>
                <span class="sidebar-link-text">لوحة المهام</span>
            </a>
        @endif
        <a href="{{ route('tickets.create') }}" class="sidebar-link {{ request()->routeIs('tickets.create') ? 'active' : '' }}">
            <i class="fa-solid fa-circle-plus sidebar-link-icon"></i>
            <span class="sidebar-link-text">رفع بلاغ جديد</span>
        </a>
        @if($u->isDepartmentHead() || $u->isWarehouseManager() || $u->isAdmin())
            <a href="{{ route('part-requests.index') }}" class="sidebar-link {{ request()->routeIs('part-requests.*') ? 'active' : '' }}">
                <i class="fa-solid fa-dolly sidebar-link-icon"></i>
                <span class="sidebar-link-text">طلبات الإسبير</span>
            </a>
        @endif
        @if($u->isDepartmentHead() || $u->isFinanceManager() || $u->isWarehouseManager() || $u->isAdmin())
            @php($poCount = $u->actionablePurchaseCount())
            <a href="{{ route('purchase-requests.index') }}" class="sidebar-link {{ request()->routeIs('purchase-requests.*') ? 'active' : '' }}">
                <i class="fa-solid fa-truck-fast sidebar-link-icon"></i>
                <span class="sidebar-link-text">طلبات الشراء والتوريد</span>
                @if($poCount > 0)<span class="badge badge-red" style="margin-inline-start:auto">{{ $poCount }}</span>@endif
            </a>
        @endif

        @can('view-reports')
            <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-line sidebar-link-icon"></i>
                <span class="sidebar-link-text">التقارير والتحليلات</span>
            </a>
        @endcan

        @can('admin-access')
            <div class="sidebar-section-title">الإدارة</div>
            <a href="{{ route('departments.index') }}" class="sidebar-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                <i class="fa-solid fa-sitemap sidebar-link-icon"></i>
                <span class="sidebar-link-text">الأقسام</span>
            </a>
            <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users sidebar-link-icon"></i>
                <span class="sidebar-link-text">المستخدمون</span>
            </a>
            <a href="{{ route('locations.index') }}" class="sidebar-link {{ request()->routeIs('locations.*') ? 'active' : '' }}">
                <i class="fa-solid fa-map-location-dot sidebar-link-icon"></i>
                <span class="sidebar-link-text">المواقع</span>
            </a>
            @if($u->company)
                <a href="{{ route('settings.theme') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-palette sidebar-link-icon"></i>
                    <span class="sidebar-link-text">الهوية البصرية</span>
                </a>
            @endif
        @endcan

        @can('inventory-access')
            <div class="sidebar-section-title">المخزون والمشتريات</div>
            <a href="{{ route('spare-parts.index') }}" class="sidebar-link {{ request()->routeIs('spare-parts.*') ? 'active' : '' }}">
                <i class="fa-solid fa-gears sidebar-link-icon"></i>
                <span class="sidebar-link-text">قطع الغيار</span>
            </a>
            <a href="{{ route('spare-categories.index') }}" class="sidebar-link {{ request()->routeIs('spare-categories.*') ? 'active' : '' }}">
                <i class="fa-solid fa-layer-group sidebar-link-icon"></i>
                <span class="sidebar-link-text">تصنيفات الإسبير</span>
            </a>
            <a href="{{ route('inventory.items.index') }}" class="sidebar-link {{ request()->routeIs('inventory.items.*') ? 'active' : '' }}">
                <i class="fa-solid fa-boxes-stacked sidebar-link-icon"></i>
                <span class="sidebar-link-text">الأصناف</span>
            </a>
            <a href="{{ route('inventory.categories.index') }}" class="sidebar-link {{ request()->routeIs('inventory.categories.*') ? 'active' : '' }}">
                <i class="fa-solid fa-tags sidebar-link-icon"></i>
                <span class="sidebar-link-text">الفئات</span>
            </a>
            <a href="{{ route('stock-transactions.index') }}" class="sidebar-link {{ request()->routeIs('stock-transactions.*') ? 'active' : '' }}">
                <i class="fa-solid fa-right-left sidebar-link-icon"></i>
                <span class="sidebar-link-text">حركات المخزون</span>
            </a>
        @endcan

        @can('platform-access')
            <div class="sidebar-section-title">المنصة</div>
            <a href="{{ route('companies.index') }}" class="sidebar-link {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                <i class="fa-solid fa-building sidebar-link-icon"></i>
                <span class="sidebar-link-text">الشركات</span>
            </a>
            <a href="{{ route('subscriptions.index') }}" class="sidebar-link {{ request()->routeIs('subscriptions.*') ? 'active' : '' }}">
                <i class="fa-solid fa-credit-card sidebar-link-icon"></i>
                <span class="sidebar-link-text">الاشتراكات</span>
            </a>
        @endcan
    </nav>

    <div class="sidebar-footer">
        <div class="avatar sm">{{ $u->initials() }}</div>
        <div style="flex:1; min-width:0">
            <div class="sf-name">{{ $u->name }}</div>
            <div class="sf-role">{{ \App\Support\Roles::label($u->roles->first()?->name) }}</div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="sf-logout" title="تسجيل الخروج"><i class="fa-solid fa-right-from-bracket"></i></button>
        </form>
    </div>
</aside>
