import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// ── Remember Selection ──────────────────────────────────────────────────────
// Uso: agrega data-remember="clave_unica" a cualquier <select> o <input>
// Al cambiar valor se guarda en localStorage["remember_{clave}"]
// Al cargar página se restaura el valor guardado (sin auto-submit)
(function () {
    function restoreSelections() {
        document.querySelectorAll('[data-remember]').forEach(function (el) {
            var key = 'remember_' + el.dataset.remember;
            var saved = localStorage.getItem(key);
            if (saved === null) return;

            // Restaurar solo si el valor existe entre las opciones (para <select>)
            if (el.tagName === 'SELECT') {
                var exists = Array.from(el.options).some(function (o) { return o.value === saved; });
                if (exists) el.value = saved;
            } else {
                el.value = saved;
            }
        });
    }

    function attachSaveListeners() {
        document.querySelectorAll('[data-remember]').forEach(function (el) {
            el.addEventListener('change', function () {
                var key = 'remember_' + el.dataset.remember;
                localStorage.setItem(key, el.value);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        restoreSelections();
        attachSaveListeners();
    });
})();
