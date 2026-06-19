{{-- Quick "add location" used next to any location/parent <select>.
     Params: $targetSelect (id of the select to append to), $locationOptions (collection for the parent picker). --}}
@push('modals')
<div class="modal-overlay" id="quickLocModal">
    <div class="modal">
        <div class="modal-head"><h3>إضافة موقع جديد</h3><span class="close" onclick="closeModal('quickLocModal')"><i class="fa-solid fa-xmark"></i></span></div>
        <div class="modal-body">
            <div id="quickLocError" class="alert alert-danger" style="display:none"></div>
            <div class="form-group full mb-4">
                <label class="form-label">اسم الموقع <span class="req">*</span></label>
                <input type="text" id="qlName" class="form-control" placeholder="مثال: المبنى الرئيسي / الدور الثالث">
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">النوع</label>
                    <select id="qlType" class="form-select">
                        @foreach(\App\Models\Location::TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">يتبع لـ (اختياري)</label>
                    <select id="qlParent" class="form-select">
                        <option value="">— موقع رئيسي —</option>
                        @foreach($locationOptions as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->full_path ?: $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-primary" onclick="quickAddLocation()"><i class="fa-solid fa-plus"></i> إضافة</button>
            <button type="button" class="btn btn-light" onclick="closeModal('quickLocModal')">إلغاء</button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
async function quickAddLocation(){
    const name = document.getElementById('qlName').value.trim();
    const errBox = document.getElementById('quickLocError');
    errBox.style.display = 'none';
    if(!name){ errBox.textContent = 'يرجى إدخال اسم الموقع'; errBox.style.display = 'flex'; return; }

    try {
        const res = await fetch("{{ route('locations.quick') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, type: document.getElementById('qlType').value, parent_id: document.getElementById('qlParent').value || null })
        });
        if(!res.ok){ throw new Error('failed'); }
        const loc = await res.json();

        // Append to the target select(s) and select the new one.
        document.querySelectorAll('#{{ $targetSelect }}').forEach(sel => {
            const o = document.createElement('option');
            o.value = loc.id; o.textContent = loc.full_path; o.selected = true;
            sel.appendChild(o);
        });
        // Also add it as a parent option for further nesting.
        const parentSel = document.getElementById('qlParent');
        if(parentSel){ const po = document.createElement('option'); po.value = loc.id; po.textContent = loc.full_path; parentSel.appendChild(po); }

        document.getElementById('qlName').value = '';
        closeModal('quickLocModal');
    } catch(e){
        errBox.textContent = 'تعذّر إضافة الموقع، تأكد من صلاحياتك وحاول مجددًا.';
        errBox.style.display = 'flex';
    }
}
</script>
@endpush
