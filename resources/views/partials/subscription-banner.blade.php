@php $co = auth()->user()?->company; @endphp
@if($co && in_array($co->subscription_status, ['trial', 'grace']))
    @php $d = $co->daysRemaining(); @endphp
    @if($d !== null && $d <= 7)
        @php $urgent = $co->subscription_status === 'grace' || $d <= 1; @endphp
        <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:12px;margin-bottom:16px;
                    background:{{ $urgent ? '#fef2f2' : '#fffbeb' }};border:1px solid {{ $urgent ? '#fecaca' : '#fde68a' }};
                    color:{{ $urgent ? '#b91c1c' : '#92400e' }}">
            <i class="fa-solid {{ $urgent ? 'fa-triangle-exclamation' : 'fa-clock' }}"></i>
            <div style="flex:1">
                @if($co->subscription_status === 'grace' || $d < 0)
                    انتهت {{ $co->isOnTrial() ? 'فترتك التجريبية' : 'باقتك' }} — جدّد الآن لتجنّب إيقاف الحساب.
                @else
                    تنتهي {{ $co->isOnTrial() ? 'تجربتك المجانية' : 'باقتك' }} خلال <strong>{{ $d }}</strong> يوم.
                @endif
            </div>
            @can('admin-access')
                <a href="{{ route('company.subscription') }}" class="btn btn-primary" style="padding:6px 14px;font-size:13px">
                    عرض الباقات والتجديد
                </a>
            @endcan
        </div>
    @endif
@endif
