<?php
/**
 * Object Cache -hallinta
 *
 * @package TonysTheme
 */

class TonysTheme_Object_Cache {
    /**
     * Välimuistin etuliite
     */
    const CACHE_PREFIX = 'tonys_obj_';

    /**
     * Välimuistin oletusaika (1 tunti)
     */
    const DEFAULT_EXPIRATION = 3600;

    /**
     * Alusta object cache
     */
    public static function init() {
        // Käytä object cachea vain tuotantoympäristössä
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Lisää välimuisti kyselyille
            add_action('pre_get_posts', [__CLASS__, 'maybe_cache_query']);
            
            // Tyhjennä välimuisti kun tarvitaan
            add_action('save_post', [__CLASS__, 'clear_post_object_cache']);
            add_action('deleted_post', [__CLASS__, 'clear_post_object_cache']);
            add_action('switch_theme', [__CLASS__, 'clear_all_object_cache']);
            add_action('wp_update_nav_menu', [__CLASS__, 'clear_menu_object_cache']);
            
            // Lisää diagnostiikka admin-paneeliin
            if (is_admin()) {
                add_action('admin_init', [__CLASS__, 'check_object_cache_status']);
                add_action('admin_notices', [__CLASS__, 'show_cache_status']);
            }
        }
    }

    /**
     * Tarkista object cache -tila
     */
    public static function check_object_cache_status() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $status = [
            'enabled' => wp_using_ext_object_cache(),
            'type' => self::get_object_cache_type(),
            'working' => self::test_object_cache()
        ];

        update_option('tonys_object_cache_status', $status);
    }

    /**
     * Näytä välimuistin tila admin-paneelissa
     */
    public static function show_cache_status() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $status = get_option('tonys_object_cache_status', []);
        
        if (empty($status)) {
            return;
        }

        $class = $status['working'] ? 'notice-success' : 'notice-warning';
        $message = $status['enabled'] 
            ? sprintf('Object Cache käytössä (%s)', esc_html($status['type']))
            : 'Object Cache ei ole käytössä. Suorituskyky paranisi Redis- tai Memcached-välimuistilla.';

        printf(
            '<div class="notice %s"><p>%s</p></div>',
            esc_attr($class),
            esc_html($message)
        );
    }

    /**
     * Välimuistita WP_Query-kysely jos mahdollista
     */
    public static function maybe_cache_query($query) {
        if (is_admin() || !$query->is_main_query() || is_user_logged_in()) {
            return;
        }

        // Luo yksilöllinen avain tälle kyselylle
        $key = self::get_query_cache_key($query);
        
        // Yritä hakea tulokset välimuistista
        $cached_results = wp_cache_get($key, self::CACHE_PREFIX);
        
        if ($cached_results !== false) {
            $query->posts = $cached_results['posts'];
            $query->post_count = count($cached_results['posts']);
            $query->found_posts = $cached_results['found_posts'];
            $query->max_num_pages = $cached_results['max_num_pages'];
            
            // Pysäytä kysely
            $query->is_cached = true;
        } else {
            // Lisää suodatin tulosten tallentamiseksi
            add_filter('posts_results', function($posts, $q) use ($key) {
                if ($q->is_main_query() && !$q->is_cached) {
                    $cache_data = [
                        'posts' => $posts,
                        'found_posts' => $q->found_posts,
                        'max_num_pages' => $q->max_num_pages
                    ];
                    
                    wp_cache_set($key, $cache_data, self::CACHE_PREFIX, self::DEFAULT_EXPIRATION);
                }
                return $posts;
            }, 10, 2);
        }
    }

    /**
     * Tyhjennä artikkelin object cache
     */
    public static function clear_post_object_cache($post_id) {
        $post_type = get_post_type($post_id);
        
        // Tyhjennä artikkeliin liittyvät välimuistit
        wp_cache_delete(self::CACHE_PREFIX . 'post_' . $post_id);
        wp_cache_delete(self::CACHE_PREFIX . 'type_' . $post_type);
        
        // Tyhjennä etusivun välimuisti jos tarpeen
        if (self::is_front_page_content($post_id, $post_type)) {
            wp_cache_delete(self::CACHE_PREFIX . 'front_page');
        }

        // Tyhjennä arkistosivujen välimuisti
        wp_cache_delete(self::CACHE_PREFIX . 'archive_' . $post_type);
    }

    /**
     * Tyhjennä valikon object cache
     */
    public static function clear_menu_object_cache() {
        $menu_locations = get_nav_menu_locations();
        foreach ($menu_locations as $location => $menu_id) {
            wp_cache_delete(self::CACHE_PREFIX . 'menu_' . $location);
        }
    }

    /**
     * Tyhjennä kaikki object cache
     */
    public static function clear_all_object_cache() {
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Testaa object cache toimivuus
     */
    private static function test_object_cache() {
        $test_key = self::CACHE_PREFIX . 'test';
        $test_data = 'test_' . time();
        
        wp_cache_set($test_key, $test_data, self::CACHE_PREFIX, 60);
        $retrieved = wp_cache_get($test_key, self::CACHE_PREFIX);
        
        return $test_data === $retrieved;
    }

    /**
     * Hae käytössä olevan object cache -järjestelmän tyyppi
     */
    private static function get_object_cache_type() {
        global $wp_object_cache;
        
        if (!wp_using_ext_object_cache()) {
            return 'none';
        }

        $class_name = get_class($wp_object_cache);
        
        if (strpos($class_name, 'Redis') !== false) {
            return 'Redis';
        } elseif (strpos($class_name, 'Memcached') !== false) {
            return 'Memcached';
        }
        
        return 'unknown';
    }

    /**
     * Luo välimuistiavain WP_Query-kyselylle
     */
    private static function get_query_cache_key($query) {
        $key_parts = [
            self::CACHE_PREFIX,
            'query'
        ];

        // Lisää kyselyparametrit avaimeen
        if ($query->is_single()) {
            $key_parts[] = 'single_' . $query->get_queried_object_id();
        } elseif ($query->is_page()) {
            $key_parts[] = 'page_' . $query->get_queried_object_id();
        } elseif ($query->is_archive()) {
            $key_parts[] = 'archive';
            $key_parts[] = $query->get('post_type', 'post');
            $key_parts[] = $query->get('paged', 1);
        }

        // Lisää järjestysparametrit
        if ($query->get('orderby')) {
            $key_parts[] = 'orderby_' . $query->get('orderby');
            $key_parts[] = 'order_' . $query->get('order', 'DESC');
        }

        return implode('_', array_filter($key_parts));
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

// Alusta object cache
TonysTheme_Object_Cache::init();
