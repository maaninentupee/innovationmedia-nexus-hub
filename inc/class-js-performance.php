<?php
/**
 * JavaScript-suorituskyvyn optimointi
 *
 * @package TonysTheme
 */

class TonysTheme_JS_Performance {
    /**
     * Alusta optimoinnit
     */
    public static function init() {
        // Lisää async/defer -attribuutit skripteille
        add_filter('script_loader_tag', [__CLASS__, 'add_async_defer'], 10, 3);
        
        // Optimoi jQuery
        add_action('wp_default_scripts', [__CLASS__, 'optimize_jquery']);
        
        // Optimoi skriptien lataus
        add_filter('script_loader_tag', [__CLASS__, 'optimize_script_loading'], 10, 2);
        
        // Optimoi tyylitiedostojen lataus
        add_filter('style_loader_tag', [__CLASS__, 'optimize_style_loading'], 10, 2);
        
        // Lisää Resource Hints
        add_filter('wp_resource_hints', [__CLASS__, 'add_resource_hints'], 10, 2);
        
        // Lisää moduulituki skripteille
        add_filter('script_loader_tag', [__CLASS__, 'add_module_support'], 10, 2);
    }

    /**
     * Lisää async/defer -attribuutit skripteille
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

        // Lisää async tai defer -attribuutti
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
        if (!is_admin()) {
            // Poista jQuery Migrate tuotantoympäristössä
            if (!empty($scripts->registered['jquery'])) {
                $scripts->registered['jquery']->deps = array_diff(
                    $scripts->registered['jquery']->deps,
                    ['jquery-migrate']
                );
            }
        }
    }

    /**
     * Optimoi skriptien lataus
     *
     * @param string $tag Script-tagi
     * @param string $handle Skriptin kahva
     * @return string Optimoitu script-tagi
     */
    public static function optimize_script_loading($tag, $handle) {
        // Älä muokkaa admin-skriptejä
        if (is_admin()) {
            return $tag;
        }

        // Lista kriittisistä skripteistä
        $critical_scripts = array(
            'jquery',
            'jquery-core',
            'jquery-migrate'
        );

        // Lista asynkronisista skripteistä
        $async_scripts = array(
            'comment-reply',
            'wp-embed',
            'wp-mediaelement'
        );

        // Lista viivästetyistä skripteistä
        $defer_scripts = array(
            'tonys-theme-main',
            'tonys-theme-extra'
        );

        // Lisää async kriittisille skripteille
        if (in_array($handle, $critical_scripts)) {
            return str_replace(' src', ' async src', $tag);
        }

        // Lisää defer ei-kriittisille skripteille
        if (in_array($handle, $defer_scripts)) {
            return str_replace(' src', ' defer src', $tag);
        }

        // Lisää async muille skripteille
        if (in_array($handle, $async_scripts)) {
            return str_replace(' src', ' async src', $tag);
        }

        return $tag;
    }

    /**
     * Optimoi tyylitiedostojen lataus
     *
     * @param string $tag Style-tagi
     * @param string $handle Tyylin kahva
     * @return string Optimoitu style-tagi
     */
    public static function optimize_style_loading($tag, $handle) {
        // Älä muokkaa admin-tyylejä
        if (is_admin()) {
            return $tag;
        }

        // Lista kriittisistä tyyleistä
        $critical_styles = array(
            'tonys-theme-critical',
            'tonys-theme-header'
        );

        // Lisää preload kriittisille tyyleille
        if (in_array($handle, $critical_styles)) {
            return str_replace("rel='stylesheet'",
                "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"",
                $tag);
        }

        return $tag;
    }

    /**
     * Lisää Resource Hints
     *
     * @param array $hints Resource hints
     * @param string $relation_type Suhteen tyyppi
     * @return array Muokatut hints
     */
    public static function add_resource_hints($hints, $relation_type) {
        switch ($relation_type) {
            case 'dns-prefetch':
                $hints[] = '//fonts.googleapis.com';
                $hints[] = '//ajax.googleapis.com';
                break;
                
            case 'preconnect':
                $hints[] = 'https://fonts.gstatic.com';
                break;
                
            case 'preload':
                // Preload kriittiset fontit
                $hints[] = array(
                    'href' => get_theme_file_uri('assets/fonts/critical-font.woff2'),
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous'
                );
                break;
        }
        
        return $hints;
    }

    /**
     * Lisää moduulituki skripteille
     *
     * @param string $tag Script-tagi
     * @param string $handle Skriptin kahva
     * @return string Muokattu script-tagi
     */
    public static function add_module_support($tag, $handle) {
        // Lista moduuliskripteistä
        $module_scripts = array(
            'tonys-theme-module',
            'tonys-theme-components'
        );

        if (in_array($handle, $module_scripts)) {
            return str_replace(' src', ' type="module" src', $tag);
        }

        return $tag;
    }
}

// Alusta suorituskyvyn optimointi
TonysTheme_JS_Performance::init();
