<script>
// Injects a show/hide (eye) toggle into every password field on the page.
document.querySelectorAll('input[type=password]').forEach(function (inp) {
    if (inp.dataset.pwReady) return;
    inp.dataset.pwReady = '1';
    var wrap = document.createElement('div');
    wrap.style.position = 'relative';
    inp.parentNode.insertBefore(wrap, inp);
    wrap.appendChild(inp);
    inp.style.paddingLeft = '44px';
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'pw-toggle';
    btn.setAttribute('aria-label', 'إظهار / إخفاء كلمة المرور');
    btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
    btn.addEventListener('click', function () {
        var show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        btn.innerHTML = show ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
    });
    wrap.appendChild(btn);
});
</script>
