<?php
/**
 * Edistynyt kuvien lazy loading -toiminto
 *
 * @package TonysTheme
 */

class TonysTheme_Advanced_Lazy_Loading {
    /**
     * Alusta lazy loading
     */
    public static function init() {
        // Lisää tuki lazy loadingille
        add_filter('wp_get_attachment_image_attributes', [__CLASS__, 'add_lazyload_attributes'], 10, 3);
        
        // Lisää JavaScript-tuki
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

    /**
     * Lisää lazy loading -attribuutit kuviin
     */
    public static function add_lazyload_attributes($attr, $attachment, $size) {
        // Älä lisää lazy loading -attribuutteja jos kuva on jo merkitty eager-lataukseen
        if (isset($attr['loading']) && $attr['loading'] === 'eager') {
            return $attr;
        }

        // Lisää loading="lazy" attribuutti
        $attr['loading'] = 'lazy';

        // Lisää placeholder-kuva
        $attr['data-src'] = $attr['src'];
        $attr['src'] = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 ' . $attachment->width . ' ' . $attachment->height . '\'%3E%3C/svg%3E';

        // Lisää blur-up efekti jos kuva on tarpeeksi suuri
        if ($attachment->width > 100 && $attachment->height > 100) {
            $tiny_image = wp_get_attachment_image_src($attachment->ID, 'thumbnail');
            if ($tiny_image) {
                $attr['data-blur-src'] = $tiny_image[0];
            }
        }

        return $attr;
    }

    /**
     * Lisää tarvittavat JavaScript-tiedostot
     */
    public static function enqueue_scripts() {
        wp_enqueue_script(
            'tonys-theme-lazy-loading',
            get_theme_file_uri('/assets/js/lazy-loading.js'),
            [],
            wp_get_theme()->get('Version'),
            true
        );
    }
}

// Alusta luokka
TonysTheme_Advanced_Lazy_Loading::init();
