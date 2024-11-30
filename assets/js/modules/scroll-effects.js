/**
 * Scroll Effects module
 * 
 * @package TonysTheme
 */

// Vientimuuttujat
export const scrollState = {
    lastScrollTop: 0,
    ticking: false,
    header: null,
    scrollUpClass: 'scroll-up',
    scrollDownClass: 'scroll-down',
    pinnedClass: 'is-pinned',
    unpinnedClass: 'is-unpinned'
};

/**
 * Alusta vieritysefektit
 */
export function initScrollEffects() {
    scrollState.header = document.querySelector('.site-header');
    
    if (!scrollState.header) return;
    
    // Alusta header-tila
    updateHeaderState();
    
    // Lisää scroll-kuuntelija
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // Lisää smooth scroll ankkurilinkeille
    setupSmoothScroll();
    
    // Alusta "Takaisin ylös" -painike
    initBackToTop();
}

/**
 * Käsittele vieritys
 */
export function handleScroll() {
    if (!scrollState.ticking) {
        window.requestAnimationFrame(() => {
            updateHeaderState();
            scrollState.ticking = false;
        });
        scrollState.ticking = true;
    }
}

/**
 * Päivitä headerin tila
 */
export function updateHeaderState() {
    const currentScroll = window.pageYOffset;
    const scrollDelta = currentScroll - scrollState.lastScrollTop;
    
    // Älä tee mitään jos vieritetään ylös sivun yläreunassa
    if (currentScroll <= 0) {
        scrollState.header.classList.remove(scrollState.unpinnedClass);
        scrollState.header.classList.add(scrollState.pinnedClass);
        return;
    }
    
    // Määritä vierityksen suunta
    if (scrollDelta > 0) {
        // Vieritetään alas
        scrollState.header.classList.remove(scrollState.scrollUpClass);
        scrollState.header.classList.add(scrollState.scrollDownClass);
        
        // Piilota header kun vieritetään alas
        if (currentScroll > 100) {
            scrollState.header.classList.remove(scrollState.pinnedClass);
            scrollState.header.classList.add(scrollState.unpinnedClass);
        }
    } else {
        // Vieritetään ylös
        scrollState.header.classList.remove(scrollState.scrollDownClass);
        scrollState.header.classList.add(scrollState.scrollUpClass);
        scrollState.header.classList.remove(scrollState.unpinnedClass);
        scrollState.header.classList.add(scrollState.pinnedClass);
    }
    
    scrollState.lastScrollTop = currentScroll;
}

/**
 * Aseta smooth scroll ankkurilinkeille
 */
export function setupSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                smoothScrollTo(targetElement);
            }
        });
    });
}

/**
 * Vieritä sulavasti kohteeseen
 */
export function smoothScrollTo(target) {
    const headerOffset = scrollState.header ? scrollState.header.offsetHeight : 0;
    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset;
    const offsetPosition = targetPosition - headerOffset;

    window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
    });
}

/**
 * Alusta "Takaisin ylös" -painike
 */
export function initBackToTop() {
    const backToTop = document.createElement('button');
    backToTop.classList.add('back-to-top');
    backToTop.setAttribute('aria-label', 'Takaisin ylös');
    backToTop.innerHTML = '↑';
    
    document.body.appendChild(backToTop);
    
    // Näytä/piilota painike vierityksen mukaan
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('is-visible');
        } else {
            backToTop.classList.remove('is-visible');
        }
    }, { passive: true });
    
    // Vieritä ylös klikkauksella
    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

/**
 * Pura vieritysefektit
 */
export function destroyScrollEffects() {
    window.removeEventListener('scroll', handleScroll);
    const backToTop = document.querySelector('.back-to-top');
    if (backToTop) {
        backToTop.remove();
    }
}

// Alusta vieritysefektit kun DOM on valmis
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScrollEffects);
} else {
    initScrollEffects();
}
