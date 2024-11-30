/**
 * Navigation module
 * 
 * @package TonysTheme
 */

// Vientimuuttujat
export const navigationState = {
    isOpen: false,
    currentSubmenu: null
};

/**
 * Alusta navigaatio
 */
export function initNavigation() {
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-navigation');
    const subMenuToggles = document.querySelectorAll('.menu-item-has-children > a');

    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', toggleMenu);
    }

    subMenuToggles.forEach(toggle => {
        toggle.addEventListener('click', handleSubmenuToggle);
    });

    // Lisää näppäimistönavigointi
    document.addEventListener('keydown', handleKeyboardNavigation);
}

/**
 * Vaihda päävalikon tila
 */
export function toggleMenu(event) {
    if (event) {
        event.preventDefault();
    }

    const mainNav = document.querySelector('.main-navigation');
    const menuToggle = document.querySelector('.menu-toggle');
    
    navigationState.isOpen = !navigationState.isOpen;
    
    mainNav.classList.toggle('is-active');
    menuToggle.setAttribute('aria-expanded', navigationState.isOpen);
    
    // Sulje kaikki auki olevat alavalikot
    if (!navigationState.isOpen) {
        closeAllSubmenus();
    }
}

/**
 * Käsittele alavalikon avaus/sulkeminen
 */
export function handleSubmenuToggle(event) {
    event.preventDefault();
    
    const menuItem = event.target.parentNode;
    const submenu = menuItem.querySelector('.sub-menu');
    
    if (!submenu) return;
    
    const isOpen = submenu.classList.contains('is-active');
    
    // Sulje muut auki olevat alavalikot
    if (navigationState.currentSubmenu && navigationState.currentSubmenu !== submenu) {
        navigationState.currentSubmenu.classList.remove('is-active');
        navigationState.currentSubmenu.parentNode.querySelector('a').setAttribute('aria-expanded', 'false');
    }
    
    // Vaihda tämän alavalikon tila
    submenu.classList.toggle('is-active');
    event.target.setAttribute('aria-expanded', !isOpen);
    
    navigationState.currentSubmenu = isOpen ? null : submenu;
}

/**
 * Sulje kaikki alavalikot
 */
export function closeAllSubmenus() {
    const submenus = document.querySelectorAll('.sub-menu.is-active');
    submenus.forEach(submenu => {
        submenu.classList.remove('is-active');
        submenu.parentNode.querySelector('a').setAttribute('aria-expanded', 'false');
    });
    navigationState.currentSubmenu = null;
}

/**
 * Käsittele näppäimistönavigointi
 */
export function handleKeyboardNavigation(event) {
    // Escape sulkee valikon
    if (event.key === 'Escape') {
        if (navigationState.isOpen) {
            toggleMenu();
        } else if (navigationState.currentSubmenu) {
            closeAllSubmenus();
        }
    }
    
    // Tab-näppäin sulkee valikon jos käyttäjä navigoi pois valikosta
    if (event.key === 'Tab') {
        const mainNav = document.querySelector('.main-navigation');
        if (!mainNav.contains(document.activeElement)) {
            if (navigationState.isOpen) {
                toggleMenu();
            }
        }
    }
}

// Alusta navigaatio kun DOM on valmis
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavigation);
} else {
    initNavigation();
}
