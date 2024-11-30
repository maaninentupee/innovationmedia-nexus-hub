<?php
/**
 * REST API:n optimointi
 *
 * @package TonysTheme
 */

class TonysTheme_REST_Optimizer {
    /**
     * Alusta REST API:n optimointi
     */
    public static function init() {
        // Optimoi REST API vain tuotantoympäristössä
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // REST API:n välimuistitus
            add_filter('rest_pre_dispatch', [__CLASS__, 'cache_rest_response'], 10, 3);
            
            // REST API:n suorituskyvyn optimointi
            add_action('rest_api_init', [__CLASS__, 'optimize_rest_api']);
            
            // REST API:n tietoturvan parantaminen
            add_filter('rest_authentication_errors', [__CLASS__, 'restrict_rest_api']);
        }

        // Lisää admin-asetukset
        if (is_admin()) {
            add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
            add_action('admin_init', [__CLASS__, 'register_settings']);
        }
    }

    /**
     * Välimuistita REST API vastaukset
     */
    public static function cache_rest_response($response, $server, $request) {
        // Älä välimuistita jos välimuistitus ei ole käytössä
        if (!get_option('tonys_theme_rest_enable_cache', true)) {
            return $response;
        }

        // Älä välimuistita kirjautuneiden käyttäjien pyyntöjä
        if (is_user_logged_in()) {
            return $response;
        }

        // Älä välimuistita muokkauspyyntöjä
        if ($request->get_method() !== 'GET') {
            return $response;
        }

        // Luo välimuistiavain
        $cache_key = 'rest_' . md5($request->get_route() . serialize($request->get_params()));

        // Tarkista välimuisti
        $cached_response = wp_cache_get($cache_key, 'tonys_theme_rest');
        if ($cached_response !== false) {
            return $cached_response;
        }

        // Suorita alkuperäinen pyyntö
        $response = rest_do_request($request);

        // Tallenna välimuistiin jos vastaus on onnistunut
        if (!is_wp_error($response) && $response->status === 200) {
            wp_cache_set($cache_key, $response, 'tonys_theme_rest', HOUR_IN_SECONDS);
        }

        return $response;
    }

    /**
     * Optimoi REST API:n suorituskyky
     */
    public static function optimize_rest_api() {
        // Poista turhat kentät vastauksista
        if (get_option('tonys_theme_rest_remove_unused', true)) {
            add_filter('rest_prepare_post', [__CLASS__, 'optimize_post_response'], 10, 3);
            add_filter('rest_prepare_page', [__CLASS__, 'optimize_post_response'], 10, 3);
            add_filter('rest_prepare_user', [__CLASS__, 'optimize_user_response'], 10, 3);
        }

        // Rajoita sivutuksen kokoa
        add_filter('rest_post_collection_params', [__CLASS__, 'limit_pagination'], 10, 2);
    }

    /**
     * Optimoi artikkelin REST vastaus
     */
    public static function optimize_post_response($response, $post, $request) {
        $data = $response->get_data();

        // Poista turhat kentät
        $remove_fields = [
            'guid',
            'link',
            'template',
            'ping_status',
            'comment_status',
            'sticky',
            'format',
            'meta',
            '_links'
        ];

        foreach ($remove_fields as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }

        // Optimoi featured media
        if (isset($data['featured_media']) && $data['featured_media']) {
            $data['featured_media'] = [
                'id' => get_post_thumbnail_id($post),
                'src' => get_the_post_thumbnail_url($post, 'medium'),
                'alt' => get_post_meta(get_post_thumbnail_id($post), '_wp_attachment_image_alt', true)
            ];
        }

        $response->set_data($data);
        return $response;
    }

    /**
     * Optimoi käyttäjän REST vastaus
     */
    public static function optimize_user_response($response, $user, $request) {
        $data = $response->get_data();

        // Poista turhat kentät
        $remove_fields = [
            'link',
            'description',
            'url',
            'meta',
            '_links',
            'capabilities',
            'extra_capabilities',
            'avatar_urls'
        ];

        foreach ($remove_fields as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }

        $response->set_data($data);
        return $response;
    }

    /**
     * Rajoita sivutuksen kokoa
     */
    public static function limit_pagination($params) {
        if (isset($params['per_page'])) {
            $params['per_page']['maximum'] = 50;
        }
        return $params;
    }

    /**
     * Rajoita REST API:n käyttöä
     */
    public static function restrict_rest_api($access) {
        // Salli pääsy vain määritellyille reiteille
        $allowed_routes = [
            '/wp/v2/posts',
            '/wp/v2/pages',
            '/wp/v2/media'
        ];

        // Tarkista onko reitti sallittu
        $current_route = $_SERVER['REQUEST_URI'];
        foreach ($allowed_routes as $route) {
            if (strpos($current_route, $route) !== false) {
                return $access;
            }
        }

        // Estä pääsy muilta kuin kirjautuneilta käyttäjiltä
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                __('Pääsy estetty.'),
                ['status' => 401]
            );
        }

        return $access;
    }

    /**
     * Lisää admin-valikko
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'themes.php',
            'REST API:n optimointi',
            'REST optimointi',
            'manage_options',
            'tonys-rest-optimizer',
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Rekisteröi asetukset
     */
    public static function register_settings() {
        register_setting('tonys_theme_rest_options', 'tonys_theme_rest_enable_cache');
        register_setting('tonys_theme_rest_options', 'tonys_theme_rest_remove_unused');
    }

    /**
     * Näytä admin-sivu
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Tallenna asetukset
        if (isset($_POST['tonys_theme_rest_options'])) {
            check_admin_referer('tonys_theme_rest_options');
            
            update_option('tonys_theme_rest_enable_cache', 
                isset($_POST['tonys_theme_rest_enable_cache']) ? 1 : 0);
            update_option('tonys_theme_rest_remove_unused', 
                isset($_POST['tonys_theme_rest_remove_unused']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>Asetukset tallennettu!</p></div>';
        }

        // Tyhjennä välimuisti
        if (isset($_POST['clear_rest_cache'])) {
            check_admin_referer('clear_rest_cache');
            wp_cache_delete_group('tonys_theme_rest');
            echo '<div class="notice notice-success"><p>REST API:n välimuisti tyhjennetty!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>REST API:n optimointi</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('tonys_theme_rest_options'); ?>
                <input type="hidden" name="tonys_theme_rest_options" value="1">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Välimuistitus</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_rest_enable_cache" value="1" 
                                    <?php checked(1, get_option('tonys_theme_rest_enable_cache', true), true); ?>>
                                Käytä välimuistitusta REST API vastauksille
                            </label>
                            <p class="description">
                                Tallentaa GET-pyynnöt välimuistiin tunnin ajaksi. Nopeuttaa API-kutsuja huomattavasti.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Vastausten optimointi</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_rest_remove_unused" value="1" 
                                    <?php checked(1, get_option('tonys_theme_rest_remove_unused', true), true); ?>>
                                Poista turhat kentät vastauksista
                            </label>
                            <p class="description">
                                Poistaa harvoin käytetyt kentät API-vastauksista. Pienentää vastausten kokoa.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Tallenna asetukset'); ?>
            </form>

            <h2>Välimuistin hallinta</h2>
            <form method="post" action="">
                <?php wp_nonce_field('clear_rest_cache'); ?>
                <p>
                    Tyhjennä REST API:n välimuisti jos huomaat ongelmia API-vastauksissa.
                </p>
                <?php submit_button('Tyhjennä REST välimuisti', 'secondary', 'clear_rest_cache'); ?>
            </form>
        </div>
        <?php
    }
}

// Alusta REST API:n optimointi
TonysTheme_REST_Optimizer::init();
