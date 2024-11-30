<?php
/**
 * WordPress-yhteensopivuuden tarkistus
 *
 * @package TonysTheme
 */

class TonysTheme_Compatibility_Checker {
    /**
     * Vähimmäisvaatimukset
     */
    private static $requirements = [
        'php' => '7.4',
        'wordpress' => '5.8',
    ];

    /**
     * Alusta yhteensopivuustarkistukset
     */
    public static function init() {
        // Tarkista yhteensopivuus teeman aktivoinnin yhteydessä
        add_action('after_switch_theme', [__CLASS__, 'check_compatibility']);
        
        // Lisää yhteensopivuustiedot teeman tietoihin
        add_filter('theme_page_templates', [__CLASS__, 'add_compatibility_info']);
        
        // Lisää admin-ilmoitukset
        add_action('admin_notices', [__CLASS__, 'display_compatibility_notices']);
    }

    /**
     * Tarkista yhteensopivuus
     */
    public static function check_compatibility() {
        $issues = [];

        // Tarkista PHP-versio
        if (version_compare(PHP_VERSION, self::$requirements['php'], '<')) {
            $issues[] = sprintf(
                __('Teema vaatii PHP-version %s tai uudemman. Nykyinen versio on %s.', 'tonys-theme'),
                self::$requirements['php'],
                PHP_VERSION
            );
        }

        // Tarkista WordPress-versio
        global $wp_version;
        if (version_compare($wp_version, self::$requirements['wordpress'], '<')) {
            $issues[] = sprintf(
                __('Teema vaatii WordPress-version %s tai uudemman. Nykyinen versio on %s.', 'tonys-theme'),
                self::$requirements['wordpress'],
                $wp_version
            );
        }

        // Tallenna ongelmat välimuistiin
        update_option('tonys_theme_compatibility_issues', $issues);

        return empty($issues);
    }

    /**
     * Näytä yhteensopivuusilmoitukset
     */
    public static function display_compatibility_notices() {
        $issues = get_option('tonys_theme_compatibility_issues', []);
        
        if (!empty($issues)) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>' . esc_html__('Tonyn Mukautettu Teema - Yhteensopivuusongelmat:', 'tonys-theme') . '</strong></p>';
            echo '<ul>';
            foreach ($issues as $issue) {
                echo '<li>' . esc_html($issue) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }

    /**
     * Lisää yhteensopivuustiedot teeman tietoihin
     */
    public static function add_compatibility_info($page_templates) {
        add_theme_support('tonys-theme-compatibility', [
            'php' => self::$requirements['php'],
            'wordpress' => self::$requirements['wordpress'],
        ]);
        
        return $page_templates;
    }

    /**
     * Tarkista tietyn ominaisuuden yhteensopivuus
     */
    public static function is_feature_compatible($feature) {
        switch ($feature) {
            case 'webp':
                return function_exists('imagecreatefromwebp');
            
            case 'amp':
                return defined('AMP__VERSION');
            
            case 'lazy_loading':
                return function_exists('wp_lazy_loading_enabled');
            
            default:
                return true;
        }
    }
}

// Alusta yhteensopivuustarkistukset
TonysTheme_Compatibility_Checker::init();
