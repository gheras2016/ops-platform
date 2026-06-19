{{-- Params: $importRoute, $templateRoute, $title --}}
@push('modals')
<div class="modal-overlay" id="importModal">
    <div class="modal">
        <form action="{{ $importRoute }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-head"><h3>استيراد {{ $title }}</h3><span class="close" onclick="closeModal('importModal')"><i class="fa-solid fa-xmark"></i></span></div>
            <div class="modal-body">
                <p class="text-sm text-muted mb-4">ارفع ملف Excel أو CSV. حمّل القالب أولًا لمعرفة الأعمدة المطلوبة. الصفوف تُحدَّث تلقائيًا إن تكرر نفس الرمز.</p>
                <a href="{{ $templateRoute }}" class="btn btn-light btn-sm mb-4"><i class="fa-solid fa-file-excel" style="color:var(--success)"></i> تحميل قالب Excel</a>
                <div class="form-group full">
                    <label class="form-label">الملف <span class="req">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required>
                    <span class="form-hint">الصيغ المدعومة: xlsx, xls, csv</span>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-primary"><i class="fa-solid fa-file-import"></i> استيراد</button>
                <button type="button" class="btn btn-light" onclick="closeModal('importModal')">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endpush
