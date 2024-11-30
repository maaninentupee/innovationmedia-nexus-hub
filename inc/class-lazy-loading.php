<?php
/**
 * Lazy Loading -optimoinnit
 *
 * @package TonysTheme
 */

class TonysTheme_Lazy_Loading {
    /**
     * Alusta lazy loading
     */
    public static function init() {
        // Lisää lazy loading -attribuutti kuviin
        add_filter('wp_get_attachment_image_attributes', [__CLASS__, 'add_lazy_loading_attribute'], 10, 3);
        
        // Lisää lazy loading sisällön kuviin
        add_filter('the_content', [__CLASS__, 'add_lazy_loading_to_content']);
        
        // Lisää tarvittavat skriptit
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

    /**
     * Lisää lazy loading -attribuutti kuviin
     */
    public static function add_lazy_loading_attribute($attr, $attachment, $size) {
        // Älä lisää lazy loading -attribuuttia jos kuva on jo lazy loaded
        if (isset($attr['loading'])) {
            return $attr;
        }

        // Lisää loading="lazy" attribuutti
        $attr['loading'] = 'lazy';
        
        return $attr;
    }

    /**
     * Lisää lazy loading sisällön kuviin
     */
    public static function add_lazy_loading_to_content($content) {
        if (!is_array($content)) {
            $content = (string) $content;
        }

        // Etsi kaikki img-tagit, jotka eivät jo sisällä loading-attribuuttia
        return preg_replace_callback(
            '/<img([^>]+?)(?!\sloading=[\'"])(.*?)>/i',
            function($matches) {
                return '<img' . $matches[1] . ' loading="lazy"' . $matches[2] . '>';
            },
            $content
        );
    }

    /**
     * Lisää tarvittavat skriptit
     */
    public static function enqueue_scripts() {
        // Rekisteröi ja lataa Intersection Observer -polyfill vanhemmille selaimille
        wp_register_script(
            'intersection-observer',
            'https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver',
            [],
            null,
            true
        );

        // Lataa vain jos selain tarvitsee polyfill:iä
        wp_add_inline_script(
            'intersection-observer',
            'if(!("IntersectionObserver" in window)) {
                var script = document.createElement("script");
                script.src = "' . esc_url(wp_scripts()->registered['intersection-observer']->src) . '";
                document.head.appendChild(script);
            }'
        );

        // Rekisteröi ja lataa lazy loading -seuranta
        wp_enqueue_script(
            'lazy-loading-monitor',
            get_template_directory_uri() . '/assets/js/lazy-loading-monitor.js',
            ['intersection-observer'],
            filemtime(get_template_directory() . '/assets/js/lazy-loading-monitor.js'),
            true
        );
    }
}

// Alusta lazy loading
TonysTheme_Lazy_Loading::init();
