<?php
/**
 * Palvelinpuolen välimuistin hallinta
 *
 * @package TonysTheme
 */

class TonysTheme_Server_Cache {
    /**
     * Välimuistin etuliite
     */
    const CACHE_PREFIX = 'tonys_cache_';

    /**
     * Alusta palvelinpuolen välimuisti
     */
    public static function init() {
        // Käytä välimuistia vain tuotantoympäristössä
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Lisää välimuisti template-parteille
            add_filter('pre_get_template_part', [__CLASS__, 'cache_template_part'], 10, 3);
            
            // Tyhjennä välimuisti kun sisältöä päivitetään
            add_action('save_post', [__CLASS__, 'clear_post_cache']);
            add_action('deleted_post', [__CLASS__, 'clear_post_cache']);
            add_action('switch_theme', [__CLASS__, 'clear_all_cache']);
            add_action('wp_update_nav_menu', [__CLASS__, 'clear_menu_cache']);
            add_action('update_option_sidebars_widgets', [__CLASS__, 'clear_widget_cache']);
            
            // Lisää hallintapaneelin toiminnot
            add_action('admin_bar_menu', [__CLASS__, 'add_admin_bar_menu'], 100);
            add_action('admin_post_clear_theme_cache', [__CLASS__, 'handle_cache_clear']);
        }
    }

    /**
     * Välimuistita template-part
     */
    public static function cache_template_part($null, $slug, $name) {
        // Älä käytä välimuistia kirjautuneille käyttäjille
        if (is_user_logged_in()) {
            return null;
        }

        // Luo yksilöllinen avain tälle template-partille
        $cache_key = self::get_template_cache_key($slug, $name);
        
        // Yritä hakea sisältö välimuistista
        $output = get_transient($cache_key);
        
        if ($output === false) {
            // Jos ei välimuistissa, palauta null jotta WordPress lataa normaalisti
            return null;
        }
        
        return $output;
    }

    /**
     * Tallenna template-part välimuistiin
     */
    public static function cache_rendered_template($content, $slug, $name) {
        if (empty($content) || is_user_logged_in()) {
            return $content;
        }

        $cache_key = self::get_template_cache_key($slug, $name);
        set_transient($cache_key, $content, HOUR_IN_SECONDS);
        
        return $content;
    }

    /**
     * Tyhjennä artikkelin välimuisti
     */
    public static function clear_post_cache($post_id) {
        $post_type = get_post_type($post_id);
        
        // Tyhjennä artikkeliin liittyvät välimuistit
        delete_transient(self::CACHE_PREFIX . 'post_' . $post_id);
        delete_transient(self::CACHE_PREFIX . 'type_' . $post_type);
        
        // Tyhjennä etusivun välimuisti jos kyseessä on etusivulla näytettävä sisältö
        if (self::is_front_page_content($post_id, $post_type)) {
            delete_transient(self::CACHE_PREFIX . 'front_page');
        }
    }

    /**
     * Tyhjennä valikon välimuisti
     */
    public static function clear_menu_cache() {
        $menu_locations = get_nav_menu_locations();
        foreach ($menu_locations as $location => $menu_id) {
            delete_transient(self::CACHE_PREFIX . 'menu_' . $location);
        }
    }

    /**
     * Tyhjennä vimpaimen välimuisti
     */
    public static function clear_widget_cache() {
        global $wp_registered_sidebars;
        foreach ($wp_registered_sidebars as $sidebar => $data) {
            delete_transient(self::CACHE_PREFIX . 'sidebar_' . $sidebar);
        }
    }

    /**
     * Tyhjennä kaikki teeman välimuistit
     */
    public static function clear_all_cache() {
        global $wpdb;
        
        // Hae kaikki teeman välimuistiavaimet
        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . self::CACHE_PREFIX . '%',
            '_transient_timeout_' . self::CACHE_PREFIX . '%'
        );
        
        $wpdb->query($sql);
    }

    /**
     * Lisää välimuistin tyhjennys admin-palkkiin
     */
    public static function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $args = [
            'id'    => 'clear-theme-cache',
            'title' => 'Tyhjennä teeman välimuisti',
            'href'  => wp_nonce_url(admin_url('admin-post.php?action=clear_theme_cache'), 'clear_theme_cache'),
            'meta'  => ['class' => 'clear-theme-cache']
        ];
        
        $wp_admin_bar->add_node($args);
    }

    /**
     * Käsittele välimuistin tyhjennys admin-paneelista
     */
    public static function handle_cache_clear() {
        if (!current_user_can('manage_options')) {
            wp_die('Ei käyttöoikeutta.');
        }

        check_admin_referer('clear_theme_cache');
        
        self::clear_all_cache();
        
        // Ohjaa takaisin edelliselle sivulle
        wp_safe_redirect(wp_get_referer());
        exit;
    }

    /**
     * Luo välimuistiavain template-partille
     */
    private static function get_template_cache_key($slug, $name = null) {
        $key_parts = [
            self::CACHE_PREFIX,
            'template',
            $slug
        ];

        if ($name) {
            $key_parts[] = $name;
        }

        // Lisää sivukohtaiset parametrit
        if (is_singular()) {
            $key_parts[] = 'post_' . get_the_ID();
        } elseif (is_archive()) {
            $key_parts[] = 'archive_' . get_query_var('post_type');
            $key_parts[] = get_query_var('paged', 1);
        }

        return implode('_', array_filter($key_parts));
    }

    /**
     * Tarkista onko sisältö etusivulla
     */
    private static function is_front_page_content($post_id, $post_type) {
        // Tarkista onko staattinen etusivu
        if (get_option('page_on_front') == $post_id) {
            return true;
        }

        // Tarkista onko artikkelisivu etusivuna
        if (get_option('show_on_front') == 'posts' && $post_type == 'post') {
            return true;
        }

        return false;
    }
}

// Alusta palvelinpuolen välimuisti
TonysTheme_Server_Cache::init();
