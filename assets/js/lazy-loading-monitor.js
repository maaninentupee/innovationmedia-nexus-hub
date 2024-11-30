/**
 * Lazy Loading -seuranta
 */
(function() {
    // Seuraa lazy-loaded kuvien latautumista
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    let loadedImages = 0;

    // Luo Intersection Observer
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                
                // Seuraa kuvan latautumista
                img.addEventListener('load', () => {
                    loadedImages++;
                    console.log(`Lazy loaded image ${loadedImages}/${lazyImages.length}`);
                    
                    // Lähetä tieto WordPressin debug-lokiin
                    if (window.console && window.console.debug) {
                        console.debug('Lazy loading metrics:', {
                            loaded: loadedImages,
                            total: lazyImages.length,
                            url: img.src
                        });
                    }
                });

                // Lopeta tämän kuvan tarkkailu
                imageObserver.unobserve(img);
            }
        });
    });

    // Aloita kuvien tarkkailu
    lazyImages.forEach(img => {
        imageObserver.observe(img);
    });
})();
