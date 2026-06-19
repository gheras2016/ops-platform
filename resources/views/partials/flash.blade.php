@if (session('success'))
    <div class="toast-wrap">
        <div class="toast"><i class="fa-solid fa-circle-check" style="color:var(--success)"></i> {{ session('success') }}</div>
    </div>
@endif

@if (session('error'))
    <div class="toast-wrap">
        <div class="toast danger"><i class="fa-solid fa-circle-exclamation" style="color:var(--danger)"></i> {{ session('error') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            <strong>يرجى مراجعة المدخلات:</strong>
            <ul style="margin-top:4px; padding-right:18px; list-style:disc;">
                @foreach ($errors->all() as $error)
                    <li style="font-size:13px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
