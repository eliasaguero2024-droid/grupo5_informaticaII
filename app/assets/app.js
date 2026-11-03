// Solo detalles de interfaz: no hay reglas del juego en este archivo.
(function () {
    // Evita el doble clic: cuando se elige una opcion se bloquean las demas.
    document.querySelectorAll('form[data-una-vez]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            var elegido = ev.submitter;
            if (elegido) {
                elegido.classList.add('elegida');
                // El valor del boton tiene que viajar aunque se deshabilite.
                var oculto = document.createElement('input');
                oculto.type = 'hidden';
                oculto.name = elegido.name;
                oculto.value = elegido.value;
                form.appendChild(oculto);
            }
            setTimeout(function () {
                form.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
            }, 0);
        });
    });

    // Atajos de teclado: 1-4 para responder, Enter para seguir.
    document.addEventListener('keydown', function (ev) {
        if (ev.target && /INPUT|SELECT|TEXTAREA/.test(ev.target.tagName)) { return; }
        var el = document.querySelector('[data-atajo="' + ev.key + '"]');
        if (el) {
            ev.preventDefault();
            el.click();
        }
    });
})();
