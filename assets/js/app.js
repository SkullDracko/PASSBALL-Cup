/**
 * PASSBALL Cup - JavaScript Principal
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- Mobile nav toggle ---
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });

        // Cerrar menú al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('active');
            }
        });
    }

    // --- Auto-cerrar alerts ---
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // --- Alerts enviados desde el servidor ---
    document.querySelectorAll('[data-custom-alert]').forEach(alert => {
        showAlert(
            alert.dataset.customAlert,
            alert.dataset.alertType || 'error',
            5000,
            alert
        );
        alert.removeAttribute('data-custom-alert');
    });
});

/**
 * Mostrar una alerta accesible dentro de su espacio reservado.
 */
function showAlert(message, type = 'error', duration = 5000, target = document.getElementById('loginAlert')) {
    if (!message || !target) return;

    const allowedTypes = ['success', 'error', 'warning', 'info'];
    const alertType = allowedTypes.includes(type) ? type : 'info';
    const previousAlert = target.querySelector('.custom-alert');

    if (previousAlert) {
        window.clearTimeout(previousAlert.dismissTimeout);
        previousAlert.remove();
    }

    const alert = document.createElement('div');
    alert.className = `custom-alert custom-alert--${alertType}`;
    alert.setAttribute('role', ['error', 'warning'].includes(alertType) ? 'alert' : 'status');
    alert.setAttribute('aria-atomic', 'true');

    const text = document.createElement('p');
    text.className = 'custom-alert__message';
    text.textContent = message;

    const closeButton = document.createElement('button');
    closeButton.className = 'custom-alert__close';
    closeButton.type = 'button';
    closeButton.setAttribute('aria-label', 'Cerrar notificación');
    closeButton.textContent = '×';

    alert.append(text, closeButton);
    target.replaceChildren(alert);
    target.classList.add('has-alert');

    const dismiss = () => {
        if (!alert.isConnected || alert.dataset.dismissing === 'true') return;

        alert.dataset.dismissing = 'true';
        window.clearTimeout(alert.dismissTimeout);
        alert.classList.remove('is-visible');
        if (target.querySelector('.custom-alert') === alert) {
            target.classList.remove('has-alert');
        }
        window.setTimeout(() => alert.remove(), 200);
    };

    closeButton.addEventListener('click', dismiss);
    requestAnimationFrame(() => alert.classList.add('is-visible'));

    if (Number.isFinite(duration) && duration > 0) {
        alert.dismissTimeout = window.setTimeout(dismiss, duration);
    }

    return alert;
}

/**
 * Helper: hacer peticiones fetch
 */
async function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method,
        headers: { 'Content-Type': 'application/json' },
    };
    if (data) {
        options.body = JSON.stringify(data);
    }
    const res = await fetch(url, options);
    return res.json();
}

/**
 * Helper: mostrar/ocultar loading en botón
 */
function setLoading(btn, loading) {
    if (loading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner"></span>';
        btn.disabled = true;
    } else {
        btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
        btn.disabled = false;
    }
}
