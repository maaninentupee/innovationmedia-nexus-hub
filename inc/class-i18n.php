<?php
/**
 * Kansainvälistämisen hallinta
 *
 * @package TonysTheme
 */

class TonysTheme_I18n {
    /**
     * Alusta kansainvälistäminen
     */
    public static function init() {
        add_action('after_setup_theme', [__CLASS__, 'load_theme_textdomain']);
        add_filter('locale', [__CLASS__, 'set_locale']);
        add_action('init', [__CLASS__, 'register_date_strings']);
    }

    /**
     * Lataa teeman tekstiverkkotunnus
     */
    public static function load_theme_textdomain() {
        load_theme_textdomain(
            'tonys-theme',
            get_template_directory() . '/languages'
        );
    }

    /**
     * Aseta locale käyttäjän valinnan mukaan
     */
    public static function set_locale($locale) {
        $user_locale = get_user_meta(get_current_user_id(), 'locale', true);
        if ($user_locale) {
            return $user_locale;
        }
        return $locale;
    }

    /**
     * Rekisteröi päivämäärien käännökset
     */
    public static function register_date_strings() {
        // Kuukaudet
        __('Tammikuu', 'tonys-theme');
        __('Helmikuu', 'tonys-theme');
        __('Maaliskuu', 'tonys-theme');
        __('Huhtikuu', 'tonys-theme');
        __('Toukokuu', 'tonys-theme');
        __('Kesäkuu', 'tonys-theme');
        __('Heinäkuu', 'tonys-theme');
        __('Elokuu', 'tonys-theme');
        __('Syyskuu', 'tonys-theme');
        __('Lokakuu', 'tonys-theme');
        __('Marraskuu', 'tonys-theme');
        __('Joulukuu', 'tonys-theme');

        // Viikonpäivät
        __('Maanantai', 'tonys-theme');
        __('Tiistai', 'tonys-theme');
        __('Keskiviikko', 'tonys-theme');
        __('Torstai', 'tonys-theme');
        __('Perjantai', 'tonys-theme');
        __('Lauantai', 'tonys-theme');
        __('Sunnuntai', 'tonys-theme');

        // Yleiset päivämääräformaatit
        __('j.n.Y', 'tonys-theme');
        __('H:i', 'tonys-theme');
    }

    /**
     * Muotoile päivämäärä käännettynä
     */
    public static function format_date($timestamp, $format = '') {
        if (empty($format)) {
            $format = __('j.n.Y', 'tonys-theme');
        }
        return date_i18n($format, $timestamp);
    }

    /**
     * Muotoile aika käännettynä
     */
    public static function format_time($timestamp, $format = '') {
        if (empty($format)) {
            $format = __('H:i', 'tonys-theme');
        }
        return date_i18n($format, $timestamp);
    }
}

// Alusta kansainvälistäminen
TonysTheme_I18n::init();
