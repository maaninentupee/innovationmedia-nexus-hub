/**
 * Lomakkeiden validointi
 */
class TonysThemeFormValidation {
    constructor() {
        this.forms = document.querySelectorAll('form');
        this.init();
    }

    init() {
        this.forms.forEach(form => {
            form.addEventListener('submit', this.handleSubmit.bind(this));
            form.querySelectorAll('input, textarea').forEach(field => {
                field.addEventListener('input', this.validateField.bind(this));
                field.addEventListener('blur', this.validateField.bind(this));
            });
        });
    }

    validateField(event) {
        const field = event.target;
        const value = field.value;
        let isValid = true;
        let errorMessage = '';

        // Tarkista kentän tyyppi ja validoi sen mukaan
        switch (field.type) {
            case 'email':
                isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                errorMessage = wp_form_vars.messages.invalid_email;
                break;
            case 'tel':
                isValid = /^[+]?[\d\s-]{8,}$/.test(value);
                errorMessage = wp_form_vars.messages.invalid_phone;
                break;
            case 'url':
                isValid = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w .-]*)*\/?$/.test(value);
                errorMessage = wp_form_vars.messages.invalid_url;
                break;
            default:
                if (field.required && !value.trim()) {
                    isValid = false;
                    errorMessage = wp_form_vars.messages.required_field;
                }
        }

        this.showFieldValidation(field, isValid, errorMessage);
        return isValid;
    }

    showFieldValidation(field, isValid, message) {
        const errorElement = this.getErrorElement(field);
        
        if (!isValid) {
            field.classList.add('invalid');
            field.classList.remove('valid');
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        } else {
            field.classList.add('valid');
            field.classList.remove('invalid');
            errorElement.style.display = 'none';
        }
    }

    getErrorElement(field) {
        let errorElement = field.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('form-error')) {
            errorElement = document.createElement('div');
            errorElement.classList.add('form-error');
            field.parentNode.insertBefore(errorElement, field.nextSibling);
        }
        return errorElement;
    }

    handleSubmit(event) {
        event.preventDefault();
        const form = event.target;
        let isValid = true;

        // Validoi kaikki kentät
        form.querySelectorAll('input, textarea').forEach(field => {
            if (!this.validateField({ target: field })) {
                isValid = false;
            }
        });

        if (isValid) {
            // Näytä latausanimaatio
            this.showLoading(form);
            
            // Lähetä lomake
            const formData = new FormData(form);
            fetch(form.action, {
                method: form.method,
                body: formData,
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                this.hideLoading(form);
                if (data.success) {
                    this.showSuccess(form, data.message);
                } else {
                    this.showError(form, data.message);
                }
            })
            .catch(error => {
                this.hideLoading(form);
                this.showError(form, wp_form_vars.messages.server_error);
            });
        }
    }

    showLoading(form) {
        const loader = document.createElement('div');
        loader.classList.add('form-loader');
        loader.innerHTML = `
            <div class="loader-spinner"></div>
            <p>${wp_form_vars.messages.sending}</p>
        `;
        form.appendChild(loader);
        form.classList.add('loading');
    }

    hideLoading(form) {
        const loader = form.querySelector('.form-loader');
        if (loader) {
            loader.remove();
        }
        form.classList.remove('loading');
    }

    showSuccess(form, message) {
        const successMessage = document.createElement('div');
        successMessage.classList.add('form-success');
        successMessage.textContent = message;
        form.insertBefore(successMessage, form.firstChild);
        form.reset();
        setTimeout(() => successMessage.remove(), 5000);
    }

    showError(form, message) {
        const errorMessage = document.createElement('div');
        errorMessage.classList.add('form-error', 'form-global-error');
        errorMessage.textContent = message;
        form.insertBefore(errorMessage, form.firstChild);
        setTimeout(() => errorMessage.remove(), 5000);
    }
}

// Alusta lomakkeiden validointi kun DOM on valmis
document.addEventListener('DOMContentLoaded', () => {
    new TonysThemeFormValidation();
});
