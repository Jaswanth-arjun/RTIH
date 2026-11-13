/**
 * TaskFlow — progressive enhancement.
 * No framework, no dependencies, no inline scripts (CSP friendly).
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var body = document.body;
    var sidebar = document.getElementById('sidebar');
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var backdrop = document.querySelector('[data-sidebar-close]');

    function setSidebar(open) {
        if (!sidebar || !toggle) {
            return;
        }

        sidebar.classList.toggle('is-open', open);
        body.classList.toggle('nav-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');

        if (backdrop) {
            backdrop.hidden = !open;
        }

        if (open) {
            var firstLink = sidebar.querySelector('a, button');
            if (firstLink) {
                firstLink.focus();
            }
        }
    }

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            setSidebar(!sidebar.classList.contains('is-open'));
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            setSidebar(false);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
            setSidebar(false);
            if (toggle) {
                toggle.focus();
            }
        }
    });

    document.querySelectorAll('[data-dismiss]').forEach(function (button) {
        button.addEventListener('click', function () {
            var alert = button.closest('[data-alert]');
            if (alert) {
                alert.remove();
            }
        });
    });

    document.querySelectorAll('[data-char-count]').forEach(function (counter) {
        var target = document.getElementById(counter.getAttribute('data-char-count'));
        if (!target) {
            return;
        }

        var max = parseInt(target.getAttribute('maxlength') || '0', 10);

        function update() {
            var length = Array.from(target.value).length;
            counter.textContent = max > 0 ? length + ' / ' + max : String(length);
        }

        target.addEventListener('input', update);
        update();
    });

    document.querySelectorAll('[data-history-back]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = button.getAttribute('data-fallback') || '/';
            }
        });
    });

    /**
     * Friendly frontend validation in addition to the server-side rules.
     * Native HTML validation catches most issues; this also rejects whitespace-only titles.
     */
    document.querySelectorAll('[data-task-form]').forEach(function (form) {
        var title = form.querySelector('#title');

        if (!title) {
            return;
        }

        title.addEventListener('input', function () {
            title.setCustomValidity('');
        });

        form.addEventListener('submit', function (event) {
            if (title.value.trim() === '') {
                event.preventDefault();
                title.value = '';
                title.setCustomValidity('Please enter a task title.');
                title.reportValidity();
                title.focus();
            }
        });
    });
});
