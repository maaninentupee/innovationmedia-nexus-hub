jQuery(document).ready(function($) {
    var $menuToggle = $('.mobile-menu-toggle');
    var $mobileMenu = $('.mobile-menu');
    var $body = $('body');

    // Toggle mobile menu
    $menuToggle.on('click touchstart', function(e) {
        e.preventDefault();
        $mobileMenu.toggleClass('is-active');
        $body.toggleClass('mobile-menu-open');
        $menuToggle.attr('aria-expanded', $mobileMenu.hasClass('is-active'));
    });

    // Close menu when clicking outside
    $(document).on('click', function(e) {
        if (!$mobileMenu.is(e.target) && 
            $mobileMenu.has(e.target).length === 0 && 
            !$menuToggle.is(e.target) && 
            $mobileMenu.hasClass('is-active')) {
            $mobileMenu.removeClass('is-active');
            $body.removeClass('mobile-menu-open');
            $menuToggle.attr('aria-expanded', 'false');
        }
    });

    // Add touch event handling
    let touchStartX = 0;
    let touchEndX = 0;
    
    document.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
    }, false);
    
    document.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, false);
    
    function handleSwipe() {
        const swipeThreshold = 100;
        if (touchEndX + swipeThreshold < touchStartX && $mobileMenu.hasClass('is-active')) {
            // Swipe left - close menu
            $mobileMenu.removeClass('is-active');
            $body.removeClass('mobile-menu-open');
            $menuToggle.attr('aria-expanded', 'false');
        }
    }
});
