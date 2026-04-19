// Barangay Connect – Form Validation
// assets/js/form_validation.js

document.addEventListener('DOMContentLoaded', function () {

    // Validate all forms with class "validate-form"
    const forms = document.querySelectorAll('.validate-form');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            let valid = true;

            // Check required fields
            const required = form.querySelectorAll('[required]');
            required.forEach(function (field) {
                clearError(field);
                if (!field.value.trim()) {
                    showError(field, 'This field is required.');
                    valid = false;
                }
            });

            // Check password match
            const password = form.querySelector('[name="password"]');
            const confirm = form.querySelector('[name="confirm_password"]');
            if (password && confirm && confirm.value) {
                if (password.value !== confirm.value) {
                    showError(confirm, 'Passwords do not match.');
                    valid = false;
                }
            }

            // Check password length
            if (password && password.value && password.value.length < 8) {
                showError(password, 'Password must be at least 8 characters.');
                valid = false;
            }

            // Check contact number format
            const contact = form.querySelector('[name="contact"]');
            if (contact && contact.value) {
                const pattern = /^09\d{9}$/;
                if (!pattern.test(contact.value)) {
                    showError(contact, 'Contact number must be in format: 09XXXXXXXXX');
                    valid = false;
                }
            }

            // Check email format
            const email = form.querySelector('[name="email"]');
            if (email && email.value) {
                const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!pattern.test(email.value)) {
                    showError(email, 'Please enter a valid email address.');
                    valid = false;
                }
            }

            if (!valid) e.preventDefault();
        });
    });

    function showError(field, message) {
        field.style.borderColor = '#c0392b';
        let err = field.parentNode.querySelector('.field-error');
        if (!err) {
            err = document.createElement('span');
            err.className = 'field-error';
            err.style.cssText = 'color:#c0392b;font-size:0.78rem;margin-top:4px;display:block;';
            field.parentNode.appendChild(err);
        }
        err.textContent = message;
    }

    function clearError(field) {
        field.style.borderColor = '';
        const err = field.parentNode.querySelector('.field-error');
        if (err) err.remove();
    }

});