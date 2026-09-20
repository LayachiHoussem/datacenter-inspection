/**
 * Datacenter Inspection Checklist Handler
 */

document.addEventListener('DOMContentLoaded', () => {
    // Quick Pass/Fail status radio highlights
    const resultButtons = document.querySelectorAll('.result-btn-group input[type="radio"]');
    resultButtons.forEach(radio => {
        radio.addEventListener('change', (e) => {
            const container = e.target.closest('.checklist-item-card');
            if (!container) return;

            container.classList.remove('status-pass', 'status-fail', 'status-warning');
            const val = e.target.value;
            if (val === 'pass') container.classList.add('status-pass');
            if (val === 'fail') container.classList.add('status-fail');
            if (val === 'warning') container.classList.add('status-warning');
        });
    });

    // Image Upload Preview
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photo-preview');

    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    photoPreview.src = event.target.result;
                    photoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
