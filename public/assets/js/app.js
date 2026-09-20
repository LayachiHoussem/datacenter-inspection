/**
 * Datacenter Inspection System - Core Client JS
 */

// Toast notification helper (SweetAlert / Toast style)
window.showToast = function(type = 'info', message = '', title = '', duration = 4500) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const icons = {
        success: 'fa-solid fa-circle-check',
        danger: 'fa-solid fa-circle-xmark',
        warning: 'fa-solid fa-triangle-exclamation',
        info: 'fa-solid fa-circle-info'
    };

    const defaultTitles = {
        success: 'Success',
        danger: 'Error Alert',
        warning: 'Attention',
        info: 'Information'
    };

    const iconClass = icons[type] || icons.info;
    const headerTitle = title || defaultTitles[type] || 'Notice';

    const toast = document.createElement('div');
    toast.className = `toast-popup toast-${type}`;
    toast.innerHTML = `
        <div class="toast-icon"><i class="${iconClass}"></i></div>
        <div class="toast-body">
            <div class="toast-title">${headerTitle}</div>
            <div class="toast-msg">${message}</div>
        </div>
        <button type="button" class="toast-close" aria-label="Close">&times;</button>
        <div class="toast-progress">
            <div class="toast-progress-bar" style="animation-duration: ${duration}ms;"></div>
        </div>
    `;

    container.appendChild(toast);

    const closeBtn = toast.querySelector('.toast-close');
    const dismiss = () => {
        toast.classList.add('toast-hiding');
        setTimeout(() => toast.remove(), 300);
    };

    closeBtn.addEventListener('click', dismiss);
    const timer = setTimeout(dismiss, duration);

    toast.addEventListener('mouseenter', () => clearTimeout(timer));
};

document.addEventListener('DOMContentLoaded', () => {
    // Show toast for any flash alerts rendered in DOM
    const flashAlerts = document.querySelectorAll('.alert[data-flash]');
    flashAlerts.forEach(alert => {
        const type = alert.getAttribute('data-type') || 'info';
        const message = alert.getAttribute('data-message') || alert.textContent.trim();
        window.showToast(type, message);
    });

    // Auto dismiss standard inline alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not([data-flash])');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Confirm delete modal or prompt
    const deleteForms = document.querySelectorAll('form[data-confirm]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const msg = form.getAttribute('data-confirm') || 'Are you sure you want to delete this record?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });
});

// Generic Email Send Button Spinner Feedback
window.handleEmailSend = function(form, btnId) {
    const btn = document.getElementById(btnId);
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending Email...';
    }
    return true;
};

// Inspection Photo Upload Preview Handler
window.previewInspectionPhoto = function(input, previewId, nameId) {
    const file = input.files && input.files[0];
    const preview = document.getElementById(previewId);
    const nameSpan = document.getElementById(nameId);
    const label = input.closest('.photo-upload-wrapper')?.querySelector('.btn-upload-photo');

    if (file) {
        if (nameSpan) {
            nameSpan.textContent = file.name;
            nameSpan.title = file.name;
        }
        if (label) {
            label.classList.add('has-file');
            label.innerHTML = '<i class="fa-solid fa-check"></i> <span>Photo Ready</span>';
        }
        if (preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'inline-block';
            };
            reader.readAsDataURL(file);
        }
    }
};

