<?php
/**
 * Hakutoiminnon parannukset
 *
 * @package TonysTheme
 */

class TonysTheme_Search_Enhancer {
    /**
     * Alusta hakuparannukset
     */
    public static function init() {
        // Paranna hakutuloksia
        add_filter('posts_search', [__CLASS__, 'improve_search_query'], 10, 2);
        
        // Lisää hakuun mukaan custom fieldit
        add_filter('posts_join', [__CLASS__, 'search_join']);
        add_filter('posts_where', [__CLASS__, 'search_where']);
        add_filter('posts_distinct', [__CLASS__, 'search_distinct']);
        
        // Lisää live-haku
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_live_search']);
        add_action('wp_ajax_live_search', [__CLASS__, 'handle_live_search']);
        add_action('wp_ajax_nopriv_live_search', [__CLASS__, 'handle_live_search']);
    }

    /**
     * Paranna hakukyselyä
     */
    public static function improve_search_query($search, $wp_query) {
        global $wpdb;

        if (empty($search) || !is_search() || !$wp_query->is_main_query()) {
            return $search;
        }

        $terms = $wp_query->query_vars['s'];
        $search = '';

        // Hae termit erikseen
        $terms = explode(' ', $terms);
        foreach ($terms as $term) {
            $term = esc_sql($wpdb->esc_like($term));
            $search .= " AND (
                {$wpdb->posts}.post_title LIKE '%{$term}%'
                OR {$wpdb->posts}.post_content LIKE '%{$term}%'
                OR {$wpdb->posts}.post_excerpt LIKE '%{$term}%'
            )";
        }

        if (!empty($search)) {
            $search = " AND (1=1 {$search} )";
        }

        return $search;
    }

    /**
     * Lisää custom fieldit hakuun - JOIN
     */
    public static function search_join($join) {
        global $wpdb;

        if (is_search()) {
            $join .= " LEFT JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id ";
        }
        return $join;
    }

    /**
     * Lisää custom fieldit hakuun - WHERE
     */
    public static function search_where($where) {
        global $wpdb;

        if (is_search()) {
            $search_term = get_search_query();
            $where = preg_replace(
                "/\(\s*{$wpdb->posts}.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                "({$wpdb->posts}.post_title LIKE $1) OR ({$wpdb->postmeta}.meta_value LIKE $1)",
                $where
            );
        }
        return $where;
    }

    /**
     * Lisää custom fieldit hakuun - DISTINCT
     */
    public static function search_distinct($distinct) {
        if (is_search()) {
            return "DISTINCT";
        }
        return $distinct;
    }

    /**
     * Lisää live-haun skriptit
     */
    public static function enqueue_live_search() {
        if (!is_admin()) {
            wp_enqueue_script(
                'live-search',
                get_template_directory_uri() . '/assets/js/live-search.js',
                ['jquery'],
                filemtime(get_template_directory() . '/assets/js/live-search.js'),
                true
            );

            wp_localize_script('live-search', 'liveSearchParams', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('live_search_nonce')
            ]);
        }
    }

    /**
     * Käsittele live-haku
     */
    public static function handle_live_search() {
        check_ajax_referer('live_search_nonce', 'nonce');

        $search_term = sanitize_text_field($_POST['term'] ?? '');
        if (empty($search_term)) {
            wp_send_json_error();
        }

        $args = [
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => 5
        ];

        $query = new WP_Query($args);
        $results = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = [
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 20),
                    'thumbnail' => get_the_post_thumbnail_url(null, 'thumbnail')
                ];
            }
        }

        wp_reset_postdata();
        wp_send_json_success($results);
    }
}

// Alusta hakuparannukset
TonysTheme_Search_Enhancer::init();
