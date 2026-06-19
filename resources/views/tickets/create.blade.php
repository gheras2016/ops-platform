@extends('layouts.app')
@section('title', 'رفع بلاغ جديد')
@section('page-title', 'رفع بلاغ جديد')
@section('page-sub', 'اختر القسم المختص وصف المشكلة')

@section('content')
@php
    $deptIcons = ['it'=>'fa-laptop-code','maintenance'=>'fa-screwdriver-wrench','mechanical'=>'fa-gears','electrical'=>'fa-bolt','hvac'=>'fa-snowflake','plumbing'=>'fa-faucet','civil'=>'fa-trowel-bricks','safety'=>'fa-helmet-safety','general'=>'fa-toolbox'];
    $typeExamples = [
        'it' => 'مثال: بطء شديد في الكمبيوتر / انقطاع الشبكة',
        'maintenance' => 'مثال: تسريب مياه / باب لا يغلق',
        'mechanical' => 'مثال: صوت غير طبيعي من المحرك',
        'electrical' => 'مثال: انقطاع كهرباء / مقبس معطل',
        'hvac' => 'مثال: المكيف لا يبرد',
        'plumbing' => 'مثال: تسريب في السباكة',
        'civil' => 'مثال: تشقق في الجدار',
        'safety' => 'مثال: طفاية حريق منتهية الصلاحية',
    ];
    $deptExamples = $departments->mapWithKeys(fn($d) => [$d->id => ($typeExamples[$d->type] ?? 'اكتب عنوانًا مختصرًا للمشكلة')]);
    $locationData = $locations->map(fn($l) => ['id' => $l->id, 'name' => $l->name, 'type' => $l->type, 'parent' => $l->parent_id]);
@endphp

<div class="breadcrumb"><a href="{{ route('tickets.index') }}">التذاكر</a> <i class="fa-solid fa-chevron-left"></i> <span>بلاغ جديد</span></div>

<form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="detail-grid">
        <div class="card card-body">
            <div class="form-group full mb-4">
                <label class="form-label">القسم المختص <span class="req">*</span></label>
                <p class="form-hint mb-3">اختر الجهة المسؤولة عن معالجة هذا البلاغ</p>
                <div class="choice-grid" id="deptPicker">
                    @foreach($departments as $d)
                        <label class="choice" data-choice>
                            <input type="radio" name="department_id" value="{{ $d->id }}" @checked(old('department_id') == $d->id) required>
                            <div class="choice-icon"><i class="fa-solid {{ $deptIcons[$d->type] ?? 'fa-toolbox' }}"></i></div>
                            <div>
                                <div class="choice-title">{{ $d->name }}</div>
                                <div class="choice-sub">{{ $d->typeLabel() }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group full">
                    <label class="form-label">عنوان المشكلة <span class="req">*</span></label>
                    <input type="text" id="ticketTitle" name="title" class="form-control" value="{{ old('title') }}" placeholder="اكتب عنوانًا مختصرًا للمشكلة" required>
                </div>
                <div class="form-group full">
                    <label class="form-label">وصف تفصيلي للمشكلة</label>
                    <textarea name="description" class="form-control" placeholder="اشرح المشكلة بالتفصيل، متى بدأت، وأي ملاحظات مهمة...">{{ old('description') }}</textarea>
                </div>
                <input type="hidden" name="location_id" id="locationId" value="{{ old('location_id', auth()->user()->location_id) }}">
                <div class="form-group">
                    <label class="form-label">المبنى</label>
                    <select id="locBuilding" class="form-select"><option value="">— اختر —</option></select>
                </div>
                <div class="form-group">
                    <label class="form-label">الدور</label>
                    <select id="locFloor" class="form-select"><option value="">— اختر —</option></select>
                </div>
                <div class="form-group">
                    <label class="form-label">الغرفة / المنطقة</label>
                    <select id="locRoom" class="form-select"><option value="">— اختر —</option></select>
                </div>
                <div class="form-group">
                    <label class="form-label">تحديد أدق للموقع <span class="form-hint">(اختياري)</span></label>
                    <input type="text" name="location_detail" class="form-control" value="{{ old('location_detail') }}" placeholder="مثال: خلف المولّد الشرقي / بجانب المصعد">
                </div>
                <div class="form-group full">
                    <label class="form-label">مرفقات (صور / مستندات)</label>
                    <input type="file" name="files[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                    <span class="form-hint">يمكنك إرفاق صور للعطل أو مستندات داعمة (حتى 10 ميجابايت للملف).</span>
                </div>
            </div>
        </div>

        <div class="card card-body" style="position:sticky; top:90px;">
            <h3 class="fw-700 mb-2">ماذا يحدث بعد الإرسال؟</h3>
            <p class="text-sm text-muted mb-4">سيتبع البلاغ المسار التالي:</p>
            <div class="timeline">
                <div class="timeline-item"><div class="timeline-dot bg-gray"><i class="fa-solid fa-paper-plane"></i></div><div class="timeline-content"><div class="timeline-title">إرسال البلاغ</div><div class="timeline-meta">يُسجَّل البلاغ ويصل لرئيس القسم</div></div></div>
                <div class="timeline-item"><div class="timeline-dot bg-indigo"><i class="fa-solid fa-user-check"></i></div><div class="timeline-content"><div class="timeline-title">الإسناد لفني</div><div class="timeline-meta">يوزّع رئيس القسم البلاغ على الفني المناسب</div></div></div>
                <div class="timeline-item"><div class="timeline-dot bg-amber"><i class="fa-solid fa-screwdriver-wrench"></i></div><div class="timeline-content"><div class="timeline-title">التنفيذ</div><div class="timeline-meta">يبدأ الفني المعالجة ويحدّث نسبة الإنجاز</div></div></div>
                <div class="timeline-item"><div class="timeline-dot bg-green"><i class="fa-solid fa-circle-check"></i></div><div class="timeline-content"><div class="timeline-title">الاعتماد والإغلاق</div><div class="timeline-meta">يعتمد رئيس القسم الإنجاز ويُغلق البلاغ</div></div></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg mt-3"><i class="fa-solid fa-paper-plane"></i> إرسال البلاغ</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
/* ---- Department picker + dynamic title example ---- */
const picker = document.getElementById('deptPicker');
const deptExamples = @json($deptExamples);
const titleInput = document.getElementById('ticketTitle');
function syncChoices(){
    picker.querySelectorAll('[data-choice]').forEach(c => c.classList.toggle('selected', c.querySelector('input').checked));
    const sel = picker.querySelector('input:checked');
    if (sel && deptExamples[sel.value]) titleInput.placeholder = deptExamples[sel.value];
}
picker.addEventListener('change', syncChoices); syncChoices();

/* ---- Cascading location picker: building → floor → room ---- */
const locations = @json($locationData);
const elB = document.getElementById('locBuilding'), elF = document.getElementById('locFloor'), elR = document.getElementById('locRoom'), elId = document.getElementById('locationId');
const fill = (el, items, ph) => { el.innerHTML = `<option value="">${ph}</option>` + items.map(i=>`<option value="${i.id}">${i.name}</option>`).join(''); };
const childrenOf = (pid, types) => locations.filter(l => l.parent === pid && types.includes(l.type));
const topLevel = () => locations.filter(l => !l.parent || l.type === 'building');

function setId(){ elId.value = elR.value || elF.value || elB.value || ''; }
fill(elB, topLevel(), '— اختر —');
elB.onchange = () => { fill(elF, childrenOf(+elB.value, ['floor','area','room']), '— اختر —'); fill(elR, [], '— اختر —'); setId(); };
elF.onchange = () => { fill(elR, childrenOf(+elF.value, ['room','area']), '— اختر —'); setId(); };
elR.onchange = setId;

// Preselect the user's default location (walk its ancestry).
(function preselect(){
    const cur = elId.value ? locations.find(l => l.id == elId.value) : null;
    if (!cur) return;
    const chain = []; let n = cur; const byId = id => locations.find(l => l.id === id);
    while (n) { chain.unshift(n); n = n.parent ? byId(n.parent) : null; }
    const b = chain.find(l=>l.type==='building'); if (b){ elB.value=b.id; elB.onchange(); }
    const f = chain.find(l=>l.type==='floor'||l.type==='area'); if (f){ elF.value=f.id; elF.onchange(); }
    const r = chain.find(l=>l.type==='room'); if (r){ elR.value=r.id; }
    setId();
})();
</script>
@endpush
