<?php
/**
 * REST API -välimuistin hallinta
 *
 * @package TonysTheme
 */

class TonysTheme_REST_Cache {
    /**
     * Välimuistin etuliite
     */
    const CACHE_PREFIX = 'tonys_rest_';

    /**
     * Alusta REST API -välimuisti
     */
    public static function init() {
        // Käytä välimuistia vain tuotantoympäristössä
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Lisää välimuisti REST API -vastauksiin
            add_filter('rest_pre_dispatch', [__CLASS__, 'check_rest_cache'], 10, 3);
            add_filter('rest_post_dispatch', [__CLASS__, 'cache_rest_response'], 10, 3);
            
            // Tyhjennä välimuisti kun sisältöä päivitetään
            add_action('save_post', [__CLASS__, 'clear_post_rest_cache']);
            add_action('deleted_post', [__CLASS__, 'clear_post_rest_cache']);
            add_action('comment_post', [__CLASS__, 'clear_comments_rest_cache']);
            
            // Lisää hallintapaneelin toiminnot
            add_action('admin_bar_menu', [__CLASS__, 'add_admin_bar_menu'], 100);
            add_action('admin_post_clear_rest_cache', [__CLASS__, 'handle_cache_clear']);
        }
    }

    /**
     * Tarkista onko vastaus välimuistissa
     */
    public static function check_rest_cache($result, $server, $request) {
        if (self::should_skip_cache($request)) {
            return $result;
        }

        $cache_key = self::get_cache_key($request);
        $cached_response = get_transient($cache_key);

        if ($cached_response !== false) {
            return $cached_response;
        }

        return $result;
    }

    /**
     * Tallenna REST API -vastaus välimuistiin
     */
    public static function cache_rest_response($response, $handler, $request) {
        if (self::should_skip_cache($request)) {
            return $response;
        }

        // Älä välimuistita virheellisiä vastauksia
        if ($response->is_error()) {
            return $response;
        }

        $cache_key = self::get_cache_key($request);
        $ttl = self::get_cache_ttl($request);

        set_transient($cache_key, $response, $ttl);

        return $response;
    }

    /**
     * Tyhjennä artikkelin REST-välimuisti
     */
    public static function clear_post_rest_cache($post_id) {
        $post_type = get_post_type($post_id);
        
        // Tyhjennä yksittäisen artikkelin välimuisti
        delete_transient(self::CACHE_PREFIX . 'post_' . $post_id);
        
        // Tyhjennä artikkelityypin listat
        delete_transient(self::CACHE_PREFIX . 'type_' . $post_type);
        
        // Tyhjennä etusivun välimuisti jos tarpeen
        if (self::is_front_page_content($post_id, $post_type)) {
            delete_transient(self::CACHE_PREFIX . 'front_page');
        }

        // Tyhjennä hakemistovälimuisti
        delete_transient(self::CACHE_PREFIX . 'archive_' . $post_type);
    }

    /**
     * Tyhjennä kommenttien REST-välimuisti
     */
    public static function clear_comments_rest_cache() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . 'comments_%'
            )
        );
    }

    /**
     * Lisää välimuistin tyhjennys admin-palkkiin
     */
    public static function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $args = [
            'id'    => 'clear-rest-cache',
            'title' => 'Tyhjennä REST-välimuisti',
            'href'  => wp_nonce_url(admin_url('admin-post.php?action=clear_rest_cache'), 'clear_rest_cache'),
            'meta'  => ['class' => 'clear-rest-cache']
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

        check_admin_referer('clear_rest_cache');
        
        global $wpdb;
        
        // Tyhjennä kaikki REST-välimuistit
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%',
                '_transient_timeout_' . self::CACHE_PREFIX . '%'
            )
        );
        
        // Ohjaa takaisin edelliselle sivulle
        wp_safe_redirect(wp_get_referer());
        exit;
    }

    /**
     * Tarkista pitääkö välimuisti ohittaa
     */
    private static function should_skip_cache($request) {
        // Ohita kirjoitusoperaatiot (POST, PUT, DELETE)
        if (!$request->is_method('GET')) {
            return true;
        }

        // Ohita kirjautuneiden käyttäjien pyynnöt
        if (is_user_logged_in()) {
            return true;
        }

        // Ohita tietyt endpointit
        $skip_endpoints = [
            '/wp/v2/users/me',
            '/wp/v2/settings',
        ];

        $current_route = $request->get_route();
        if (in_array($current_route, $skip_endpoints)) {
            return true;
        }

        return false;
    }

    /**
     * Luo välimuistiavain REST-pyynnölle
     */
    private static function get_cache_key($request) {
        $key_parts = [
            self::CACHE_PREFIX,
            $request->get_method(),
            $request->get_route()
        ];

        // Lisää query-parametrit avaimeen
        $params = $request->get_params();
        if (!empty($params)) {
            ksort($params); // Järjestä parametrit
            $key_parts[] = md5(serialize($params));
        }

        return implode('_', array_filter($key_parts));
    }

    /**
     * Määritä välimuistin kesto pyyntötyypin mukaan
     */
    private static function get_cache_ttl($request) {
        $route = $request->get_route();
        
        // Pidempi välimuistiaika staattiselle sisällölle
        if (strpos($route, '/wp/v2/pages') === 0) {
            return DAY_IN_SECONDS;
        }
        
        // Keskipitkä välimuistiaika artikkeleille
        if (strpos($route, '/wp/v2/posts') === 0) {
            return 6 * HOUR_IN_SECONDS;
        }
        
        // Lyhyt välimuistiaika kommenteille
        if (strpos($route, '/wp/v2/comments') === 0) {
            return HOUR_IN_SECONDS;
        }
        
        // Oletusaika muille pyynnöille
        return 2 * HOUR_IN_SECONDS;
    }

    /**
     * Tarkista onko sisältö etusivulla
     */
    private static function is_front_page_content($post_id, $post_type) {
        if (get_option('page_on_front') == $post_id) {
            return true;
        }

        if (get_option('show_on_front') == 'posts' && $post_type == 'post') {
            return true;
        }

        return false;
    }
}

// Alusta REST API -välimuisti
TonysTheme_REST_Cache::init();
