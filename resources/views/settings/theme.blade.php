@extends('layouts.app')
@section('title', 'الهوية البصرية')
@section('page-title', 'إعدادات الهوية البصرية')
@section('page-sub', 'خصّص ألوان وشعار شركتك — تُطبَّق على كامل النظام')

@section('content')
@php
    $cur = [
        'primary' => $company->primary_color ?: $defaults['primary'],
        'sidebar' => $company->sidebar_color ?: $defaults['sidebar'],
        'bg' => $company->bg_color ?: $defaults['bg'],
    ];
@endphp

<div class="detail-grid">
    {{-- Form --}}
    <form action="{{ route('settings.theme.update') }}" method="POST" enctype="multipart/form-data" class="card card-body">
        @csrf

        <h3 class="fw-700 mb-3"><i class="fa-solid fa-palette text-muted"></i> الألوان</h3>
        <div class="form-grid">
            @foreach([['primary_color','اللون الأساسي','primary'], ['sidebar_color','لون الشريط الجانبي','sidebar'], ['bg_color','لون الخلفية','bg']] as [$field,$label,$key])
                <div class="form-group">
                    <label class="form-label">{{ $label }}</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" class="theme-color" data-target="{{ $field }}" value="{{ $cur[$key] }}" style="width:46px;height:44px;border:1px solid var(--border-strong);border-radius:10px;background:none;cursor:pointer;padding:3px">
                        <input type="text" name="{{ $field }}" id="{{ $field }}" class="form-control theme-hex" value="{{ $cur[$key] }}" style="flex:1;font-family:monospace" maxlength="7" placeholder="#RRGGBB">
                    </div>
                </div>
            @endforeach
        </div>

        <hr class="divider">
        <h3 class="fw-700 mb-3"><i class="fa-solid fa-swatchbook text-muted"></i> قوالب جاهزة</h3>
        <div class="flex gap-2" style="flex-wrap:wrap">
            @foreach($presets as $p)
                <button type="button" class="preset-btn" onclick="applyPreset('{{ $p['primary'] }}','{{ $p['sidebar'] }}','{{ $p['bg'] }}')" title="{{ $p['name'] }}">
                    <span style="background:{{ $p['primary'] }}"></span>
                    <span style="background:{{ $p['sidebar'] }}"></span>
                    <span style="background:{{ $p['bg'] }};border:1px solid var(--border)"></span>
                </button>
            @endforeach
        </div>

        <hr class="divider">
        <h3 class="fw-700 mb-3"><i class="fa-solid fa-image text-muted"></i> شعار الشركة</h3>
        <div class="flex items-center gap-3 mb-3">
            <div id="logoPreview" style="width:64px;height:64px;border-radius:12px;border:1px solid var(--border);background:var(--surface-2);display:grid;place-items:center;overflow:hidden">
                @if($company->logoUrl())
                    <img src="{{ $company->logoUrl() }}" style="width:100%;height:100%;object-fit:contain">
                @else
                    <i class="fa-solid fa-building text-soft" style="font-size:24px"></i>
                @endif
            </div>
            <div style="flex:1">
                <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg,.svg,.webp" onchange="previewLogo(this)">
                <span class="form-hint">PNG / SVG مفضّل، بخلفية شفافة، حتى 2 ميجابايت.</span>
            </div>
        </div>
        @if($company->logo)
            <label class="switch"><input type="checkbox" name="remove_logo" value="1"><span class="track"></span><span class="text-sm">حذف الشعار الحالي</span></label>
        @endif

        <div class="flex gap-2 mt-5">
            <button class="btn btn-primary"><i class="fa-solid fa-save"></i> حفظ وتطبيق</button>
            <button type="button" class="btn btn-light" onclick="resetLive()">معاينة الافتراضي</button>
        </div>
    </form>

    {{-- Live preview + reset --}}
    <div>
        <div class="card card-body mb-4">
            <h3 class="fw-700 mb-3">معاينة مباشرة</h3>
            <p class="text-sm text-muted mb-3">تتغيّر ألوان النظام بالكامل أمامك مباشرةً أثناء التعديل (الشريط الجانبي، الأزرار، الشارات).</p>
            <div style="border:1px solid var(--border);border-radius:14px;overflow:hidden">
                <div style="display:flex;height:150px">
                    <div id="pvSidebar" style="width:70px;background:var(--sidebar-bg, #0f172a);display:flex;flex-direction:column;align-items:center;gap:10px;padding-top:14px">
                        <div style="width:34px;height:34px;border-radius:9px;background:var(--primary)"></div>
                        <div style="width:30px;height:8px;border-radius:5px;background:rgba(255,255,255,.18)"></div>
                        <div style="width:30px;height:8px;border-radius:5px;background:rgba(255,255,255,.1)"></div>
                        <div style="width:30px;height:8px;border-radius:5px;background:rgba(255,255,255,.1)"></div>
                    </div>
                    <div style="flex:1;background:var(--bg);padding:14px;display:flex;flex-direction:column;gap:10px">
                        <div class="flex gap-2"><span class="badge badge-indigo" style="background:var(--primary-soft);color:var(--primary)">شارة</span><span class="badge badge-green">مكتمل</span></div>
                        <button type="button" class="btn btn-primary btn-sm" style="width:fit-content">زر أساسي</button>
                        <div style="height:8px;width:80%;background:#fff;border:1px solid var(--border);border-radius:6px"></div>
                        <div style="height:8px;width:60%;background:#fff;border:1px solid var(--border);border-radius:6px"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-body">
            <div class="kv"><span class="k">الشركة</span><span class="v">{{ $company->name }}</span></div>
            <form action="{{ route('settings.theme.reset') }}" method="POST" onsubmit="return confirm('إعادة الهوية البصرية للوضع الافتراضي؟')" class="mt-3">
                @csrf
                <button class="btn btn-ghost btn-sm" style="color:var(--danger)"><i class="fa-solid fa-rotate-left"></i> إعادة للوضع الافتراضي</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const D = { primary: '{{ $defaults['primary'] }}', sidebar: '{{ $defaults['sidebar'] }}', bg: '{{ $defaults['bg'] }}' };

// --- colour math (mirror of App\Support\Theme) ---
const rgb = h => [parseInt(h.substr(1,2),16), parseInt(h.substr(3,2),16), parseInt(h.substr(5,2),16)];
const hx = (r,g,b) => '#' + [r,g,b].map(v => Math.max(0,Math.min(255,Math.round(v))).toString(16).padStart(2,'0')).join('');
const adjust = (h,p) => { const [r,g,b]=rgb(h), f=1+p/100; return hx(r*f,g*f,b*f); };
const tint = (h,p) => { const [r,g,b]=rgb(h), f=p/100; return hx(r+(255-r)*f, g+(255-g)*f, b+(255-b)*f); };
const isHex = h => /^#[0-9a-fA-F]{6}$/.test(h);

function fields(){ return { primary: document.getElementById('primary_color').value, sidebar: document.getElementById('sidebar_color').value, bg: document.getElementById('bg_color').value }; }
function applyLive(){
    const v = fields(); const r = document.documentElement.style;
    if (isHex(v.primary)) { r.setProperty('--primary', v.primary); r.setProperty('--primary-600', adjust(v.primary,-8)); r.setProperty('--primary-700', adjust(v.primary,-16)); r.setProperty('--primary-soft', tint(v.primary,90)); }
    if (isHex(v.bg)) r.setProperty('--bg', v.bg);
    if (isHex(v.sidebar)) r.setProperty('--sidebar-bg', 'linear-gradient(180deg,' + adjust(v.sidebar,6) + ' 0%,' + v.sidebar + ' 100%)');
}
// sync color picker <-> hex text, apply live on change
document.querySelectorAll('.theme-color').forEach(c => c.addEventListener('input', () => { document.getElementById(c.dataset.target).value = c.value; applyLive(); }));
document.querySelectorAll('.theme-hex').forEach(t => t.addEventListener('input', () => { if(isHex(t.value)){ const c = document.querySelector('.theme-color[data-target="'+t.id+'"]'); if(c) c.value = t.value; applyLive(); } }));

function applyPreset(p,s,b){
    document.getElementById('primary_color').value = p; document.querySelector('.theme-color[data-target=primary_color]').value = p;
    document.getElementById('sidebar_color').value = s; document.querySelector('.theme-color[data-target=sidebar_color]').value = s;
    document.getElementById('bg_color').value = b; document.querySelector('.theme-color[data-target=bg_color]').value = b;
    applyLive();
}
function resetLive(){ applyPreset(D.primary, D.sidebar, D.bg); }
function previewLogo(input){
    if (!input.files || !input.files[0]) return;
    const url = URL.createObjectURL(input.files[0]);
    document.getElementById('logoPreview').innerHTML = '<img src="'+url+'" style="width:100%;height:100%;object-fit:contain">';
}
</script>
@endpush
