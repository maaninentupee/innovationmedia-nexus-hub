/**
 * Lazy Loading module
 * 
 * @package TonysTheme
 */

// Vientimuuttujat
export const lazyLoadingState = {
    observer: null,
    loadedImages: new Set()
};

/**
 * Alusta lazy loading
 */
export function initLazyLoading() {
    // Tarkista Intersection Observer -tuki
    if ('IntersectionObserver' in window) {
        setupIntersectionObserver();
    } else {
        loadAllImages();
    }

    // Lisää tuki dynaamisesti lisätyille kuville
    observeDynamicImages();
}

/**
 * Aseta Intersection Observer
 */
export function setupIntersectionObserver() {
    const options = {
        root: null, // viewport
        rootMargin: '50px 0px', // lataa kuvat hieman ennen kuin ne tulevat näkyviin
        threshold: 0.1 // lataa kun 10% kuvasta on näkyvissä
    };

    lazyLoadingState.observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadImage(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, options);

    // Tarkkaile kaikkia lazy-load kuvia
    document.querySelectorAll('img[loading="lazy"]').forEach(img => {
        if (!lazyLoadingState.loadedImages.has(img)) {
            lazyLoadingState.observer.observe(img);
        }
    });
}

/**
 * Lataa kuva
 */
export function loadImage(img) {
    if (lazyLoadingState.loadedImages.has(img)) return;

    // Lataa alkuperäinen kuva
    if (img.dataset.src) {
        img.src = img.dataset.src;
        delete img.dataset.src;
    }

    // Lataa srcset jos määritelty
    if (img.dataset.srcset) {
        img.srcset = img.dataset.srcset;
        delete img.dataset.srcset;
    }

    // Lataa sizes jos määritelty
    if (img.dataset.sizes) {
        img.sizes = img.dataset.sizes;
        delete img.dataset.sizes;
    }

    // Merkitse kuva ladatuksi
    lazyLoadingState.loadedImages.add(img);

    // Lisää animaatio kun kuva on ladattu
    img.addEventListener('load', () => {
        img.classList.add('is-loaded');
    });
}

/**
 * Lataa kaikki kuvat (fallback)
 */
export function loadAllImages() {
    document.querySelectorAll('img[loading="lazy"]').forEach(img => {
        loadImage(img);
    });
}

/**
 * Tarkkaile dynaamisesti lisättyjä kuvia
 */
export function observeDynamicImages() {
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) { // ELEMENT_NODE
                    // Tarkista lisätty elementti
                    if (node.tagName === 'IMG' && node.getAttribute('loading') === 'lazy') {
                        if (!lazyLoadingState.loadedImages.has(node)) {
                            lazyLoadingState.observer.observe(node);
                        }
                    }
                    
                    // Tarkista lisätyn elementin lapset
                    node.querySelectorAll('img[loading="lazy"]').forEach(img => {
                        if (!lazyLoadingState.loadedImages.has(img)) {
                            lazyLoadingState.observer.observe(img);
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
 * Pura lazy loading
 */
export function destroyLazyLoading() {
    if (lazyLoadingState.observer) {
        lazyLoadingState.observer.disconnect();
        lazyLoadingState.observer = null;
    }
    lazyLoadingState.loadedImages.clear();
}

// Alusta lazy loading kun DOM on valmis
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLazyLoading);
} else {
    initLazyLoading();
}
