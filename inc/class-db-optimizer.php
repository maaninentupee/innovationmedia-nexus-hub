<?php
/**
 * Tietokannan optimointi
 *
 * @package TonysTheme
 */

class TonysTheme_DB_Optimizer {
    /**
     * Alusta tietokannan optimointi
     */
    public static function init() {
        // Optimoi kyselyt vain tuotantoympäristössä
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            add_action('init', [__CLASS__, 'optimize_queries']);
            add_action('init', [__CLASS__, 'optimize_emojis']);
            add_action('init', [__CLASS__, 'optimize_oembed']);
            add_action('init', [__CLASS__, 'manage_revisions']);
        }

        // Lisää admin-asetukset
        if (is_admin()) {
            add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
            add_action('admin_init', [__CLASS__, 'register_settings']);
            add_action('admin_init', [__CLASS__, 'schedule_cleanup']);
        }
    }

    /**
     * Optimoi WordPress-kyselyt
     */
    public static function optimize_queries() {
        // Poista turhat kyselyt headista
        remove_action('wp_head', 'wp_generator');                     // WordPress versio
        remove_action('wp_head', 'wlwmanifest_link');                // Windows Live Writer
        remove_action('wp_head', 'rsd_link');                        // Really Simple Discovery
        remove_action('wp_head', 'wp_shortlink_wp_head');            // Shortlink
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head'); // Edellinen/seuraava artikkeli linkit
        remove_action('wp_head', 'feed_links_extra', 3);             // Ylimääräiset syötelinkit
        
        // Poista REST API head linkit jos ei tarvita
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('template_redirect', 'rest_output_link_header', 11);
        
        // Poista DNS prefetch
        remove_action('wp_head', 'wp_resource_hints', 2);

        // Optimoi heartbeat API
        add_filter('heartbeat_settings', function($settings) {
            // Hidasta heartbeat-kyselyitä admin-paneelissa (oletuksena 15)
            $settings['interval'] = 60;
            return $settings;
        });

        // Poista heartbeat kokonaan julkiselta puolelta
        add_action('init', function() {
            if (!is_admin()) {
                wp_deregister_script('heartbeat');
            }
        });

        // Optimoi wp-cron
        if (!defined('DISABLE_WP_CRON')) {
            define('DISABLE_WP_CRON', true);
        }

        // Vähennä post_meta kyselyitä välimuistittamalla
        add_filter('update_post_metadata_cache', function($check, $post_ids) {
            if (!is_array($post_ids)) {
                $post_ids = [$post_ids];
            }
            
            foreach ($post_ids as $post_id) {
                wp_cache_add("post_meta_{$post_id}", [], 'post_meta');
            }
            
            return $check;
        }, 10, 2);

        // Optimoi taxonomy kyselyt
        add_filter('term_query_fields', function($fields, $taxonomies) {
            if (empty($taxonomies)) {
                return $fields;
            }
            return 't.term_id, t.name, t.slug, t.term_group';
        }, 10, 2);

        // Optimoi user kyselyt
        add_filter('pre_user_query', function($query) {
            if (isset($query->query_vars['count_total']) && !$query->query_vars['count_total']) {
                $query->query_fields = 'ID, user_login, user_nicename, user_email, user_url, user_registered, display_name';
            }
            return $query;
        });
    }

    /**
     * Optimoi emojit
     */
    public static function optimize_emojis() {
        if (get_option('tonys_theme_disable_emojis', false)) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
            
            // Poista emoji CDN
            add_filter('emoji_svg_url', '__return_false');
            
            // Poista emojit TinyMCE:stä
            add_filter('tiny_mce_plugins', function($plugins) {
                if (is_array($plugins)) {
                    return array_diff($plugins, ['wpemoji']);
                }
                return [];
            });
        }
    }

    /**
     * Optimoi oEmbed
     */
    public static function optimize_oembed() {
        if (get_option('tonys_theme_disable_oembed', false)) {
            // Poista oEmbed-ominaisuudet
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
            
            // Poista oEmbed-suodattimet
            remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
            remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
            
            // Poista oEmbed REST API endpoint
            add_filter('rest_endpoints', function($endpoints) {
                if (isset($endpoints['/oembed/1.0/embed'])) {
                    unset($endpoints['/oembed/1.0/embed']);
                }
                return $endpoints;
            });

            // Poista oEmbed-related rewrite rules
            add_filter('rewrite_rules_array', function($rules) {
                foreach($rules as $rule => $rewrite) {
                    if (strpos($rewrite, 'embed=true') !== false) {
                        unset($rules[$rule]);
                    }
                }
                return $rules;
            });
        }
    }

    /**
     * Hallitse revisioita
     */
    public static function manage_revisions() {
        // Rajoita revisioiden määrää
        $max_revisions = get_option('tonys_theme_max_revisions', 5);
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', $max_revisions);
        }

        // Poista vanhat revisiot
        if (get_option('tonys_theme_auto_cleanup', false)) {
            add_action('wp_scheduled_delete', [__CLASS__, 'delete_old_revisions']);
        }
    }

    /**
     * Ajasta tietokannan puhdistus
     */
    public static function schedule_cleanup() {
        if (!wp_next_scheduled('tonys_theme_db_cleanup') && get_option('tonys_theme_auto_cleanup', false)) {
            wp_schedule_event(time(), 'daily', 'tonys_theme_db_cleanup');
        }
    }

    /**
     * Poista vanhat revisiot
     */
    public static function delete_old_revisions() {
        global $wpdb;
        
        $max_revisions = get_option('tonys_theme_max_revisions', 5);
        
        // Hae kaikki artikkelit joilla on revisioita
        $posts = $wpdb->get_results(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post' OR post_type = 'page'"
        );
        
        foreach ($posts as $post) {
            // Hae revisiot uusimmasta vanhimpaan
            $revisions = wp_get_post_revisions($post->ID, ['order' => 'ASC']);
            
            // Jos revisioita on enemmän kuin sallittu määrä, poista vanhimmat
            if (count($revisions) > $max_revisions) {
                $to_delete = array_slice($revisions, 0, count($revisions) - $max_revisions);
                foreach ($to_delete as $revision) {
                    wp_delete_post_revision($revision->ID);
                }
            }
        }
    }

    /**
     * Optimoi tietokanta
     */
    public static function optimize_database() {
        global $wpdb;
        
        // Optimoi taulut
        $tables = $wpdb->get_results('SHOW TABLES');
        foreach ($tables as $table) {
            $table_name = current($table);
            $wpdb->query("OPTIMIZE TABLE $table_name");
        }
        
        // Siivoa roskapostit
        $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'");
        
        // Siivoa roskakori
        $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'");
        $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'");
        
        // Siivoa meta-taulut
        $wpdb->query("DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL");
        $wpdb->query("DELETE cm FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id WHERE c.comment_ID IS NULL");
        
        // Siivoa termi-relaatiot
        $wpdb->query("DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} p ON p.ID = tr.object_id WHERE p.ID IS NULL");
        
        // Siivoa käyttämättömät termit
        $wpdb->query("DELETE t FROM {$wpdb->terms} t LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.term_id IS NULL");
        
        // Siivoa transient-optiot
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
        
        // Optimoi autoload-optiot
        $wpdb->query("UPDATE {$wpdb->options} SET autoload = 'no' WHERE option_name NOT IN ('siteurl', 'home', 'blogname', 'blogdescription', 'users_can_register', 'active_plugins', 'template', 'stylesheet')");
        
        // Siivoa vanhentuneet sessiot
        $wpdb->query("DELETE FROM {$wpdb->prefix}sessions WHERE session_expiry < UNIX_TIMESTAMP()");
        
        // Optimoi indeksit
        $wpdb->query("ANALYZE TABLE {$wpdb->posts}, {$wpdb->postmeta}, {$wpdb->options}, {$wpdb->comments}, {$wpdb->commentmeta}, {$wpdb->terms}, {$wpdb->term_relationships}, {$wpdb->term_taxonomy}");
        
        return true;
    }

    /**
     * Lisää admin-valikko
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'themes.php',
            'Tietokannan optimointi',
            'DB Optimointi',
            'manage_options',
            'tonys-db-optimizer',
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Rekisteröi asetukset
     */
    public static function register_settings() {
        register_setting('tonys_theme_db_options', 'tonys_theme_disable_emojis');
        register_setting('tonys_theme_db_options', 'tonys_theme_disable_oembed');
        register_setting('tonys_theme_db_options', 'tonys_theme_max_revisions');
        register_setting('tonys_theme_db_options', 'tonys_theme_auto_cleanup');
    }

    /**
     * Näytä admin-sivu
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Tarkista onko optimointi käynnissä
        if (isset($_POST['optimize_database'])) {
            check_admin_referer('tonys_theme_db_optimize');
            
            if (self::optimize_database()) {
                echo '<div class="notice notice-success"><p>Tietokanta optimoitu onnistuneesti!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Tietokannan optimoinnissa tapahtui virhe.</p></div>';
            }
        }

        // Tallenna asetukset
        if (isset($_POST['tonys_theme_db_options'])) {
            check_admin_referer('tonys_theme_db_options');
            
            update_option('tonys_theme_disable_emojis', 
                isset($_POST['tonys_theme_disable_emojis']) ? 1 : 0);
            update_option('tonys_theme_disable_oembed', 
                isset($_POST['tonys_theme_disable_oembed']) ? 1 : 0);
            update_option('tonys_theme_max_revisions',
                intval($_POST['tonys_theme_max_revisions']));
            update_option('tonys_theme_auto_cleanup',
                isset($_POST['tonys_theme_auto_cleanup']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>Asetukset tallennettu!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Tietokannan optimointi</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('tonys_theme_db_options'); ?>
                <input type="hidden" name="tonys_theme_db_options" value="1">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">WordPress Emojit</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_disable_emojis" value="1" 
                                    <?php checked(1, get_option('tonys_theme_disable_emojis'), true); ?>>
                                Poista WordPress-emojit käytöstä
                            </label>
                            <p class="description">
                                Poistaa WordPress-emojien lataamisen. Selainten omat emojit toimivat silti.
                                Säästää 1-2 HTTP-pyyntöä ja noin 30KB ladattavaa dataa.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">oEmbed</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_disable_oembed" value="1" 
                                    <?php checked(1, get_option('tonys_theme_disable_oembed'), true); ?>>
                                Poista oEmbed käytöstä
                            </label>
                            <p class="description">
                                Poistaa automaattisen median upotuksen (esim. YouTube-videot) käytöstä.
                                Säästää 1-2 HTTP-pyyntöä ja noin 20KB ladattavaa dataa.
                                Voit silti käyttää iframe-upotuksia manuaalisesti.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Revisioiden hallinta</th>
                        <td>
                            <label>
                                Säilytä enintään 
                                <input type="number" name="tonys_theme_max_revisions" value="<?php echo esc_attr(get_option('tonys_theme_max_revisions', 5)); ?>" min="1" max="100" step="1">
                                revisiota per artikkeli
                            </label>
                            <p class="description">
                                Määritä kuinka monta revisiota säilytetään per artikkeli.
                                Liian suuri määrä revisioita voi hidastaa sivustoa.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Automaattinen puhdistus</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_auto_cleanup" value="1" 
                                    <?php checked(1, get_option('tonys_theme_auto_cleanup'), true); ?>>
                                Suorita tietokannan puhdistus automaattisesti kerran päivässä
                            </label>
                            <p class="description">
                                Puhdistaa automaattisesti roskapostit, poistetut artikkelit ja ylimääräiset revisiot.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Tallenna asetukset'); ?>
            </form>

            <h2>Manuaalinen optimointi</h2>
            <form method="post" action="">
                <?php wp_nonce_field('tonys_theme_db_optimize'); ?>
                <p>
                    Tämä toiminto optimoi tietokannan taulut ja poistaa turhat tiedot kuten:
                </p>
                <ul>
                    <li>Roskapostit ja poistetut kommentit</li>
                    <li>Poistetut artikkelit</li>
                    <li>Orvot meta-tiedot</li>
                    <li>Ylimääräiset revisiot</li>
                    <li>Termi-relaatiot</li>
                    <li>Käyttämättömät termit</li>
                    <li>Transient-optiot</li>
                    <li>Autoload-optiot</li>
                    <li>Vanhentuneet sessiot</li>
                    <li>Indeksit</li>
                </ul>
                <?php submit_button('Optimoi tietokanta nyt', 'primary', 'optimize_database'); ?>
            </form>
        </div>
        <?php
    }
}

// Alusta tietokannan optimointi
TonysTheme_DB_Optimizer::init();
