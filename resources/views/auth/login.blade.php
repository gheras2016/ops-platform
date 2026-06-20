<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — OPS Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=3">
</head>
<body>
<div class="auth-wrap">
    {{-- Brand panel --}}
    <div class="auth-side">
        <div class="brand">
            <div class="sidebar-logo-icon" style="width:48px;height:48px;font-size:18px;">OP</div>
            <div>
                <div class="name">OPS Platform</div>
                <div class="tag">منصة إدارة عمليات الصيانة</div>
            </div>
        </div>

        <div class="auth-hero">
            <h1>نظام تشغيلي موحّد<br>لإدارة الصيانة والأعطال</h1>
            <p class="lead">من رفع البلاغ حتى الإغلاق والاعتماد — تتبّع كامل لدورة العمل، إدارة قطع الغيار والمشتريات، وتقارير لحظية للأداء عبر جميع الأقسام والشركات.</p>

            <div class="auth-features">
                <div class="auth-feature"><i class="fa-solid fa-route"></i> تتبّع كامل لدورة حياة البلاغ</div>
                <div class="auth-feature"><i class="fa-solid fa-boxes-stacked"></i> إدارة الإسبيرات والمخزون والمشتريات</div>
                <div class="auth-feature"><i class="fa-solid fa-people-group"></i> توزيع المهام واعتمادها بتسلسل إداري</div>
                <div class="auth-feature"><i class="fa-solid fa-chart-pie"></i> تقارير وتحليلات وتقرير PDF لكل بلاغ</div>
            </div>
        </div>

        <div class="copyright">© {{ date('Y') }} OPS Platform — جميع الحقوق محفوظة</div>
    </div>

    {{-- Form panel --}}
    <div class="auth-main">
        <div class="auth-card">
            <div class="auth-logo-m">
                <div class="sidebar-logo-icon" style="width:42px;height:42px;">OP</div>
                <div style="font-size:18px;font-weight:800;">OPS Platform</div>
            </div>

            <h2>أهلاً بعودتك 👋</h2>
            <p class="sub">سجّل الدخول للوصول إلى لوحة التحكم</p>

            @if ($errors->any())
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="form-group full mb-4">
                    <label class="form-label">البريد الإلكتروني</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="name@company.com" required autofocus>
                    </div>
                </div>
                <div class="form-group full mb-4">
                    <label class="form-label">كلمة المرور</label>
                    <div class="input-icon">
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                <label class="switch mb-5">
                    <input type="checkbox" name="remember"><span class="track"></span>
                    <span class="text-sm">تذكّرني على هذا الجهاز</span>
                </label>
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    <i class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول
                </button>
            </form>
        </div>
    </div>
</div>

@include('partials.password-toggle')
</body>
</html>
