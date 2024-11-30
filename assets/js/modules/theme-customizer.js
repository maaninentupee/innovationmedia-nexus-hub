/**
 * Theme Customizer module
 * 
 * @package TonysTheme
 */

// Vientimuuttujat
export const customizerState = {
    settings: new Map(),
    callbacks: new Map()
};

/**
 * Alusta teeman muokkain
 */
export function initThemeCustomizer() {
    // Alusta oletusasetukset
    setupDefaultSettings();
    
    // Lataa tallennetut asetukset
    loadSavedSettings();
    
    // Lisää muokkainpaneeli
    setupCustomizerPanel();
}

/**
 * Aseta oletusasetukset
 */
export function setupDefaultSettings() {
    // Värit
    customizerState.settings.set('primary-color', '#0073aa');
    customizerState.settings.set('secondary-color', '#23282d');
    customizerState.settings.set('text-color', '#333333');
    customizerState.settings.set('background-color', '#ffffff');
    
    // Typografia
    customizerState.settings.set('font-family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif');
    customizerState.settings.set('font-size-base', '16px');
    customizerState.settings.set('line-height', '1.5');
    
    // Layout
    customizerState.settings.set('content-width', '1200px');
    customizerState.settings.set('sidebar-width', '300px');
    customizerState.settings.set('grid-gap', '20px');
}

/**
 * Lataa tallennetut asetukset
 */
export function loadSavedSettings() {
    const savedSettings = localStorage.getItem('theme-customizer-settings');
    if (savedSettings) {
        try {
            const settings = JSON.parse(savedSettings);
            Object.entries(settings).forEach(([key, value]) => {
                customizerState.settings.set(key, value);
            });
        } catch (error) {
            console.error('Virhe asetusten lataamisessa:', error);
        }
    }
}

/**
 * Aseta muokkainpaneeli
 */
export function setupCustomizerPanel() {
    const panel = document.createElement('div');
    panel.classList.add('theme-customizer-panel');
    panel.innerHTML = `
        <button class="theme-customizer-toggle" aria-label="Avaa teeman muokkain">
            <span class="dashicons dashicons-admin-customizer"></span>
        </button>
        <div class="theme-customizer-content">
            <h2>Teeman asetukset</h2>
            <form class="theme-customizer-form">
                <div class="customizer-section">
                    <h3>Värit</h3>
                    <div class="customizer-control">
                        <label for="primary-color">Pääväri</label>
                        <input type="color" id="primary-color" name="primary-color" value="${customizerState.settings.get('primary-color')}">
                    </div>
                    <div class="customizer-control">
                        <label for="secondary-color">Toissijainen väri</label>
                        <input type="color" id="secondary-color" name="secondary-color" value="${customizerState.settings.get('secondary-color')}">
                    </div>
                    <div class="customizer-control">
                        <label for="text-color">Tekstin väri</label>
                        <input type="color" id="text-color" name="text-color" value="${customizerState.settings.get('text-color')}">
                    </div>
                    <div class="customizer-control">
                        <label for="background-color">Taustaväri</label>
                        <input type="color" id="background-color" name="background-color" value="${customizerState.settings.get('background-color')}">
                    </div>
                </div>
                
                <div class="customizer-section">
                    <h3>Typografia</h3>
                    <div class="customizer-control">
                        <label for="font-family">Fontti</label>
                        <select id="font-family" name="font-family">
                            <option value="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif">Järjestelmän fontti</option>
                            <option value="'Open Sans', sans-serif">Open Sans</option>
                            <option value="'Roboto', sans-serif">Roboto</option>
                            <option value="'Lato', sans-serif">Lato</option>
                        </select>
                    </div>
                    <div class="customizer-control">
                        <label for="font-size-base">Tekstin koko</label>
                        <input type="range" id="font-size-base" name="font-size-base" min="12" max="24" step="1" value="${parseInt(customizerState.settings.get('font-size-base'))}">
                        <output for="font-size-base">${customizerState.settings.get('font-size-base')}</output>
                    </div>
                    <div class="customizer-control">
                        <label for="line-height">Riviväli</label>
                        <input type="range" id="line-height" name="line-height" min="1" max="2" step="0.1" value="${customizerState.settings.get('line-height')}">
                        <output for="line-height">${customizerState.settings.get('line-height')}</output>
                    </div>
                </div>
                
                <div class="customizer-section">
                    <h3>Layout</h3>
                    <div class="customizer-control">
                        <label for="content-width">Sisällön leveys</label>
                        <input type="range" id="content-width" name="content-width" min="800" max="1600" step="100" value="${parseInt(customizerState.settings.get('content-width'))}">
                        <output for="content-width">${customizerState.settings.get('content-width')}</output>
                    </div>
                    <div class="customizer-control">
                        <label for="sidebar-width">Sivupalkin leveys</label>
                        <input type="range" id="sidebar-width" name="sidebar-width" min="200" max="400" step="50" value="${parseInt(customizerState.settings.get('sidebar-width'))}">
                        <output for="sidebar-width">${customizerState.settings.get('sidebar-width')}</output>
                    </div>
                    <div class="customizer-control">
                        <label for="grid-gap">Ruudukon väli</label>
                        <input type="range" id="grid-gap" name="grid-gap" min="10" max="40" step="5" value="${parseInt(customizerState.settings.get('grid-gap'))}">
                        <output for="grid-gap">${customizerState.settings.get('grid-gap')}</output>
                    </div>
                </div>
                
                <div class="customizer-actions">
                    <button type="submit" class="button button-primary">Tallenna muutokset</button>
                    <button type="button" class="button button-secondary reset-settings">Palauta oletukset</button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(panel);
    
    // Lisää tapahtumankäsittelijät
    setupCustomizerEvents(panel);
}

/**
 * Aseta muokkaimen tapahtumat
 */
export function setupCustomizerEvents(panel) {
    const toggle = panel.querySelector('.theme-customizer-toggle');
    const form = panel.querySelector('.theme-customizer-form');
    const resetButton = panel.querySelector('.reset-settings');
    
    // Avaa/sulje paneeli
    toggle.addEventListener('click', () => {
        panel.classList.toggle('is-open');
    });
    
    // Käsittele lomakkeen lähetys
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        saveSettings(form);
    });
    
    // Palauta oletusasetukset
    resetButton.addEventListener('click', () => {
        if (confirm('Haluatko varmasti palauttaa oletusasetukset?')) {
            setupDefaultSettings();
            updateCustomizerUI(form);
            applySettings();
            saveSettingsToStorage();
        }
    });
    
    // Päivitä output-elementit range-inputeille
    form.querySelectorAll('input[type="range"]').forEach(input => {
        const output = form.querySelector(`output[for="${input.id}"]`);
        if (output) {
            input.addEventListener('input', () => {
                output.textContent = input.value + (input.id.includes('width') ? 'px' : '');
            });
        }
    });
    
    // Päivitä teema reaaliajassa
    form.querySelectorAll('input, select').forEach(input => {
        input.addEventListener('change', () => {
            updateSetting(input.name, input.value);
            applySettings();
        });
    });
}

/**
 * Tallenna asetukset
 */
export function saveSettings(form) {
    const formData = new FormData(form);
    
    formData.forEach((value, key) => {
        updateSetting(key, value);
    });
    
    applySettings();
    saveSettingsToStorage();
    
    // Näytä ilmoitus
    showCustomizerMessage('Asetukset tallennettu!');
}

/**
 * Päivitä asetus
 */
export function updateSetting(key, value) {
    customizerState.settings.set(key, value);
    
    // Kutsu mahdollisia callback-funktioita
    const callbacks = customizerState.callbacks.get(key);
    if (callbacks) {
        callbacks.forEach(callback => callback(value));
    }
}

/**
 * Sovella asetukset
 */
export function applySettings() {
    const root = document.documentElement;
    
    // Päivitä CSS-muuttujat
    customizerState.settings.forEach((value, key) => {
        root.style.setProperty(`--${key}`, value);
    });
}

/**
 * Tallenna asetukset local storageen
 */
export function saveSettingsToStorage() {
    const settings = {};
    customizerState.settings.forEach((value, key) => {
        settings[key] = value;
    });
    
    localStorage.setItem('theme-customizer-settings', JSON.stringify(settings));
}

/**
 * Päivitä muokkaimen UI
 */
export function updateCustomizerUI(form) {
    customizerState.settings.forEach((value, key) => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = value;
            
            // Päivitä output-elementit
            if (input.type === 'range') {
                const output = form.querySelector(`output[for="${input.id}"]`);
                if (output) {
                    output.textContent = value + (input.id.includes('width') ? 'px' : '');
                }
            }
        }
    });
}

/**
 * Näytä muokkaimen viesti
 */
export function showCustomizerMessage(message, type = 'success') {
    const messageElement = document.createElement('div');
    messageElement.classList.add('customizer-message', `customizer-message--${type}`);
    messageElement.textContent = message;
    
    document.querySelector('.theme-customizer-panel').appendChild(messageElement);
    
    // Poista viesti automaattisesti
    setTimeout(() => {
        messageElement.remove();
    }, 3000);
}

/**
 * Lisää callback-funktio asetuksen muutoksille
 */
export function onSettingChange(key, callback) {
    if (!customizerState.callbacks.has(key)) {
        customizerState.callbacks.set(key, new Set());
    }
    
    customizerState.callbacks.get(key).add(callback);
}

/**
 * Poista callback-funktio
 */
export function offSettingChange(key, callback) {
    if (customizerState.callbacks.has(key)) {
        customizerState.callbacks.get(key).delete(callback);
    }
}

/**
 * Pura muokkain
 */
export function destroyThemeCustomizer() {
    const panel = document.querySelector('.theme-customizer-panel');
    if (panel) {
        panel.remove();
    }
    
    customizerState.settings.clear();
    customizerState.callbacks.clear();
}

// Alusta teeman muokkain kun DOM on valmis
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeCustomizer);
} else {
    initThemeCustomizer();
}
