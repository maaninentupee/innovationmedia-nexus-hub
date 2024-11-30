<?php
/**
 * Välimuistin optimointi
 *
 * @package TonysTheme
 */

class TonysTheme_Cache_Optimizer {
    /**
     * Alusta välimuistin optimointi
     */
    public static function init() {
        // Optimoi välimuisti vain tuotantoympäristössä
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Tarkista välimuistin kirjoitusoikeudet
            if (!self::check_cache_permissions()) {
                add_action('admin_notices', [__CLASS__, 'show_cache_permission_error']);
                return;
            }

            // Tarkista välimuistin koko
            if (self::get_cache_size() > self::$max_cache_size) {
                self::cleanup_cache();
            }

            // Alusta välimuistin statistiikka
            self::init_cache_stats();

            // Sivuvälimuisti
            add_action('template_redirect', [__CLASS__, 'maybe_cache_page']);
            add_action('wp_footer', [__CLASS__, 'maybe_save_page_cache'], PHP_INT_MAX);

            // Tietokantakyselyiden välimuisti
            add_filter('posts_pre_query', [__CLASS__, 'maybe_cache_query'], 10, 2);
            add_action('save_post', [__CLASS__, 'clear_query_cache']);
            
            // Navigaation välimuisti
            add_filter('pre_wp_nav_menu', [__CLASS__, 'maybe_cache_menu'], 10, 2);
            add_action('wp_update_nav_menu', [__CLASS__, 'clear_menu_cache']);
            
            // Widgetien välimuisti
            add_filter('pre_get_sidebar', [__CLASS__, 'maybe_cache_sidebar'], 10, 2);
            add_action('updated_option', [__CLASS__, 'clear_widget_cache']);
        }

        // Lisää admin-asetukset
        if (is_admin()) {
            add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
            add_action('admin_init', [__CLASS__, 'register_settings']);
        }
    }

    /**
     * Tarkista välimuistin kirjoitusoikeudet
     */
    private static function check_cache_permissions() {
        $cache_dir = WP_CONTENT_DIR . '/cache/tonys-theme';
        
        if (!file_exists($cache_dir)) {
            if (!wp_mkdir_p($cache_dir)) {
                error_log('TonysTheme: Välimuistihakemiston luonti epäonnistui');
                return false;
            }
        }

        if (!is_writable($cache_dir)) {
            error_log('TonysTheme: Välimuistihakemisto ei ole kirjoitettavissa');
            return false;
        }

        return true;
    }

    /**
     * Näytä välimuistin virheet
     */
    public static function show_cache_permission_error() {
        ?>
        <div class="notice notice-error">
            <p>TonysTheme: Välimuistin käyttö epäonnistui. Tarkista hakemiston oikeudet.</p>
        </div>
        <?php
    }

    /**
     * Alusta välimuistin statistiikka
     */
    private static function init_cache_stats() {
        self::$cache_stats = [
            'hits' => 0,
            'misses' => 0,
            'size' => 0,
            'last_cleanup' => 0
        ];

        add_action('shutdown', [__CLASS__, 'save_cache_stats']);
    }

    /**
     * Tallenna välimuistin statistiikka
     */
    public static function save_cache_stats() {
        update_option('tonys_theme_cache_stats', self::$cache_stats);
    }

    /**
     * Hae välimuistin koko
     */
    private static function get_cache_size() {
        $cache_dir = WP_CONTENT_DIR . '/cache/tonys-theme';
        $size = 0;

        if (is_dir($cache_dir)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cache_dir)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }

        return $size;
    }

    /**
     * Siivoa välimuisti
     */
    private static function cleanup_cache() {
        $cache_dir = WP_CONTENT_DIR . '/cache/tonys-theme';
        $files = glob($cache_dir . '/*');
        
        // Järjestä tiedostot viimeisen käyttöajan mukaan
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Poista vanhimmat tiedostot kunnes koko on alle rajan
        $current_size = self::get_cache_size();
        foreach ($files as $file) {
            if ($current_size <= self::$max_cache_size * 0.8) { // Jätä 20% puskuria
                break;
            }
            $current_size -= filesize($file);
            unlink($file);
        }

        self::$cache_stats['last_cleanup'] = time();
    }

    /**
     * Välimuistita sivu
     */
    public static function maybe_cache_page() {
        // Älä välimuistita jos välimuistitus ei ole käytössä
        if (!get_option('tonys_theme_enable_page_cache', true)) {
            return;
        }

        // Älä välimuistita kirjautuneille käyttäjille
        if (is_user_logged_in()) {
            return;
        }

        // Älä välimuistita POST-pyyntöjä tai muita kuin GET-pyyntöjä
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }

        // Älä välimuistita hakutuloksia tai dynaamisia sivuja
        if (is_search() || is_404() || is_feed() || is_trackback() || is_robots() || is_preview()) {
            return;
        }

        // Älä välimuistita WooCommerce-sivuja
        if (function_exists('is_woocommerce') && (is_cart() || is_checkout() || is_account_page())) {
            return;
        }

        // Älä välimuistita jos sivulla on lomake
        if (isset($_COOKIE['wordpress_no_cache']) || isset($_COOKIE['comment_author_'])) {
            return;
        }

        // Tarkista mobiili vs. työpöytä versio
        $device_type = wp_is_mobile() ? 'mobile' : 'desktop';
        
        // Luo yksilöllinen välimuistiavain
        $cache_key = self::generate_cache_key($device_type);
        
        // Tarkista välimuisti
        $cached_data = self::get_cached_page($cache_key);
        
        if ($cached_data !== false) {
            // Lisää välimuistin metatiedot
            header('X-Cache: HIT');
            header('X-Cache-Time: ' . $cached_data['time']);
            
            // Palauta välimuistista
            echo $cached_data['content'];
            exit;
        }

        // Lisää välimuistin metatiedot
        header('X-Cache: MISS');
        
        // Aloita output buffering
        ob_start();
    }

    /**
     * Sanitoi välimuistin avain
     *
     * @param string $key Välimuistin avain
     * @return string Sanitoitu avain
     */
    private static function sanitize_cache_key($key) {
        // Poista erikoismerkit
        $key = sanitize_key($key);
        
        // Lisää versio avaimeen
        $key = $key . '_v' . self::$cache_version;
        
        // Lisää sivuston ID avaimeen monisivustotukea varten
        if (is_multisite()) {
            $key = get_current_blog_id() . '_' . $key;
        }
        
        // Rajoita avaimen pituus
        if (strlen($key) > 40) {
            $key = substr($key, 0, 40);
        }
        
        return $key;
    }

    /**
     * Generoi välimuistin avain
     *
     * @param string $type Välimuistin tyyppi
     * @param mixed $data Avaimeen liittyvä data
     * @return string Välimuistin avain
     */
    private static function generate_cache_key($type, $data = '') {
        $key = $type;
        
        if (is_array($data)) {
            $key .= '_' . md5(serialize($data));
        } elseif (is_scalar($data)) {
            $key .= '_' . $data;
        }
        
        return self::sanitize_cache_key($key);
    }

    /**
     * Hae sivu välimuistista
     */
    private static function get_cached_page($cache_key) {
        $cached_data = wp_cache_get($cache_key, 'tonys_theme_page_cache');
        
        if ($cached_data === false) {
            return false;
        }

        // Tarkista välimuistin vanhentuminen
        $max_age = get_option('tonys_theme_page_cache_ttl', HOUR_IN_SECONDS);
        if (time() - $cached_data['time'] > $max_age) {
            wp_cache_delete($cache_key, 'tonys_theme_page_cache');
            return false;
        }

        return $cached_data;
    }

    /**
     * Tallenna sivu välimuistiin
     */
    public static function maybe_save_page_cache() {
        if (!get_option('tonys_theme_enable_page_cache', true) || is_user_logged_in()) {
            return;
        }

        // Tarkista onko sivulla virheitä
        if (http_response_code() !== 200) {
            return;
        }

        $device_type = wp_is_mobile() ? 'mobile' : 'desktop';
        $cache_key = self::generate_cache_key($device_type);
        
        $content = ob_get_contents();
        if (!$content) {
            return;
        }

        // Optimoi HTML ennen tallennusta
        $content = self::optimize_html($content);

        // Tallenna välimuistiin
        $cache_data = array(
            'content' => $content,
            'time' => time(),
            'device' => $device_type,
            'url' => $_SERVER['REQUEST_URI']
        );

        $ttl = get_option('tonys_theme_page_cache_ttl', HOUR_IN_SECONDS);
        wp_cache_set($cache_key, $cache_data, 'tonys_theme_page_cache', $ttl);

        // Tallenna välimuistiavain listaan automaattista tyhjennystä varten
        self::register_cached_page($cache_key);
    }

    /**
     * Optimoi HTML-sisältö
     */
    private static function optimize_html($content) {
        if (!get_option('tonys_theme_enable_html_optimization', true)) {
            return $content;
        }

        // Poista turhat välilyönnit ja rivinvaihdot
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Poista HTML-kommentit (paitsi IE-konditionaalit)
        $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
        
        // Optimoi script- ja style-tagit
        $content = preg_replace('/<(script|style)([^>]*)>/i', '<$1$2>', $content);
        
        return trim($content);
    }

    /**
     * Rekisteröi välimuistiin tallennettu sivu
     */
    private static function register_cached_page($cache_key) {
        $cached_pages = get_option('tonys_theme_cached_pages', array());
        
        if (!in_array($cache_key, $cached_pages)) {
            $cached_pages[] = $cache_key;
            update_option('tonys_theme_cached_pages', $cached_pages);
        }
    }

    /**
     * Tyhjennä kaikki sivuvälimuistit
     */
    public static function clear_all_page_cache() {
        $cached_pages = get_option('tonys_theme_cached_pages', array());
        
        foreach ($cached_pages as $cache_key) {
            wp_cache_delete($cache_key, 'tonys_theme_page_cache');
        }
        
        update_option('tonys_theme_cached_pages', array());
        
        // Päivitä tyhjennysaika
        update_option('tonys_theme_cache_last_cleared', time());
    }

    /**
     * Tyhjennä yksittäisen sivun välimuisti
     */
    public static function clear_page_cache($url = '') {
        if (empty($url)) {
            $url = $_SERVER['REQUEST_URI'];
        }

        $cache_keys = array(
            self::generate_cache_key('mobile'),
            self::generate_cache_key('desktop')
        );

        foreach ($cache_keys as $cache_key) {
            wp_cache_delete($cache_key, 'tonys_theme_page_cache');
        }
    }

    /**
     * Välimuistita tietokantakysely
     *
     * @param array|null $posts Artikkelit tai null
     * @param WP_Query $query WP_Query-objekti
     * @return array|null
     */
    public static function maybe_cache_query($posts, $query) {
        // Älä välimuistita jos välimuistitus ei ole käytössä
        if (!get_option('tonys_theme_enable_query_cache', true)) {
            return $posts;
        }

        // Älä välimuistita admin-kyselyitä tai kirjautuneille käyttäjille
        if (is_admin() || is_user_logged_in()) {
            return $posts;
        }

        // Älä välimuistita tiettyjä kyselytyyppejä
        if ($query->is_preview || $query->is_search || $query->is_feed || $query->is_404) {
            return $posts;
        }

        // Luo yksilöllinen välimuistiavain kyselylle
        $cache_key = self::generate_cache_key('query', $query->query_vars);

        // Jos välimuistissa, palauta se
        $cached_posts = wp_cache_get($cache_key, 'tonys_theme_query_cache');
        if ($cached_posts !== false) {
            self::log_cache_hit('query', $cache_key);
            return $cached_posts;
        }

        // Jos ei välimuistissa, suorita kysely ja tallenna
        if ($posts === null) {
            return $posts;
        }

        // Optimoi kysely ennen tallennusta
        $posts_to_cache = self::optimize_posts_for_cache($posts);
        
        // Tallenna välimuistiin
        $ttl = self::get_query_cache_ttl($query);
        wp_cache_set($cache_key, $posts_to_cache, 'tonys_theme_query_cache', $ttl);
        
        self::log_cache_miss('query', $cache_key);
        self::register_cached_query($cache_key, $query);

        return $posts;
    }

    /**
     * Optimoi artikkelit välimuistia varten
     */
    private static function optimize_posts_for_cache($posts) {
        if (!is_array($posts)) {
            return $posts;
        }

        foreach ($posts as &$post) {
            // Poista tarpeettomat kentät
            unset($post->filter);
            
            // Optimoi meta-kentät
            if (isset($post->meta_data)) {
                $post->meta_data = self::optimize_meta_data($post->meta_data);
            }
        }

        return $posts;
    }

    /**
     * Optimoi meta-data välimuistia varten
     */
    private static function optimize_meta_data($meta_data) {
        if (!is_array($meta_data)) {
            return $meta_data;
        }

        // Poista väliaikaiset meta-kentät
        $exclude_meta = array('_edit_lock', '_edit_last', '_wp_old_slug');
        foreach ($exclude_meta as $key) {
            unset($meta_data[$key]);
        }

        return $meta_data;
    }

    /**
     * Määritä kyselyn välimuistin vanhenemisaika
     */
    private static function get_query_cache_ttl($query) {
        $base_ttl = get_option('tonys_theme_query_cache_ttl', HOUR_IN_SECONDS);

        // Mukautetut TTL:t eri kyselytyypeille
        if ($query->is_home || $query->is_archive) {
            return $base_ttl / 2; // Arkistosivut vanhenevat nopeammin
        } elseif ($query->is_single) {
            return $base_ttl * 2; // Yksittäiset artikkelit säilyvät pidempään
        }

        return $base_ttl;
    }

    /**
     * Rekisteröi välimuistiin tallennettu kysely
     */
    private static function register_cached_query($cache_key, $query) {
        $cached_queries = get_option('tonys_theme_cached_queries', array());
        
        $query_info = array(
            'key' => $cache_key,
            'type' => self::get_query_type($query),
            'time' => time()
        );

        $cached_queries[$cache_key] = $query_info;
        update_option('tonys_theme_cached_queries', $cached_queries);
    }

    /**
     * Määritä kyselyn tyyppi
     */
    private static function get_query_type($query) {
        if ($query->is_single) return 'single';
        if ($query->is_page) return 'page';
        if ($query->is_archive) return 'archive';
        if ($query->is_home) return 'home';
        return 'other';
    }

    /**
     * Tyhjennä kyselyvälimuisti
     */
    public static function clear_query_cache($post_id = null) {
        if ($post_id) {
            // Tyhjennä vain tiettyyn artikkeliin liittyvät kyselyt
            self::clear_related_queries($post_id);
        } else {
            // Tyhjennä kaikki kyselyvälimuistit
            $cached_queries = get_option('tonys_theme_cached_queries', array());
            foreach ($cached_queries as $query_info) {
                wp_cache_delete($query_info['key'], 'tonys_theme_query_cache');
            }
            update_option('tonys_theme_cached_queries', array());
        }
    }

    /**
     * Tyhjennä artikkeliin liittyvät kyselyt
     */
    private static function clear_related_queries($post_id) {
        $post_type = get_post_type($post_id);
        $cached_queries = get_option('tonys_theme_cached_queries', array());
        
        foreach ($cached_queries as $key => $query_info) {
            // Tyhjennä arkistokyselyt ja yksittäiset artikkelikyselyt
            if ($query_info['type'] === 'archive' || 
                ($query_info['type'] === 'single' && $post_type === 'post')) {
                wp_cache_delete($query_info['key'], 'tonys_theme_query_cache');
                unset($cached_queries[$key]);
            }
        }
        
        update_option('tonys_theme_cached_queries', $cached_queries);
    }

    /**
     * Kirjaa välimuistin osuma
     */
    private static function log_cache_hit($type, $key) {
        if (!get_option('tonys_theme_enable_cache_logging', false)) {
            return;
        }

        $stats = get_option('tonys_theme_cache_stats', array());
        if (!isset($stats[$type])) {
            $stats[$type] = array('hits' => 0, 'misses' => 0);
        }
        $stats[$type]['hits']++;
        update_option('tonys_theme_cache_stats', $stats);
    }

    /**
     * Kirjaa välimuistin ohitus
     */
    private static function log_cache_miss($type, $key) {
        if (!get_option('tonys_theme_enable_cache_logging', false)) {
            return;
        }

        $stats = get_option('tonys_theme_cache_stats', array());
        if (!isset($stats[$type])) {
            $stats[$type] = array('hits' => 0, 'misses' => 0);
        }
        $stats[$type]['misses']++;
        update_option('tonys_theme_cache_stats', $stats);
    }

    /**
     * Välimuistita navigaatiovalikko
     */
    public static function maybe_cache_menu($output, $args) {
        // Älä välimuistita jos välimuistitus ei ole käytössä
        if (!get_option('tonys_theme_enable_menu_cache', true)) {
            return $output;
        }

        // Luo välimuistiavain
        $cache_key = self::generate_cache_key('menu', $args);

        // Tarkista välimuisti
        $cached_menu = wp_cache_get($cache_key, 'tonys_theme_cache');
        if ($cached_menu !== false) {
            return $cached_menu;
        }

        // Suorita valikon haku ja tallenna välimuistiin
        $menu = wp_nav_menu(array_merge($args, ['echo' => false]));
        wp_cache_set($cache_key, $menu, 'tonys_theme_cache', HOUR_IN_SECONDS);

        return $menu;
    }

    /**
     * Tyhjennä valikkovälimuisti
     */
    public static function clear_menu_cache() {
        wp_cache_delete_group('tonys_theme_cache');
    }

    /**
     * Välimuistita sivupalkki
     */
    public static function maybe_cache_sidebar($output, $name) {
        // Älä välimuistita jos välimuistitus ei ole käytössä
        if (!get_option('tonys_theme_enable_widget_cache', true)) {
            return $output;
        }

        // Älä välimuistita kirjautuneille käyttäjille
        if (is_user_logged_in()) {
            return $output;
        }

        // Luo välimuistiavain
        $cache_key = self::generate_cache_key('sidebar', $name);

        // Tarkista välimuisti
        $cached_sidebar = wp_cache_get($cache_key, 'tonys_theme_cache');
        if ($cached_sidebar !== false) {
            return $cached_sidebar;
        }

        // Suorita sivupalkin haku ja tallenna välimuistiin
        ob_start();
        dynamic_sidebar($name);
        $sidebar = ob_get_clean();

        wp_cache_set($cache_key, $sidebar, 'tonys_theme_cache', HOUR_IN_SECONDS);

        return $sidebar;
    }

    /**
     * Tyhjennä widget-välimuisti
     */
    public static function clear_widget_cache() {
        wp_cache_delete_group('tonys_theme_cache');
    }

    /**
     * Lisää admin-valikko
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'themes.php',
            'Välimuistin optimointi',
            'Välimuisti',
            'manage_options',
            'tonys-cache-optimizer',
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Rekisteröi asetukset
     */
    public static function register_settings() {
        register_setting('tonys_theme_cache_options', 'tonys_theme_enable_page_cache');
        register_setting('tonys_theme_cache_options', 'tonys_theme_enable_query_cache');
        register_setting('tonys_theme_cache_options', 'tonys_theme_enable_menu_cache');
        register_setting('tonys_theme_cache_options', 'tonys_theme_enable_widget_cache');
        register_setting('tonys_theme_cache_options', 'tonys_theme_page_cache_ttl');
        register_setting('tonys_theme_cache_options', 'tonys_theme_enable_html_optimization');
    }

    /**
     * Näytä admin-sivu
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Tallenna asetukset
        if (isset($_POST['tonys_theme_cache_options'])) {
            check_admin_referer('tonys_theme_cache_options');
            
            update_option('tonys_theme_enable_page_cache', 
                isset($_POST['tonys_theme_enable_page_cache']) ? 1 : 0);
            update_option('tonys_theme_enable_query_cache', 
                isset($_POST['tonys_theme_enable_query_cache']) ? 1 : 0);
            update_option('tonys_theme_enable_menu_cache', 
                isset($_POST['tonys_theme_enable_menu_cache']) ? 1 : 0);
            update_option('tonys_theme_enable_widget_cache', 
                isset($_POST['tonys_theme_enable_widget_cache']) ? 1 : 0);
            update_option('tonys_theme_page_cache_ttl', 
                absint($_POST['tonys_theme_page_cache_ttl']));
            update_option('tonys_theme_enable_html_optimization', 
                isset($_POST['tonys_theme_enable_html_optimization']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>Asetukset tallennettu!</p></div>';
        }

        // Tyhjennä välimuisti
        if (isset($_POST['clear_all_cache'])) {
            check_admin_referer('clear_all_cache');
            wp_cache_flush();
            echo '<div class="notice notice-success"><p>Välimuisti tyhjennetty!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Välimuistin optimointi</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('tonys_theme_cache_options'); ?>
                <input type="hidden" name="tonys_theme_cache_options" value="1">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Sivuvälimuisti</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_enable_page_cache" value="1" 
                                    <?php checked(1, get_option('tonys_theme_enable_page_cache', true), true); ?>>
                                Käytä sivuvälimuistitusta
                            </label>
                            <p class="description">
                                Tallentaa kokonaiset sivut välimuistiin. Nopeuttaa sivujen latautumista huomattavasti.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Kyselyvälimuisti</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_enable_query_cache" value="1" 
                                    <?php checked(1, get_option('tonys_theme_enable_query_cache', true), true); ?>>
                                Käytä kyselyvälimuistitusta
                            </label>
                            <p class="description">
                                Tallentaa tietokantakyselyt välimuistiin. Vähentää tietokantakuormitusta.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Valikkovälimuisti</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_enable_menu_cache" value="1" 
                                    <?php checked(1, get_option('tonys_theme_enable_menu_cache', true), true); ?>>
                                Käytä valikkovälimuistitusta
                            </label>
                            <p class="description">
                                Tallentaa navigaatiovalikot välimuistiin. Nopeuttaa valikoiden latautumista.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Widget-välimuisti</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_enable_widget_cache" value="1" 
                                    <?php checked(1, get_option('tonys_theme_enable_widget_cache', true), true); ?>>
                                Käytä widget-välimuistitusta
                            </label>
                            <p class="description">
                                Tallentaa sivupalkkien widgetit välimuistiin. Nopeuttaa sivupalkkien latautumista.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sivuvälimuistin vanhenemisaika</th>
                        <td>
                            <input type="number" name="tonys_theme_page_cache_ttl" value="<?php echo get_option('tonys_theme_page_cache_ttl', HOUR_IN_SECONDS); ?>">
                            <p class="description">
                                Määrittää, kuinka kauan sivuvälimuisti säilyy voimassa. Arvo on sekunteina.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">HTML-optimointi</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tonys_theme_enable_html_optimization" value="1" 
                                    <?php checked(1, get_option('tonys_theme_enable_html_optimization', true), true); ?>>
                                Käytä HTML-optimointia
                            </label>
                            <p class="description">
                                Poistaa turhat välilyönnit ja rivinvaihdot HTML-koodista. Nopeuttaa sivujen latautumista.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Tallenna asetukset'); ?>
            </form>

            <h2>Välimuistin hallinta</h2>
            <form method="post" action="">
                <?php wp_nonce_field('clear_all_cache'); ?>
                <p>
                    Tyhjennä kaikki välimuistit jos huomaat ongelmia sivuston toiminnassa.
                    Tämä tyhjentää:
                </p>
                <ul>
                    <li>Sivuvälimuistin</li>
                    <li>Kyselyvälimuistin</li>
                    <li>Valikkovälimuistin</li>
                    <li>Widget-välimuistin</li>
                </ul>
                <?php submit_button('Tyhjennä kaikki välimuistit', 'secondary', 'clear_all_cache'); ?>
            </form>

            <h2>Välimuistin tilastot</h2>
            <?php
            $stats = wp_cache_get_stats();
            if ($stats) {
                echo '<table class="widefat">';
                echo '<thead><tr><th>Mittari</th><th>Arvo</th></tr></thead>';
                echo '<tbody>';
                foreach ($stats as $key => $value) {
                    echo '<tr>';
                    echo '<td>' . esc_html($key) . '</td>';
                    echo '<td>' . esc_html($value) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p>Välimuistitilastoja ei ole saatavilla.</p>';
            }
            ?>
        </div>
        <?php
    }

    private static $cache_version = '1.0';
    private static $max_cache_size = 100 * 1024 * 1024; // 100MB
    private static $cache_stats = [];

}

// Alusta välimuistin optimointi
TonysTheme_Cache_Optimizer::init();
