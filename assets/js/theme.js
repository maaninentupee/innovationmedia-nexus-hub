// Service Worker rekisteröinti
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/wp-content/themes/tonys-theme/assets/js/service-worker.js')
            .then(registration => {
                console.log('Service Worker rekisteröity:', registration);
            })
            .catch(error => {
                console.log('Service Worker rekisteröinti epäonnistui:', error);
            });
    });
}

/**
 * Teeman pää-JavaScript-tiedosto
 * @module theme
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const body = document.body;

    /**
     * Vaihda mobiilivalikon tila
     * @function toggleMobileMenu
     * @param {Event} event - Click-tapahtuma
     */
    function toggleMobileMenu(event) {
        event.preventDefault();
        const isOpen = mobileMenu.classList.contains('is-active');
        
        mobileMenu.classList.toggle('is-active');
        mobileMenuToggle.classList.toggle('is-active');
        body.classList.toggle('mobile-menu-open');
        
        // Update ARIA attributes
        mobileMenuToggle.setAttribute('aria-expanded', !isOpen);
        mobileMenu.setAttribute('aria-hidden', isOpen);
    }

    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenu.contains(event.target) && 
                !mobileMenuToggle.contains(event.target) && 
                mobileMenu.classList.contains('is-active')) {
                mobileMenu.classList.remove('is-active');
                mobileMenuToggle.classList.remove('is-active');
                body.classList.remove('mobile-menu-open');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
                mobileMenu.setAttribute('aria-hidden', 'true');
            }
        });
    }

    // Back to Top Button
    const backToTopButton = document.createElement('button');
    backToTopButton.className = 'back-to-top';
    backToTopButton.setAttribute('aria-label', 'Takaisin ylös');
    backToTopButton.innerHTML = '<span class="screen-reader-text">Takaisin ylös</span>';
    body.appendChild(backToTopButton);

    /**
     * Näytä/Pyhä Back to Top -painike riippuen sivun vieritysasemasta
     * @function showBackToTopButton
     */
    function showBackToTopButton() {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.add('is-visible');
        } else {
            backToTopButton.classList.remove('is-visible');
        }
    }

    // Show/Hide Back to Top button based on scroll position
    window.addEventListener('scroll', showBackToTopButton);

    /**
     * Siirry sivun ylös painikkeen klikkauksen jälkeen
     * @function scrollToTop
     * @param {Event} event - Click-tapahtuma
     */
    function scrollToTop(event) {
        event.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Smooth scroll to top when clicking the button
    backToTopButton.addEventListener('click', scrollToTop);

    // Add smooth scrolling to all internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Sub-menu accessibility for keyboard navigation
    const menuItems = document.querySelectorAll('.menu-item-has-children');
    
    menuItems.forEach(item => {
        const link = item.querySelector('a');
        const submenu = item.querySelector('.sub-menu');
        
        if (link && submenu) {
            // Add ARIA attributes
            link.setAttribute('aria-expanded', 'false');
            submenu.setAttribute('aria-hidden', 'true');
            
            /**
             * Käsittele näppäimistönavigointi
             * @function handleKeyboardNavigation
             * @param {Event} event - Keydown-tapahtuma
             */
            function handleKeyboardNavigation(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    const isExpanded = link.getAttribute('aria-expanded') === 'true';
                    
                    link.setAttribute('aria-expanded', !isExpanded);
                    submenu.setAttribute('aria-hidden', isExpanded);
                    submenu.classList.toggle('is-active');
                }
            }

            // Handle keyboard navigation
            link.addEventListener('keydown', handleKeyboardNavigation);
        }
    });

    /**
     * Teeman JavaScript-toiminnallisuudet
     */
    (function($) {
        'use strict';

        // Virheilmoitusten käsittely
        const handleFormErrors = {
            /**
             * Näytä virheilmoitus
             * @function showError
             * @param {HTMLElement} form - Lomakeelementti
             * @param {string} message - Virheilmoituksen teksti
             */
            showError: function(form, message) {
                // Poista vanhat virheilmoitukset
                form.find('.form-error').remove();
                
                // Lisää uusi virheilmoitus
                const errorDiv = $('<div>')
                    .addClass('form-error')
                    .text(message)
                    .hide();
                
                form.prepend(errorDiv);
                errorDiv.slideDown();
                
                // Poista virheilmoitus automaattisesti 5 sekunnin kuluttua
                setTimeout(() => {
                    errorDiv.slideUp(() => errorDiv.remove());
                }, 5000);
            },
            
            /**
             * Tyhjennä virheilmoitukset
             * @function clearErrors
             * @param {HTMLElement} form - Lomakeelementti
             */
            clearErrors: function(form) {
                form.find('.form-error').slideUp(() => $(this).remove());
            }
        };

        // Lomakkeiden validointi
        const validateForms = {
            /**
             * Alusta lomakkeiden validointi
             * @function init
             */
            init: function() {
                $('form').on('submit', this.handleSubmit);
            },
            
            /**
             * Käsittele lomakkeen lähetys
             * @function handleSubmit
             * @param {Event} event - Submit-tapahtuma
             */
            handleSubmit: function(e) {
                const $form = $(this);
                let hasErrors = false;
                
                // Tyhjennä vanhat virheet
                handleFormErrors.clearErrors($form);
                
                // Tarkista pakolliset kentät
                $form.find('[required]').each(function() {
                    const $field = $(this);
                    if (!$field.val().trim()) {
                        hasErrors = true;
                        $field.addClass('error');
                        handleFormErrors.showError($form, 'Täytä kaikki pakolliset kentät.');
                    }
                });
                
                // Tarkista sähköpostiosoitteet
                $form.find('input[type="email"]').each(function() {
                    const $field = $(this);
                    const email = $field.val().trim();
                    if (email && !validateForms.isValidEmail(email)) {
                        hasErrors = true;
                        $field.addClass('error');
                        handleFormErrors.showError($form, 'Tarkista sähköpostiosoite.');
                    }
                });
                
                // Pysäytä lähetys jos virheitä löytyy
                if (hasErrors) {
                    e.preventDefault();
                }
            },
            
            /**
             * Tarkista sähköpostiosoitteen oikeellisuus
             * @function isValidEmail
             * @param {string} email - Sähköpostiosoite
             * @returns {boolean} Onko sähköpostiosoite oikeellinen
             */
            isValidEmail: function(email) {
                const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(email.toLowerCase());
            }
        };

        // AJAX-lomakkeiden käsittely
        const ajaxForms = {
            /**
             * Alusta AJAX-lomakkeiden käsittely
             * @function init
             */
            init: function() {
                $('.ajax-form').on('submit', this.handleSubmit);
            },
            
            /**
             * Käsittele AJAX-lomakkeen lähetys
             * @function handleSubmit
             * @param {Event} event - Submit-tapahtuma
             */
            handleSubmit: function(e) {
                e.preventDefault();
                const $form = $(this);
                
                // Näytä latausanimaatio
                $form.addClass('loading');
                
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            // Näytä onnistumisilmoitus
                            handleFormErrors.showError($form, response.message || 'Lomake lähetetty onnistuneesti!');
                            $form[0].reset();
                        } else {
                            // Näytä virheilmoitus
                            handleFormErrors.showError($form, response.message || 'Lomakkeen lähetys epäonnistui.');
                        }
                    },
                    error: function() {
                        handleFormErrors.showError($form, 'Palvelinvirhe. Yritä myöhemmin uudelleen.');
                    },
                    complete: function() {
                        $form.removeClass('loading');
                    }
                });
            }
        };

        // Alusta kaikki toiminnallisuudet kun DOM on valmis
        $(document).ready(function() {
            validateForms.init();
            ajaxForms.init();
        });

    })(jQuery);
});
