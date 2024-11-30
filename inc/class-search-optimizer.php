<?php
/**
 * Hakutoiminnon optimointi
 *
 * @package TonysTheme
 */

class TonysTheme_Search_Optimizer {
    /**
     * Alusta hakuoptimointi
     */
    public static function init() {
        // Optimoi hakukyselyt vain tuotantoympäristössä
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            add_filter('posts_search', [__CLASS__, 'optimize_search_query'], 10, 2);
            add_filter('posts_where', [__CLASS__, 'optimize_search_where'], 10, 2);
            add_filter('posts_join', [__CLASS__, 'optimize_search_join'], 10, 2);
            add_filter('posts_groupby', [__CLASS__, 'optimize_search_groupby'], 10, 2);
            add_filter('posts_orderby', [__CLASS__, 'optimize_search_orderby'], 10, 2);
        }

        // Lisää admin-asetukset
        if (is_admin()) {
            add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
            add_action('admin_init', [__CLASS__, 'register_settings']);
        }

        // Lisää välimuistitus hakutuloksille
        add_action('pre_get_posts', [__CLASS__, 'maybe_use_search_cache']);
        add_action('wp', [__CLASS__, 'maybe_cache_search_results']);
    }

    /**
     * Optimoi hakukysely
     */
    public static function optimize_search_query($search, $wp_query) {
        if (!$wp_query->is_search() || !$wp_query->is_main_query()) {
            return $search;
        }

        global $wpdb;

        $q = $wp_query->query_vars;
        $search_terms = isset($q['search_terms']) ? $q['search_terms'] : [];

        if (empty($search_terms)) {
            return $search;
        }

        $search = '';
        $searchand = '';

        foreach ($search_terms as $term) {
            $term = esc_sql($wpdb->esc_like($term));
            $search .= "{$searchand}(";
            
            // Hae otsikosta (painoarvo 3)
            $search .= $wpdb->prepare("(CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN 3 ELSE 0 END) + ", '%' . $term . '%');
            
            // Hae sisällöstä (painoarvo 1)
            $search .= $wpdb->prepare("(CASE WHEN {$wpdb->posts}.post_content LIKE %s THEN 1 ELSE 0 END) + ", '%' . $term . '%');
            
            // Hae kuvauksesta (painoarvo 2)
            $search .= $wpdb->prepare("(CASE WHEN {$wpdb->posts}.post_excerpt LIKE %s THEN 2 ELSE 0 END)", '%' . $term . '%');
            
            $search .= ") > 0";
            $searchand = ' AND ';
        }

        if (!empty($search)) {
            $search = " AND ({$search}) ";
        }

        return $search;
    }

    /**
     * Optimoi WHERE-lause
     */
    public static function optimize_search_where($where, $wp_query) {
        if (!$wp_query->is_search() || !$wp_query->is_main_query()) {
            return $where;
        }

        // Rajaa haku vain julkaistuihin artikkeleihin
        $where .= " AND {$GLOBALS['wpdb']->posts}.post_status = 'publish'";

        // Rajaa haku määriteltyihin post-tyyppeihin
        $post_types = get_option('tonys_theme_search_post_types', ['post', 'page']);
        if (!empty($post_types)) {
            $post_types = array_map('esc_sql', $post_types);
            $where .= " AND {$GLOBALS['wpdb']->posts}.post_type IN ('" . implode("','", $post_types) . "')";
        }

        return $where;
    }

    /**
     * Optimoi JOIN-lause
     */
    public static function optimize_search_join($join, $wp_query) {
        if (!$wp_query->is_search() || !$wp_query->is_main_query()) {
            return $join;
        }

        global $wpdb;

        // Liitä meta-taulut jos meta-haku on käytössä
        if (get_option('tonys_theme_search_include_meta', false)) {
            $join .= " LEFT JOIN {$wpdb->postmeta} ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id)";
        }

        return $join;
    }

    /**
     * Optimoi GROUP BY -lause
     */
    public static function optimize_search_groupby($groupby, $wp_query) {
        if (!$wp_query->is_search() || !$wp_query->is_main_query()) {
            return $groupby;
        }

        global $wpdb;

        // Ryhmittele tulokset ID:n mukaan jos meta-haku on käytössä
        if (get_option('tonys_theme_search_include_meta', false)) {
            $groupby = "{$wpdb->posts}.ID";
        }

        return $groupby;
    }

    /**
     * Optimoi ORDER BY -lause
     */
    public static function optimize_search_orderby($orderby, $wp_query) {
        if (!$wp_query->is_search() || !$wp_query->is_main_query()) {
            return $orderby;
        }

        // Järjestä relevanssin mukaan
        return '(
            CASE 
                WHEN post_title LIKE %' . get_search_query() . '% THEN 10
                WHEN post_excerpt LIKE %' . get_search_query() . '% THEN 5
                ELSE 1
            END
        ) DESC, post_date DESC';
    }

    /**
     * Tarkista onko hakutulokset välimuistissa
     */
    public static function maybe_use_search_cache($query) {
        if (!$query->is_search() || !$query->is_main_query()) {
            return;
        }

        // Tarkista onko välimuistitus käytössä
        if (!get_option('tonys_theme_search_enable_cache', false)) {
            return;
        }

        $cache_key = 'search_' . md5(serialize($query->query_vars));
        $cache = wp_cache_get($cache_key, 'tonys_theme_search');

        if ($cache !== false) {
            $query->posts = $cache['posts'];
            $query->post_count = count($cache['posts']);
            $query->found_posts = $cache['found_posts'];
            $query->max_num_pages = $cache['max_num_pages'];

            // Estä kyselyn suoritus
            $query->no_found_rows = true;
        }
    }

    /**
     * Tallenna hakutulokset välimuistiin
     */
    public static function maybe_cache_search_results() {
        if (!is_search() || !get_option('tonys_theme_search_enable_cache', false)) {
            return;
        }

        global $wp_query;
        $cache_key = 'search_' . md5(serialize($wp_query->query_vars));

        $cache = [
            'posts' => $wp_query->posts,
            'found_posts' => $wp_query->found_posts,
            'max_num_pages' => $wp_query->max_num_pages
        ];

        // Tallenna välimuistiin tunnin ajaksi
        wp_cache_set($cache_key, $cache, 'tonys_theme_search', HOUR_IN_SECONDS);
    }

    /**
     * Lisää admin-valikko
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'themes.php',
            'Haun optimointi',
            'Haun optimointi',
            'manage_options',
            'tonys-search-optimizer',
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Rekisteröi asetukset
     */
    public static function register_settings() {
        register_setting('tonys_theme_search_options', 'tonys_theme_search_post_types');
        register_setting('tonys_theme_search_options', 'tonys_theme_search_include_meta');
        register_setting('tonys_theme_search_options', 'tonys_theme_search_enable_cache');
    }

    /**
     * Näytä admin-sivu
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Tallenna asetukset
        if (isset($_POST['tonys_theme_search_options'])) {
            check_admin_referer('tonys_theme_search_options');
            
            update_option('tonys_theme_search_post_types', 
                isset($_POST['tonys_theme_search_post_types']) ? $_POST['tonys_theme_search_post_types'] : []);
            update_option('tonys_theme_search_include_meta', 
                isset($_POST['tonys_theme_search_include_meta']) ? 1 : 0);
            update_option('tonys_theme_search_enable_cache', 
                isset($_POST['tonys_theme_search_enable_cache']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>Asetukset tallennettu!</p></div>';
        }

        // Tyhjennä välimuisti
        if (isset($_POST['clear_search_cache'])) {
            check_admin_referer('clear_search_cache');
            wp_cache_flush();
            echo '<div class="notice notice-success"><p>Hakuvälimuisti tyhjennetty!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Haun optimointi</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('tonys_theme_search_options'); ?>
                <input type="hidden" name="tonys_theme_search_options" value="1">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Sisällytettävät sisältötyypit</th>
                        <td>
                            <?php
                            $post_types = get_post_types(['public' => true], 'objects');
                            $selected_types = get_option('tonys_theme_search_post_types', ['post', 'page']);
                            
                            foreach ($post_types as $type) {
                                echo '<label>';
                                echo '<input type="checkbox" name="tonys_theme_search_post_types[]" value="' . esc_attr($type->name) . '" ';
                                checked(in_array($type->name, $selected_types));
                                echo '> ' . esc_html($type->label);
                                echo '</label><br>';
                            }
                            ?>
                            <p class="description">
                                Valitse sisältötyypit jotka sisällytetään hakutuloksiin.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Meta-tietojen haku</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_search_include_meta" value="1" 
                                    <?php checked(1, get_option('tonys_theme_search_include_meta'), true); ?>>
                                Sisällytä meta-tiedot hakuun
                            </label>
                            <p class="description">
                                Hae myös artikkeleiden meta-tiedoista. Tämä voi hidastaa hakua hieman.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Välimuistitus</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_search_enable_cache" value="1" 
                                    <?php checked(1, get_option('tonys_theme_search_enable_cache'), true); ?>>
                                Käytä välimuistitusta hakutuloksille
                            </label>
                            <p class="description">
                                Tallentaa hakutulokset välimuistiin tunnin ajaksi. Nopeuttaa toistuvia hakuja.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Tallenna asetukset'); ?>
            </form>

            <h2>Välimuistin hallinta</h2>
            <form method="post" action="">
                <?php wp_nonce_field('clear_search_cache'); ?>
                <p>
                    Tyhjennä hakutulosten välimuisti jos huomaat ongelmia hakutuloksissa.
                </p>
                <?php submit_button('Tyhjennä hakuvälimuisti', 'secondary', 'clear_search_cache'); ?>
            </form>
        </div>
        <?php
    }
}

// Alusta hakuoptimointi
TonysTheme_Search_Optimizer::init();
