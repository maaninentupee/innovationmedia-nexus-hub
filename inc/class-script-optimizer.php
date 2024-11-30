<?php
/**
 * JavaScript-tiedostojen optimointi
 *
 * @package TonysTheme
 */

class TonysTheme_Script_Optimizer {
    /**
     * Alusta optimoinnit
     */
    public static function init() {
        // Lisää async/defer attribuutit skripteille
        add_filter('script_loader_tag', [__CLASS__, 'add_async_defer'], 10, 3);
        
        // Optimoi jQuery lataus
        add_action('wp_default_scripts', [__CLASS__, 'optimize_jquery']);
        
        // Siirrä skriptit footeriin
        add_action('wp_enqueue_scripts', [__CLASS__, 'move_scripts_to_footer']);
    }

    /**
     * Lisää async/defer attribuutit skripteille
     */
    public static function add_async_defer($tag, $handle, $src) {
        // Lista skripteistä, jotka voidaan ladata asynkronisesti
        $async_scripts = [
            'google-analytics',
            'tonys-theme-lazy-loading',
            'tonys-theme-social-sharing'
        ];

        // Lista skripteistä, jotka voidaan ladata deferillä
        $defer_scripts = [
            'comment-reply',
            'tonys-theme-navigation'
        ];

        if (in_array($handle, $async_scripts)) {
            return str_replace(' src', ' async src', $tag);
        }

        if (in_array($handle, $defer_scripts)) {
            return str_replace(' src', ' defer src', $tag);
        }

        return $tag;
    }

    /**
     * Optimoi jQuery lataus
     */
    public static function optimize_jquery($scripts) {
        if (!is_admin() && !empty($scripts->registered['jquery'])) {
            $scripts->registered['jquery']->deps = array_diff(
                $scripts->registered['jquery']->deps,
                ['jquery-migrate']
            );
        }
    }

    /**
     * Siirrä skriptit footeriin
     */
    public static function move_scripts_to_footer() {
        // Poista jQuery wp_head():sta
        wp_deregister_script('jquery');
        
        // Rekisteröi jQuery uudelleen footeriin
        wp_register_script('jquery', includes_url('/js/jquery/jquery.min.js'), [], null, true);
        
        // Lataa jQuery takaisin jos sitä tarvitaan
        if (!is_admin()) {
            wp_enqueue_script('jquery');
        }
    }
}

// Alusta skriptien optimointi
TonysTheme_Script_Optimizer::init();
