<?php
/**
 * Offline-sivu
 *
 * @package TonysTheme
 */

get_header();
?>

<div class="container offline-page">
    <div class="offline-content">
        <h1><?php esc_html_e('Ei verkkoyhteyttä', 'tonys-theme'); ?></h1>
        <p><?php esc_html_e('Näyttää siltä että olet offline-tilassa. Tarkista verkkoyhteytesi ja yritä uudelleen.', 'tonys-theme'); ?></p>
        <button onclick="window.location.reload()" class="reload-button">
            <?php esc_html_e('Päivitä sivu', 'tonys-theme'); ?>
        </button>
    </div>
</div>

<?php
get_footer();
