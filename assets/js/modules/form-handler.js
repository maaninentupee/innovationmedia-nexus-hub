/**
 * Form Handler module
 * 
 * @package TonysTheme
 */

// Vientimuuttujat
export const formState = {
    forms: new Map(),
    submitHandlers: new Map(),
    validationRules: new Map()
};

/**
 * Alusta lomakkeiden käsittely
 */
export function initFormHandler() {
    // Etsi kaikki lomakkeet
    document.querySelectorAll('form').forEach(form => {
        if (!formState.forms.has(form)) {
            setupForm(form);
        }
    });

    // Tarkkaile dynaamisesti lisättyjä lomakkeita
    observeDynamicForms();
}

/**
 * Aseta lomake
 */
export function setupForm(form) {
    // Lisää lomake tilaan
    formState.forms.set(form, {
        isSubmitting: false,
        validationErrors: new Map()
    });

    // Lisää validointi
    setupFormValidation(form);

    // Lisää lähetyskäsittelijä
    form.addEventListener('submit', handleFormSubmit);

    // Lisää real-time validointi
    setupRealTimeValidation(form);
}

/**
 * Aseta lomakkeen validointi
 */
export function setupFormValidation(form) {
    const inputs = form.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        // Lisää validointisäännöt input-tyypin mukaan
        const rules = getValidationRules(input);
        if (rules.length) {
            formState.validationRules.set(input, rules);
        }

        // Lisää visuaalinen palaute
        input.addEventListener('invalid', () => {
            input.classList.add('is-invalid');
        });

        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
            input.classList.remove('is-valid');
        });
    });
}

/**
 * Hae validointisäännöt
 */
export function getValidationRules(input) {
    const rules = [];
    
    // Pakolliset kentät
    if (input.required) {
        rules.push({
            validate: value => value.trim() !== '',
            message: 'Tämä kenttä on pakollinen'
        });
    }

    // Sähköposti
    if (input.type === 'email') {
        rules.push({
            validate: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
            message: 'Syötä kelvollinen sähköpostiosoite'
        });
    }

    // Puhelin
    if (input.type === 'tel') {
        rules.push({
            validate: value => /^[+]?[\d\s-]{5,}$/.test(value),
            message: 'Syötä kelvollinen puhelinnumero'
        });
    }

    // URL
    if (input.type === 'url') {
        rules.push({
            validate: value => {
                try {
                    new URL(value);
                    return true;
                } catch {
                    return false;
                }
            },
            message: 'Syötä kelvollinen URL-osoite'
        });
    }

    return rules;
}

/**
 * Aseta reaaliaikainen validointi
 */
export function setupRealTimeValidation(form) {
    const inputs = form.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        input.addEventListener('blur', () => {
            validateInput(input);
        });

        input.addEventListener('input', () => {
            // Poista virheviestit inputin muuttuessa
            const errorElement = input.parentNode.querySelector('.error-message');
            if (errorElement) {
                errorElement.remove();
            }
        });
    });
}

/**
 * Validoi input
 */
export function validateInput(input) {
    const rules = formState.validationRules.get(input);
    if (!rules) return true;

    const value = input.value;
    let isValid = true;

    // Poista vanhat virheviestit
    const oldError = input.parentNode.querySelector('.error-message');
    if (oldError) {
        oldError.remove();
    }

    // Tarkista kaikki säännöt
    for (const rule of rules) {
        if (!rule.validate(value)) {
            isValid = false;
            
            // Lisää virheilmoitus
            const errorElement = document.createElement('div');
            errorElement.classList.add('error-message');
            errorElement.textContent = rule.message;
            input.parentNode.appendChild(errorElement);
            
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            
            break;
        }
    }

    if (isValid) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    }

    return isValid;
}

/**
 * Käsittele lomakkeen lähetys
 */
export async function handleFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Tarkista onko lomake jo lähetetty
    const state = formState.forms.get(form);
    if (state.isSubmitting) return;
    
    // Validoi kaikki kentät
    const inputs = form.querySelectorAll('input, textarea, select');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateInput(input)) {
            isValid = false;
        }
    });
    
    if (!isValid) return;
    
    // Merkitse lomake lähetetyksi
    state.isSubmitting = true;
    
    try {
        // Näytä latausanimaatio
        const submitButton = form.querySelector('[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Lähetetään...';
        
        // Lähetä lomake
        const response = await fetch(form.action, {
            method: form.method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Lomakkeen lähetys epäonnistui');
        }
        
        // Näytä onnistumisviesti
        showFormMessage(form, 'Lomake lähetetty onnistuneesti!', 'success');
        
        // Tyhjennä lomake
        form.reset();
        
    } catch (error) {
        // Näytä virheviesti
        showFormMessage(form, 'Lomakkeen lähetyksessä tapahtui virhe. Yritä uudelleen.', 'error');
        
    } finally {
        // Palauta lomake normaalitilaan
        const submitButton = form.querySelector('[type="submit"]');
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        state.isSubmitting = false;
    }
}

/**
 * Näytä lomakeviesti
 */
export function showFormMessage(form, message, type = 'info') {
    // Poista vanhat viestit
    const oldMessage = form.querySelector('.form-message');
    if (oldMessage) {
        oldMessage.remove();
    }
    
    // Luo uusi viesti
    const messageElement = document.createElement('div');
    messageElement.classList.add('form-message', `form-message--${type}`);
    messageElement.textContent = message;
    
    // Lisää viesti lomakkeen alkuun
    form.insertBefore(messageElement, form.firstChild);
    
    // Poista viesti automaattisesti
    setTimeout(() => {
        messageElement.remove();
    }, 5000);
}

/**
 * Tarkkaile dynaamisesti lisättyjä lomakkeita
 */
export function observeDynamicForms() {
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) { // ELEMENT_NODE
                    // Tarkista lisätty elementti
                    if (node.tagName === 'FORM') {
                        if (!formState.forms.has(node)) {
                            setupForm(node);
                        }
                    }
                    
                    // Tarkista lisätyn elementin lapset
                    node.querySelectorAll('form').forEach(form => {
                        if (!formState.forms.has(form)) {
                            setupForm(form);
                        }
                    });
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

/**
 * Pura lomakkeiden käsittely
 */
export function destroyFormHandler() {
    formState.forms.forEach((state, form) => {
        form.removeEventListener('submit', handleFormSubmit);
    });
    
    formState.forms.clear();
    formState.submitHandlers.clear();
    formState.validationRules.clear();
}

// Alusta lomakkeiden käsittely kun DOM on valmis
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFormHandler);
} else {
    initFormHandler();
}
