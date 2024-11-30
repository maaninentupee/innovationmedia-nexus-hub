/**
 * Edistynyt kuvien lazy loading
 */
document.addEventListener('DOMContentLoaded', function() {
    // Luodaan Intersection Observer kuvien tarkkailuun
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                loadImage(img);
                observer.unobserve(img);
            }
        });
    }, {
        // Lataa kuvat kun ne ovat 50px päässä näkyvästä alueesta
        rootMargin: '50px'
    });

    // Etsi kaikki lazy loading -kuvat
    document.querySelectorAll('img[loading="lazy"]').forEach(img => {
        // Lisää blur-up efekti jos käytössä
        if (img.dataset.blurSrc) {
            const tempImage = new Image();
            tempImage.src = img.dataset.blurSrc;
            tempImage.onload = function() {
                img.style.backgroundImage = `url(${img.dataset.blurSrc})`;
                img.style.backgroundSize = 'cover';
                img.style.filter = 'blur(5px)';
            };
        }

        // Aloita kuvan tarkkailu
        imageObserver.observe(img);
    });

    // Lataa kuva kun se tulee näkyviin
    function loadImage(img) {
        if (img.dataset.src) {
            const fullImage = new Image();
            fullImage.src = img.dataset.src;
            fullImage.onload = function() {
                img.src = img.dataset.src;
                img.style.filter = 'none';
                img.style.transition = 'filter 0.3s ease-out';
            };
        }
    }
});
