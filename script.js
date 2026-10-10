try {
    const savedTheme = localStorage.getItem('baryocare-theme');
    if (savedTheme === 'dark' || savedTheme === 'light') {
        document.documentElement.dataset.theme = savedTheme;
    }
} catch {
}

document.addEventListener('DOMContentLoaded', () => {
    const themeButtons = document.querySelectorAll('[data-theme-toggle]');
    const syncTheme = () => {
        const isDark = document.documentElement.dataset.theme === 'dark';
        themeButtons.forEach((button) => {
            const label = isDark ? 'Switch to light mode' : 'Switch to dark mode';
            button.setAttribute('aria-label', label);
            button.title = label;
            const icon = button.querySelector('i');
            if (icon) icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
            const text = button.querySelector('span');
            if (text) text.textContent = isDark ? 'Light mode' : 'Dark mode';
        });
    };
    syncTheme();
    themeButtons.forEach((button) => button.addEventListener('click', () => {
        const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = theme;
        try {
            localStorage.setItem('baryocare-theme', theme);
        } catch {
        }
        syncTheme();
    }));

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            const icon = button.querySelector('i');
            if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    const registerForm = document.querySelector('[data-register-form]');
    if (registerForm) {
        const password = registerForm.querySelector('#password');
        const confirm = registerForm.querySelector('#confirm_password');
        const strength = registerForm.querySelector('#password-strength');
        const feedback = registerForm.querySelector('#confirm-feedback');
        const updateStrength = () => {
            const value = password.value;
            const score = value.length === 0 ? 0 : [
                value.length >= 8, /[A-Z]/.test(value), /\d/.test(value), /[^A-Za-z0-9]/.test(value),
            ].filter(Boolean).length;
            strength.dataset.strength = String(score);
            strength.querySelector('.strength-copy').textContent = value.length === 0
                ? 'At least 8 characters'
                : score <= 1 ? 'Needs more strength' : score === 2 ? 'Getting stronger' : score === 3 ? 'Good strength' : 'Strong password';
            if (confirm.value !== '') updateConfirmation();
        };
        const updateConfirmation = () => {
            const mismatch = confirm.value !== '' && confirm.value !== password.value;
            confirm.setCustomValidity(mismatch ? 'Passwords do not match.' : '');
            feedback.textContent = confirm.value === '' ? '' : mismatch ? 'Passwords do not match.' : 'Passwords match.';
            feedback.classList.toggle('is-invalid', mismatch);
            feedback.classList.toggle('is-valid', confirm.value !== '' && !mismatch);
        };
        password.addEventListener('input', updateStrength);
        confirm.addEventListener('input', updateConfirmation);
        updateStrength();
    }

    document.querySelectorAll('[data-file-upload]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const feedback = document.querySelector('[data-file-feedback]');
            if (!feedback) return;
            if (!file) {
                feedback.textContent = 'One image can help us understand the situation.';
                return;
            }
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowed.includes(file.type) || file.size > 2 * 1024 * 1024) {
                input.setCustomValidity(file.size > 2 * 1024 * 1024
                    ? 'Images must be 2 MB or smaller.'
                    : 'Choose a JPG, PNG, or WEBP image.');
                feedback.textContent = input.validationMessage;
                feedback.classList.add('field-error');
            } else {
                input.setCustomValidity('');
                feedback.textContent = `${file.name} · ${(file.size / 1024).toFixed(0)} KB`;
                feedback.classList.remove('field-error');
            }
        });
    });

    const menuButton = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.getElementById('app-sidebar');
    const closeSidebar = () => {
        document.body.classList.remove('sidebar-open');
        menuButton?.setAttribute('aria-expanded', 'false');
        menuButton?.setAttribute('aria-label', 'Open navigation');
    };
    menuButton?.addEventListener('click', () => {
        const open = document.body.classList.toggle('sidebar-open');
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        if (open) sidebar?.querySelector('a')?.focus();
    });
    document.querySelector('[data-sidebar-close]')?.addEventListener('click', closeSidebar);
    sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeSidebar));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSidebar();
    });

    const confirmModal = document.getElementById('confirmDeleteModal');
    let pendingDeleteForm = null;
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmApproved === 'true') {
                return;
            }
            event.preventDefault();
            if (!confirmModal || !window.bootstrap?.Modal) {
                if (window.confirm('Delete this submission? This cannot be undone.')) {
                    form.dataset.confirmApproved = 'true';
                    form.requestSubmit();
                }
                return;
            }
            pendingDeleteForm = form;
            window.bootstrap.Modal.getOrCreateInstance(confirmModal).show();
        });
    });
    document.querySelector('[data-confirm-delete]')?.addEventListener('click', () => {
        if (!pendingDeleteForm) return;
        const form = pendingDeleteForm;
        pendingDeleteForm = null;
        form.dataset.confirmApproved = 'true';
        window.bootstrap.Modal.getOrCreateInstance(confirmModal).hide();
        form.requestSubmit();
    });

    const modalTarget = document.querySelector(`[data-section-id="${CSS.escape(location.hash.slice(1))}"]`);
    if (location.hash && modalTarget && window.bootstrap?.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modalTarget).show();
    }

    document.querySelectorAll('[data-loading-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented || !form.checkValidity()) return;
            if (form.hasAttribute('data-confirm') && form.dataset.confirmApproved !== 'true') return;
            const button = form.querySelector('button[type="submit"]');
            if (!button || button.disabled) return;
            button.disabled = true;
            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');
            const label = button.querySelector('span') || button;
            if (label === button) {
                label.dataset.originalLabel = button.textContent.trim();
                button.textContent = 'Please wait…';
            } else {
                label.textContent = 'Please wait…';
            }
            const spinner = document.createElement('span');
            spinner.className = 'button-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            button.prepend(spinner);
        });
    });

    const toast = document.querySelector('[data-toast]');
    if (toast && window.bootstrap?.Toast) {
        window.bootstrap.Toast.getOrCreateInstance(toast, { delay: 4500 }).show();
    }

    const chartColors = {
        grid: getComputedStyle(document.documentElement).getPropertyValue('--line').trim(),
        text: getComputedStyle(document.documentElement).getPropertyValue('--muted').trim(),
    };
    const categoryCanvas = document.getElementById('categoryChart');
    const categoryData = document.getElementById('category-chart-data');
    if (categoryCanvas && categoryData && window.Chart) {
        const data = JSON.parse(categoryData.textContent);
        new window.Chart(categoryCanvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: '#3565c5',
                    hoverBackgroundColor: '#244da4',
                    borderRadius: 6,
                    maxBarThickness: 32,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { displayColors: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: chartColors.text, font: { family: 'Inter', size: 8 }, maxRotation: 35 } },
                    y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1, color: chartColors.text, font: { family: 'Inter', size: 8 } }, grid: { color: chartColors.grid } },
                },
            },
        });
    }
    const statusCanvas = document.getElementById('statusChart');
    const statusData = document.getElementById('status-chart-data');
    if (statusCanvas && statusData && window.Chart) {
        const data = JSON.parse(statusData.textContent);
        new window.Chart(statusCanvas, {
            type: 'doughnut',
            data: { labels: data.labels, datasets: [{ data: data.values, backgroundColor: data.colors, borderWidth: 0, hoverOffset: 4 }] },
            options: {
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false }, tooltip: { displayColors: false } },
            },
        });
    }
});
