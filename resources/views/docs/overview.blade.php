<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { font-family: dejavusans, sans-serif; color: #1e293b; direction: rtl; font-size: 11.5pt; line-height: 1.7; }
    .page { padding: 38px 46px; }
    h2 { color: #4f46e5; font-size: 16pt; margin: 0 0 6px; }
    h3 { color: #0f172a; font-size: 12.5pt; margin: 0 0 4px; }
    p { margin: 0 0 8px; }
    .muted { color: #64748b; }
    .small { font-size: 9.5pt; }

    /* Cover */
    .cover { background-color: #0f172a; color: #fff; height: 1120px; padding: 0; }
    .cover-inner { padding: 150px 60px 0; }
    .brand { font-size: 13pt; color: #a5b4fc; letter-spacing: 1px; margin-bottom: 30px; }
    .cover h1 { font-size: 40pt; margin: 0 0 14px; color: #fff; line-height: 1.25; }
    .cover .tag { font-size: 15pt; color: #cbd5e1; margin-bottom: 40px; }
    .pill { background-color: #4f46e5; color: #fff; padding: 7px 18px; border-radius: 20px; font-size: 11pt; }
    .cover-foot { position: absolute; bottom: 60px; right: 60px; left: 60px; color: #94a3b8; font-size: 10pt; border-top: 1px solid #334155; padding-top: 16px; }

    /* Bands & cards */
    .band { background-color: #f1f5f9; border-right: 4px solid #4f46e5; padding: 12px 16px; margin: 0 0 14px; border-radius: 6px; }
    .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 8px; }
    .accent { color: #4f46e5; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 6px 0 12px; }
    td { vertical-align: top; padding: 6px 8px; }
    .feat td { border-bottom: 1px solid #eef2f7; }
    .ic { color: #4f46e5; font-weight: bold; font-size: 13pt; }
    .step-no { background-color: #4f46e5; color: #fff; border-radius: 50%; width: 26px; height: 26px; text-align: center; font-weight: bold; }
    .footer-cta { background-color: #4f46e5; color: #fff; padding: 22px 26px; border-radius: 10px; text-align: center; }
    .footer-cta h2 { color: #fff; }
    .two td { width: 50%; }

    /* UI mockups */
    .mock { border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; margin: 8px 0 18px; }
    .mock-bar { background-color: #e2e8f0; padding: 6px 12px; font-size: 8pt; color: #64748b; }
    .mock-body td { padding: 0; }
    .mock-side { background-color: #0f172a; width: 26%; padding: 12px 8px; vertical-align: top; }
    .mock-side .brandbox { color: #fff; font-weight: bold; font-size: 9pt; margin-bottom: 10px; padding: 0 6px; }
    .mock-side .si { color: #cbd5e1; font-size: 8.5pt; padding: 5px 7px; }
    .mock-side .si.on { background-color: #4f46e5; color: #fff; border-radius: 5px; }
    .mock-main { background-color: #f8fafc; padding: 12px; vertical-align: top; }
    .mock-h { font-weight: bold; font-size: 11pt; color: #0f172a; margin-bottom: 8px; }
    .kpi { border: 1px solid #e2e8f0; background-color: #fff; border-radius: 8px; padding: 7px 4px; text-align: center; }
    .kpi .n { font-size: 15pt; font-weight: bold; color: #0f172a; }
    .kpi .l { font-size: 7.5pt; color: #64748b; }
    .b-green { background-color: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 8pt; }
    .b-amber { background-color: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 8pt; }
    .b-blue { background-color: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 10px; font-size: 8pt; }
    .bar { background-color: #e2e8f0; border-radius: 6px; }
    .bar-fill { background-color: #4f46e5; border-radius: 6px; height: 9px; font-size: 1pt; }
    .mini { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    .mini td { padding: 6px 8px; border-bottom: 1px solid #eef2f7; background-color: #fff; }
    .tl-row td { padding: 3px 0; font-size: 8.5pt; }
    .dot { color: #4f46e5; font-weight: bold; }
    .caption { color: #64748b; font-size: 8.5pt; margin: -10px 0 16px; }
</style>
</head>
<body>

{{-- ============ COVER ============ --}}
<div class="cover">
    <div class="cover-inner">
        <div class="brand">OPS PLATFORM</div>
        <h1>منصّة إدارة<br>عمليات الصيانة</h1>
        <div class="tag">نظام ذكي متعدّد الشركات لإدارة البلاغات وأوامر الصيانة<br>والمخزون والمشتريات — من البلاغ حتى الإغلاق.</div>
        <span class="pill">CMMS · Multi-Tenant SaaS</span>
    </div>
    <div class="cover-foot">
        عرض تعريفي للنظام &nbsp;•&nbsp; وثيقة موجّهة للشركات والعملاء المحتملين
    </div>
</div>

{{-- ============ PAGE 1: الفكرة + المشاكل ============ --}}
<pagebreak>
<div class="page">
    <h2>فكرة النظام</h2>
    <div class="band">
        منصّة سحابية واحدة تُمكّن كل منشأة من إدارة دورة الصيانة بالكامل رقمياً: يرفع الموظف بلاغاً،
        يوزّعه رئيس القسم على الفنيين، يتابع الفني التنفيذ خطوة بخطوة، ويُعتمد الإنجاز ويُغلق —
        مع ربط مباشر بالمخزون وقطع الغيار ودورة الشراء، ولوحات تحليلية لحظية.
    </div>
    <p class="muted">نظام واحد يخدم عدّة شركات بعزل تام للبيانات وهوية بصرية مستقلة لكل شركة (White-Label).</p>

    <h2 style="margin-top:22px">مشاكل يحلّها النظام</h2>
    <table class="feat">
        <tr><td class="ic">✕</td><td><b>بلاغات الصيانة عبر الهاتف والورق</b><br><span class="muted small">تضيع، لا تُتابَع، ولا يوجد سجل لمن فعل ماذا ومتى.</span></td></tr>
        <tr><td class="ic">✕</td><td><b>غياب المساءلة وتتبّع الأداء</b><br><span class="muted small">لا تعرف زمن الاستجابة ولا إنتاجية الفنيين ولا أسباب التأخير.</span></td></tr>
        <tr><td class="ic">✕</td><td><b>انفصال الصيانة عن المخزون والمشتريات</b><br><span class="muted small">نقص قطع الغيار يوقف العمل دون مسار واضح للشراء.</span></td></tr>
        <tr><td class="ic">✕</td><td><b>صعوبة إدارة عدّة فروع/شركات</b><br><span class="muted small">أنظمة منفصلة ومكلفة بدل منصّة واحدة موحّدة.</span></td></tr>
    </table>
</div>

{{-- ============ PAGE 2: المميزات ============ --}}
<pagebreak>
<div class="page">
    <h2>المميزات الرئيسية</h2>

    <table class="two"><tr>
        <td>
            <div class="card"><span class="ic">◆</span> <b>دورة بلاغ كاملة</b><br><span class="muted small">من الفتح للإغلاق مع آلة حالات واضحة واعتماد الإنجاز.</span></div>
            <div class="card"><span class="ic">◆</span> <b>سجل زمني قابل للتدقيق</b><br><span class="muted small">كل إجراء مُوثّق: من، متى، وماذا تغيّر.</span></div>
            <div class="card"><span class="ic">◆</span> <b>إدارة المخزون وقطع الغيار</b><br><span class="muted small">خصم تلقائي عند الصرف وربط التكلفة بالتذكرة.</span></div>
            <div class="card"><span class="ic">◆</span> <b>سلسلة اعتماد شراء ذكية</b><br><span class="muted small">من رئيس القسم إلى الإدارة إلى المالية تلقائياً.</span></div>
        </td>
        <td>
            <div class="card"><span class="ic">◆</span> <b>تقارير وتحليلات لحظية</b><br><span class="muted small">مؤشرات أداء + تصدير Excel / PDF.</span></div>
            <div class="card"><span class="ic">◆</span> <b>إشعارات فورية</b><br><span class="muted small">لكل طرف عند كل تحديث يخصّه.</span></div>
            <div class="card"><span class="ic">◆</span> <b>تعدّد الشركات وعزل البيانات</b><br><span class="muted small">كل شركة معزولة تماماً عن الأخرى.</span></div>
            <div class="card"><span class="ic">◆</span> <b>هوية بصرية لكل شركة</b><br><span class="muted small">ألوان وشعار خاص — جاهز White-Label.</span></div>
        </td>
    </tr></table>

    <h2 style="margin-top:14px">الفئات المستهدفة</h2>
    <table class="feat">
        <tr><td class="ic">▸</td><td><b>المصانع والمنشآت الصناعية</b> — صيانة الخطوط والمعدّات وقطع الغيار.</td></tr>
        <tr><td class="ic">▸</td><td><b>المستشفيات والمنشآت الصحية</b> — صيانة الأجهزة والمرافق الحيوية.</td></tr>
        <tr><td class="ic">▸</td><td><b>المجمّعات التجارية والفنادق</b> — صيانة المباني والمرافق (HVAC، كهرباء…).</td></tr>
        <tr><td class="ic">▸</td><td><b>الجهات الحكومية والتعليمية</b> — إدارة بلاغات المرافق عبر فروع متعددة.</td></tr>
        <tr><td class="ic">▸</td><td><b>شركات إدارة المرافق (FM)</b> — إدارة عملاء متعددين على منصّة واحدة.</td></tr>
    </table>
</div>

{{-- ============ PAGE 3: كيف يعمل + الواجهات ============ --}}
<pagebreak>
<div class="page">
    <h2>كيف يعمل النظام — خطوة بخطوة</h2>
    <table>
        <tr><td class="step-no">1</td><td><b>رفع البلاغ</b><br><span class="muted small">يصف الموظف العطل ويحدّد القسم والموقع، ويرفق صوراً عند الحاجة.</span></td></tr>
        <tr><td class="step-no">2</td><td><b>الإسناد</b><br><span class="muted small">رئيس القسم يُسند البلاغ لفني ويحدّد الأولوية والموعد المتوقّع.</span></td></tr>
        <tr><td class="step-no">3</td><td><b>التنفيذ والمتابعة</b><br><span class="muted small">الفني يقبل ويبدأ، يحدّث نسبة الإنجاز، ويوقف مؤقتاً مع تسجيل السبب إن لزم.</span></td></tr>
        <tr><td class="step-no">4</td><td><b>قطع الغيار</b><br><span class="muted small">عند الحاجة لقطعة: تُصرف من المخزون أو تُحوَّل تلقائياً لدورة شراء معتمدة.</span></td></tr>
        <tr><td class="step-no">5</td><td><b>الإنجاز والاعتماد</b><br><span class="muted small">الفني ينهي ويسجّل القطع المستخدمة، ورئيس القسم يعتمد ويُغلق البلاغ.</span></td></tr>
        <tr><td class="step-no">6</td><td><b>التحليل</b><br><span class="muted small">تُحدَّث المؤشرات والتقارير لحظياً لقياس الأداء واتخاذ القرار.</span></td></tr>
    </table>

    <h2 style="margin-top:18px">لمحة عن الواجهات</h2>
    <table class="feat">
        <tr><td class="ic">▣</td><td><b>لوحة تحكم ذكية</b> — مؤشرات مخصّصة حسب دور المستخدم.</td></tr>
        <tr><td class="ic">▣</td><td><b>لوحة المهام (Board)</b> — عرض البلاغات حسب الحالة مع إجراءات سريعة.</td></tr>
        <tr><td class="ic">▣</td><td><b>صفحة تفاصيل البلاغ</b> — حالة، نسبة إنجاز، سجل زمني، تعليقات، مرفقات.</td></tr>
        <tr><td class="ic">▣</td><td><b>بوّابة المبلّغ</b> — واجهة مبسّطة لرفع البلاغ ومتابعته مباشرة.</td></tr>
        <tr><td class="ic">▣</td><td><b>التقارير</b> — رسوم بيانية وتصدير احترافي للبيانات.</td></tr>
        <tr><td class="ic">▣</td><td><b>واجهة عربية كاملة (RTL)</b> — تصميم عصري واحترافي.</td></tr>
    </table>

    </div>

{{-- ============ PAGE 4: معاينة الواجهات ============ --}}
<pagebreak>
<div class="page">
    <h2>معاينة الواجهات</h2>
    <p class="caption">نماذج تمثيلية للواجهة العربية (RTL) بنفس نظام التصميم الفعلي للنظام.</p>

    <h3 style="margin-top:8px">لوحة التحكم</h3>
    <div class="mock">
        <div class="mock-bar">● ● ● &nbsp;&nbsp; ops-platform — لوحة التحكم</div>
        <table class="mock-body"><tr>
            <td class="mock-side">
                <div class="brandbox">◼ شركة الرواد</div>
                <div class="si on">▤ لوحة التحكم</div>
                <div class="si">🎫 التذاكر</div>
                <div class="si">▦ لوحة المهام</div>
                <div class="si">⛟ طلبات الشراء</div>
                <div class="si">📊 التقارير</div>
                <div class="si">⚙ الإدارة</div>
            </td>
            <td class="mock-main">
                <div class="mock-h">مرحباً، سعد 👋</div>
                <table style="width:100%"><tr>
                    <td style="width:25%; padding:3px"><div class="kpi"><div class="n" style="color:#64748b">18</div><div class="l">مفتوحة</div></div></td>
                    <td style="width:25%; padding:3px"><div class="kpi"><div class="n" style="color:#d97706">7</div><div class="l">قيد التنفيذ</div></div></td>
                    <td style="width:25%; padding:3px"><div class="kpi"><div class="n" style="color:#dc2626">3</div><div class="l">متأخّرة</div></div></td>
                    <td style="width:25%; padding:3px"><div class="kpi"><div class="n" style="color:#16a34a">42</div><div class="l">مُغلقة هذا الشهر</div></div></td>
                </tr></table>
                <table class="mini" style="margin-top:8px">
                    <tr><td><b>TKT-1-2026-0042</b></td><td>تسريب مكيّف</td><td><span class="b-amber">قيد التنفيذ</span></td></tr>
                    <tr><td><b>TKT-1-2026-0041</b></td><td>عطل لوحة كهرباء</td><td><span class="b-blue">مُسندة</span></td></tr>
                    <tr><td><b>TKT-1-2026-0040</b></td><td>صيانة مصعد</td><td><span class="b-green">مُغلقة</span></td></tr>
                </table>
            </td>
        </tr></table>
    </div>

    <h3>صفحة تفاصيل البلاغ</h3>
    <div class="mock">
        <div class="mock-bar">● ● ● &nbsp;&nbsp; ops-platform — تفاصيل التذكرة</div>
        <div style="padding:14px; background-color:#fff">
            <table style="width:100%"><tr>
                <td><b style="font-size:11pt">TKT-1-2026-0042</b> &nbsp; <span class="b-amber">قيد التنفيذ</span><br><span class="muted small">تسريب مياه من وحدة التكييف — الدور الثاني</span></td>
                <td style="text-align:left; width:30%"><span class="muted small">نسبة الإنجاز 60%</span><div class="bar" style="margin-top:4px"><div class="bar-fill" style="width:60%">&nbsp;</div></div></td>
            </tr></table>
            <hr style="border:none; border-top:1px solid #eef2f7; margin:10px 0">
            <table style="width:100%"><tr>
                <td style="width:58%; vertical-align:top">
                    <b class="small">السجل الزمني</b>
                    <table style="margin-top:6px">
                        <tr class="tl-row"><td><span class="dot">●</span> أُنشئ البلاغ <span class="muted">— موظف 1</span></td></tr>
                        <tr class="tl-row"><td><span class="dot">●</span> أُسند للفني وليد <span class="muted">— رئيس القسم</span></td></tr>
                        <tr class="tl-row"><td><span class="dot">●</span> بدأ العمل <span class="muted">— الفني</span></td></tr>
                        <tr class="tl-row"><td><span class="dot">●</span> تحديث الإنجاز 60% <span class="muted">— الفني</span></td></tr>
                    </table>
                </td>
                <td style="width:42%; vertical-align:top; padding-right:10px">
                    <div class="card" style="margin:0"><span class="muted small">الأولوية</span><br><span class="b-amber">عالية</span></div>
                    <div class="card" style="margin-top:6px"><span class="muted small">الفني المسؤول</span><br><b class="small">وليد الفني</b></div>
                    <div class="card" style="margin-top:6px"><span class="muted small">الموعد المتوقّع</span><br><b class="small">2026-06-20</b></div>
                </td>
            </tr></table>
        </div>
    </div>

    <div class="footer-cta" style="margin-top:10px">
        <h2>منصّة واحدة... صيانة بلا فوضى</h2>
        <p style="margin:0">نظّم عمليات الصيانة، اضبط التكاليف، وارفع كفاءة فرقك — مع تقارير تدعم قرارك.</p>
    </div>
    <p class="muted small" style="text-align:center; margin-top:14px">OPS Platform &nbsp;—&nbsp; نظام إدارة عمليات الصيانة متعدّد الشركات</p>
</div>

</body>
</html>
