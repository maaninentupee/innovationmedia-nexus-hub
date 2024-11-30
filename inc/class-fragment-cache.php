<?php
/**
 * Fragmenttivälimuistin hallinta
 *
 * @package TonysTheme
 */

class TonysTheme_Fragment_Cache {
    /**
     * Välimuistin etuliite
     */
    const CACHE_PREFIX = 'tonys_fragment_';

    /**
     * Alusta fragmenttivälimuisti
     */
    public static function init() {
        // Käytä välimuistia vain tuotantoympäristössä
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Rekisteröi välimuistitoiminnot
            add_action('widgets_init', [__CLASS__, 'register_cached_widgets']);
            add_action('init', [__CLASS__, 'register_cached_shortcodes']);
            
            // Tyhjennä välimuisti tarvittaessa
            add_action('save_post', [__CLASS__, 'clear_related_fragments']);
            add_action('comment_post', [__CLASS__, 'clear_comment_fragments']);
            add_action('wp_update_nav_menu', [__CLASS__, 'clear_menu_fragments']);
        }
    }

    /**
     * Aloita fragmentin välimuistitus
     */
    public static function start($name, $args = [], $ttl = 3600) {
        if (self::should_skip_cache()) {
            return false;
        }

        $key = self::get_fragment_key($name, $args);
        $cached_content = get_transient($key);

        if ($cached_content !== false) {
            echo $cached_content;
            return true;
        }

        ob_start();
        return false;
    }

    /**
     * Lopeta fragmentin välimuistitus
     */
    public static function end($name, $args = [], $ttl = 3600) {
        if (self::should_skip_cache()) {
            return;
        }

        $content = ob_get_clean();
        if (!empty($content)) {
            $key = self::get_fragment_key($name, $args);
            set_transient($key, $content, $ttl);
        }
        echo $content;
    }

    /**
     * Rekisteröi välimuistitetut vimpaimet
     */
    public static function register_cached_widgets() {
        // Välimuistita suosituimmat artikkelit
        register_widget('TonysTheme_Cached_Popular_Posts');
        
        // Välimuistita viimeisimmät kommentit
        register_widget('TonysTheme_Cached_Recent_Comments');
    }

    /**
     * Rekisteröi välimuistitetut shortcodet
     */
    public static function register_cached_shortcodes() {
        // Välimuistita liittyvät artikkelit
        add_shortcode('related_posts', [__CLASS__, 'cached_related_posts']);
        
        // Välimuistita sosiaalisen median syöte
        add_shortcode('social_feed', [__CLASS__, 'cached_social_feed']);
    }

    /**
     * Välimuistitettu liittyvät artikkelit -shortcode
     */
    public static function cached_related_posts($atts) {
        $args = shortcode_atts([
            'posts_per_page' => 3,
            'category' => '',
            'tags' => ''
        ], $atts);

        if (self::start('related_posts', $args, HOUR_IN_SECONDS)) {
            return;
        }

        // Hae liittyvät artikkelit
        $query_args = [
            'post_type' => 'post',
            'posts_per_page' => $args['posts_per_page'],
            'post__not_in' => [get_the_ID()],
            'orderby' => 'rand'
        ];

        if (!empty($args['category'])) {
            $query_args['category_name'] = $args['category'];
        }

        if (!empty($args['tags'])) {
            $query_args['tag'] = $args['tags'];
        }

        $related_posts = new WP_Query($query_args);
        
        ob_start();
        if ($related_posts->have_posts()) {
            echo '<div class="related-posts">';
            while ($related_posts->have_posts()) {
                $related_posts->the_post();
                get_template_part('template-parts/content', 'related');
            }
            echo '</div>';
        }
        wp_reset_postdata();
        
        self::end('related_posts', $args, HOUR_IN_SECONDS);
    }

    /**
     * Välimuistitettu sosiaalisen median syöte -shortcode
     */
    public static function cached_social_feed($atts) {
        $args = shortcode_atts([
            'type' => 'twitter',
            'count' => 5
        ], $atts);

        if (self::start('social_feed', $args, 30 * MINUTE_IN_SECONDS)) {
            return;
        }

        // Toteuta some-syötteen haku tähän
        echo '<div class="social-feed">';
        // Syötteen sisältö
        echo '</div>';

        self::end('social_feed', $args, 30 * MINUTE_IN_SECONDS);
    }

    /**
     * Tyhjennä artikkeliin liittyvät fragmentit
     */
    public static function clear_related_fragments($post_id) {
        $post_type = get_post_type($post_id);
        
        // Tyhjennä liittyvät fragmentit
        delete_transient(self::CACHE_PREFIX . 'related_posts');
        delete_transient(self::CACHE_PREFIX . 'popular_posts');
        
        if ($post_type === 'post') {
            delete_transient(self::CACHE_PREFIX . 'recent_posts');
        }
    }

    /**
     * Tyhjennä kommentteihin liittyvät fragmentit
     */
    public static function clear_comment_fragments() {
        delete_transient(self::CACHE_PREFIX . 'recent_comments');
        delete_transient(self::CACHE_PREFIX . 'popular_comments');
    }

    /**
     * Tyhjennä valikkoon liittyvät fragmentit
     */
    public static function clear_menu_fragments() {
        $menu_locations = get_nav_menu_locations();
        foreach ($menu_locations as $location => $menu_id) {
            delete_transient(self::CACHE_PREFIX . 'menu_' . $location);
        }
    }

    /**
     * Luo fragmentin välimuistiavain
     */
    private static function get_fragment_key($name, $args = []) {
        $key_parts = [
            self::CACHE_PREFIX,
            $name
        ];

        if (!empty($args)) {
            $key_parts[] = md5(serialize($args));
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
     * Tarkista pitääkö välimuisti ohittaa
     */
    private static function should_skip_cache() {
        // Ohita välimuisti kirjautuneille käyttäjille
        if (is_user_logged_in()) {
            return true;
        }

        // Ohita välimuisti lomakkeiden lähetyksille
        if ($_POST) {
            return true;
        }

        // Ohita välimuisti esikatselussa
        if (is_preview()) {
            return true;
        }

        return false;
    }
}

/**
 * Apufunktiot fragmenttien välimuistittamiseen
 */
function tonys_cache_start($name, $args = [], $ttl = 3600) {
    return TonysTheme_Fragment_Cache::start($name, $args, $ttl);
}

function tonys_cache_end($name, $args = [], $ttl = 3600) {
    TonysTheme_Fragment_Cache::end($name, $args, $ttl);
}

// Alusta fragmenttivälimuisti
TonysTheme_Fragment_Cache::init();
