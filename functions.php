<?php
/**
 * Teeman toiminnot ja määritykset
 *
 * @package TonysTheme
 */

if (!defined('ABSPATH')) {
    exit; // Estetään suora pääsy tiedostoon
}

/**
 * Teeman asetusten määritys
 */
function tonys_theme_setup() {
    // Lisää automaattinen RSS-syötteiden tuki
    add_theme_support('automatic-feed-links');

    // Anna WordPressin hallita sivun otsikkoa
    add_theme_support('title-tag');

    // Ota käyttöön artikkelikuvien tuki
    add_theme_support('post-thumbnails');

    // Gutenberg-editorin laaja tuki
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');
    add_theme_support('custom-line-height');
    add_theme_support('custom-spacing');
    add_theme_support('custom-units');
    add_theme_support('appearance-tools');
    
    // Block editor -väripaletti
    add_theme_support('editor-color-palette', array(
        array(
            'name'  => __('Pääväri', 'tonys-theme'),
            'slug'  => 'primary',
            'color' => '#333333',
        ),
        array(
            'name'  => __('Korostusväri', 'tonys-theme'),
            'slug'  => 'accent',
            'color' => '#0073aa',
        ),
        array(
            'name'  => __('Vaalea', 'tonys-theme'),
            'slug'  => 'light',
            'color' => '#ffffff',
        ),
        array(
            'name'  => __('Tumma', 'tonys-theme'),
            'slug'  => 'dark',
            'color' => '#1a1a1a',
        ),
    ));

    // Block editor -fonttikoot
    add_theme_support('editor-font-sizes', array(
        array(
            'name' => __('Pieni', 'tonys-theme'),
            'slug' => 'small',
            'size' => 14,
        ),
        array(
            'name' => __('Normaali', 'tonys-theme'),
            'slug' => 'normal',
            'size' => 16,
        ),
        array(
            'name' => __('Keskikokoinen', 'tonys-theme'),
            'slug' => 'medium',
            'size' => 20,
        ),
        array(
            'name' => __('Suuri', 'tonys-theme'),
            'slug' => 'large',
            'size' => 24,
        ),
    ));

    // Rekisteröi navigointivalikot
    register_nav_menus(array(
        'primary'      => __('Päävalikko', 'tonys-theme'),
        'footer'       => __('Alatunnisteen valikko', 'tonys-theme'),
        'social'       => __('Some-valikko', 'tonys-theme'),
        'mobile'       => __('Mobiilivalikko', 'tonys-theme'),
    ));

    // Lisää kielitiedostojen tuki
    load_theme_textdomain('tonys-theme', get_template_directory() . '/languages');
    
    // Add support for child themes
    add_theme_support('child-theme-stylesheet');
    
    // Add support for custom backgrounds
    add_theme_support('custom-background', array(
        'default-color' => 'ffffff',
        'default-image' => '',
    ));
    
    // Add support for custom headers
    add_theme_support('custom-header', array(
        'default-image'          => '',
        'width'                  => 1920,
        'height'                 => 400,
        'flex-height'            => true,
        'flex-width'             => true,
        'uploads'                => true,
        'random-default'         => false,
        'header-text'            => true,
        'default-text-color'     => '000000',
    ));
}
add_action('after_setup_theme', 'tonys_theme_setup');

// Add editor styles
function tonys_theme_add_editor_styles() {
    add_theme_support('editor-styles');
    add_editor_style('assets/css/block-editor.css');
}
add_action('after_setup_theme', 'tonys_theme_add_editor_styles');

/**
 * Gutenberg-tuen lisääminen
 */
function tonys_theme_gutenberg_support() {
    // Lisää teematuki
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('custom-spacing');
    
    // Rekisteröi Gutenberg-tyylit
    add_editor_style('assets/css/blocks.css');
    
    // Rekisteröi tyylit julkiselle puolelle
    wp_enqueue_style(
        'tonys-theme-blocks',
        get_template_directory_uri() . '/assets/css/blocks.css',
        array(),
        wp_get_theme()->get('Version')
    );
}
add_action('after_setup_theme', 'tonys_theme_gutenberg_support');

/**
 * Tyylitiedostojen ja skriptien lataus
 */
function tonys_theme_scripts() {
    // Get theme version for cache busting
    $theme_version = wp_get_theme()->get('Version');
    
    // Pää-tyylitiedosto
    wp_enqueue_style(
        'tonys-theme-style',
        get_stylesheet_uri(),
        array(),
        $theme_version
    );

    // Custom fonts
    wp_enqueue_style(
        'tonys-theme-fonts',
        get_theme_file_uri('assets/fonts/fonts.css'),
        array(),
        $theme_version
    );

    // Block editor -tyylit
    wp_enqueue_style(
        'tonys-theme-block-editor',
        get_theme_file_uri('/assets/css/blocks.css'),
        array(),
        $theme_version
    );

    // Google Fonts
    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Montserrat:wght@400;500;700&display=swap',
        array(),
        null
    );

    // Pää-JavaScript
    wp_enqueue_script(
        'tonys-theme-scripts',
        get_theme_file_uri('/assets/js/main.js'),
        array('jquery'),
        $theme_version,
        true
    );

    // Navigaatio-JavaScript
    wp_enqueue_script(
        'tonys-theme-navigation',
        get_theme_file_uri('assets/js/mobile-menu.min.js'),
        array('jquery'),
        $theme_version,
        true
    );

    // Localize script
    wp_localize_script('tonys-theme-navigation', 'tonysThemeL10n', array(
        'expandMenu' => esc_html__('Avaa valikko', 'tonys-theme'),
        'collapseMenu' => esc_html__('Sulje valikko', 'tonys-theme'),
        'ajaxurl' => esc_url(admin_url('admin-ajax.php')),
        'nonce' => wp_create_nonce('tonys-theme-nonce')
    ));

    // Kommenttien vastausskripti
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    // Enqueue theme JavaScript
    wp_enqueue_script(
        'tonys-theme-script',
        get_template_directory_uri() . '/assets/js/theme.js',
        array(), // No dependencies
        filemtime(get_template_directory() . '/assets/js/theme.js'), // Version based on file modification time
        true // Load in footer
    );
}
add_action('wp_enqueue_scripts', 'tonys_theme_scripts');

/**
 * Block editor -tyylien lataus
 */
function tonys_theme_block_editor_styles() {
    wp_enqueue_style(
        'tonys-theme-block-editor-styles',
        get_theme_file_uri('/assets/css/block-editor.css'),
        array(),
        wp_get_theme()->get('Version')
    );
}
add_action('enqueue_block_editor_assets', 'tonys_theme_block_editor_styles');

/**
 * Vimpainten rekisteröinti
 */
function tonys_theme_widgets_init() {
    // Pääsivupalkki
    register_sidebar(array(
        'name'          => __('Pääsivupalkki', 'tonys-theme'),
        'id'            => 'sidebar-main',
        'description'   => __('Lisää vimpaimia pääsivupalkkiin.', 'tonys-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    // Blogin sivupalkki
    register_sidebar(array(
        'name'          => __('Blogin sivupalkki', 'tonys-theme'),
        'id'            => 'sidebar-blog',
        'description'   => __('Näytetään blogiartikkeleiden yhteydessä.', 'tonys-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    // Footer-vimpainalueet
    $footer_widget_areas = 4;
    for ($i = 1; $i <= $footer_widget_areas; $i++) {
        register_sidebar(array(
            'name'          => sprintf(__('Footer %d', 'tonys-theme'), $i),
            'id'            => 'footer-' . $i,
            'description'   => sprintf(__('Footer-vimpainalue %d', 'tonys-theme'), $i),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));
    }

    // WooCommerce-sivupalkki (jos WooCommerce on asennettu)
    if (class_exists('WooCommerce')) {
        register_sidebar(array(
            'name'          => __('Verkkokaupan sivupalkki', 'tonys-theme'),
            'id'            => 'sidebar-shop',
            'description'   => __('Näytetään verkkokaupan sivuilla.', 'tonys-theme'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ));
    }
}
add_action('widgets_init', 'tonys_theme_widgets_init');

/**
 * Mukautetut navigointivalikkojen callback-funktiot
 */
function tonys_theme_primary_menu($args) {
    $args = array_merge($args, array(
        'fallback_cb'     => 'tonys_theme_primary_menu_fallback',
        'container_class' => 'primary-menu-container',
        'menu_class'      => 'primary-menu',
        'depth'           => 3,
        'walker'          => new Tonys_Theme_Walker_Nav_Menu(),
    ));
    return $args;
}
add_filter('wp_nav_menu_args', 'tonys_theme_primary_menu');

function tonys_theme_primary_menu_fallback() {
    if (current_user_can('edit_theme_options')) {
        printf(
            '<div class="menu-fallback-notice"><p>%s</p></div>',
            sprintf(
                /* translators: %s: URL to create a new menu */
                __('Lisää <a href="%s">tästä</a> uusi valikko.', 'tonys-theme'),
                esc_url(admin_url('nav-menus.php'))
            )
        );
    }
}

/**
 * Mukautettu Walker-luokka valikoille
 */
class Tonys_Theme_Walker_Nav_Menu extends Walker_Nav_Menu {
    public function start_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul class=\"sub-menu depth-$depth\">\n";
    }

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $indent = str_repeat("\t", $depth);
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        
        if ($args->walker->has_children) {
            $classes[] = 'has-children';
        }

        $class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $output .= $indent . '<li' . $class_names . '>';

        $atts = array();
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        $atts['rel']    = !empty($item->xfn) ? $item->xfn : '';
        $atts['href']   = !empty($item->url) ? $item->url : '';

        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $title = apply_filters('the_title', $item->title, $item->ID);
        $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

        $item_output = $args->before;
        $item_output .= '<a' . $attributes . '>';
        $item_output .= $args->link_before . $title . $args->link_after;
        $item_output .= '</a>';
        $item_output .= $args->after;

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
}

/**
 * Tietoturvaparannukset
 */
function tonys_theme_security_headers() {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    add_filter('the_generator', '__return_empty_string');
}
add_action('init', 'tonys_theme_security_headers');

/**
 * Excerpt-pituuden muokkaus
 */
function tonys_theme_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'tonys_theme_excerpt_length');

/**
 * Register custom blocks
 */
function tonys_theme_register_blocks() {
    // Register affiliate button block
    register_block_type(__DIR__ . '/blocks/src/affiliate-button');
    
    // Register product comparison block
    register_block_type(__DIR__ . '/blocks/src/product-comparison');
}
add_action('init', 'tonys_theme_register_blocks');

/**
 * Add affiliate styles
 */
function tonys_theme_affiliate_styles() {
    wp_enqueue_style(
        'tonys-theme-affiliate-styles',
        get_template_directory_uri() . '/assets/css/affiliate.css',
        array(),
        filemtime(get_template_directory() . '/assets/css/affiliate.css')
    );
}
add_action('wp_enqueue_scripts', 'tonys_theme_affiliate_styles');
add_action('enqueue_block_editor_assets', 'tonys_theme_affiliate_styles');

/**
 * Automatically add affiliate tags to external links
 */
function tonys_theme_add_affiliate_tags($content) {
    if (empty($content)) return $content;
        
    // Cache affiliate domains and IDs
    static $affiliate_data = null;
    if ($affiliate_data === null) {
        $affiliate_data = array(
            'amazon.' => array(
                'tag' => 'affiliate-20',
                'param' => 'tag'
            ),
            'booking.' => array(
                'tag' => 'affiliate123',
                'param' => 'aid'
            ),
            // Add more affiliate programs here
        );
    }
        
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
        
    $links = $dom->getElementsByTagName('a');
    $modified = false;
        
    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        if (empty($href) || strpos($href, '#') === 0) continue;
            
        foreach ($affiliate_data as $domain => $data) {
            if (strpos($href, $domain) !== false) {
                // Parse URL and query string
                $url_parts = parse_url($href);
                $query = array();
                if (isset($url_parts['query'])) {
                    parse_str($url_parts['query'], $query);
                }
                    
                // Add or update affiliate parameter
                if (!isset($query[$data['param']]) || $query[$data['param']] !== $data['tag']) {
                    $query[$data['param']] = $data['tag'];
                    $modified = true;
                        
                    // Rebuild URL
                    $url_parts['query'] = http_build_query($query);
                    $new_url = tonys_theme_build_url($url_parts);
                    $link->setAttribute('href', $new_url);
                        
                    // Add rel attributes for compliance
                    $rel = $link->getAttribute('rel');
                    $rel_values = array_unique(array_merge(
                        $rel ? explode(' ', $rel) : array(),
                        array('nofollow', 'sponsored', 'external')
                    ));
                    $link->setAttribute('rel', implode(' ', $rel_values));
                        
                    // Add target attribute
                    $link->setAttribute('target', '_blank');
                }
                break;
            }
        }
    }
        
    return $modified ? $dom->saveHTML() : $content;
}
add_filter('the_content', 'tonys_theme_add_affiliate_tags', 999);
add_filter('widget_text', 'tonys_theme_add_affiliate_tags', 999);

/**
 * Helper function to rebuild URL
 */
function tonys_theme_build_url($parts) {
    $scheme   = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
    $host     = isset($parts['host']) ? $parts['host'] : '';
    $port     = isset($parts['port']) ? ':' . $parts['port'] : '';
    $user     = isset($parts['user']) ? $parts['user'] : '';
    $pass     = isset($parts['pass']) ? ':' . $parts['pass']  : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = isset($parts['path']) ? $parts['path'] : '';
    $query    = isset($parts['query']) ? '?' . $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
    return "$scheme$user$pass$host$port$path$query$fragment";
}

/**
 * Analytics and Tracking Settings
 */
class TonysThemeAnalytics {
    // Analytics IDs
    private static $gtm_id = ''; // GTM-XXXXXX
    private static $ga_id = '';  // G-XXXXXXXXXX or UA-XXXXXX-X

    // Feature flags
    private static $enable_gtm = false;
    private static $enable_ga = false;

    /**
     * Initialize analytics
     */
    public static function init() {
        // Enable features based on the presence of IDs
        self::$enable_gtm = !empty(self::$gtm_id);
        self::$enable_ga = !empty(self::$ga_id);

        // Add tracking codes to appropriate hooks
        if (self::$enable_gtm) {
            add_action('wp_head', array(__CLASS__, 'gtm_head'), 1);
            add_action('wp_body_open', array(__CLASS__, 'gtm_body'), 1);
        }

        if (self::$enable_ga && !self::$enable_gtm) {
            add_action('wp_head', array(__CLASS__, 'ga_head'), 1);
        }
    }

    /**
     * GTM head code
     */
    public static function gtm_head() {
        if (!self::should_track()) {
            return;
        }
        ?>
<!-- Google Tag Manager -->
<script>
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js(self::$gtm_id); ?>');
</script>
<!-- End Google Tag Manager -->
        <?php
    }

    /**
     * GTM body code (noscript fallback)
     */
    public static function gtm_body() {
        if (!self::should_track()) {
            return;
        }
        ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr(self::$gtm_id); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
        <?php
    }

    /**
     * GA4 head code (only used if GTM is not enabled)
     */
    public static function ga_head() {
        if (!self::should_track()) {
            return;
        }
        ?>
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr(self::$ga_id); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?php echo esc_js(self::$ga_id); ?>', {
    'anonymize_ip': true,
    'cookie_flags': 'secure;samesite=strict'
});
</script>
<!-- End Google Analytics -->
        <?php
    }

    /**
     * Check if tracking should be enabled
     */
    private static function should_track() {
        // Don't track admin users or when in customizer preview
        if (is_admin() || is_user_logged_in() || is_customize_preview()) {
            return false;
        }

        // Don't track if Do Not Track header is present
        if (isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] === '1') {
            return false;
        }

        // Check if user has given consent (if you're using a cookie consent plugin)
        if (function_exists('has_analytics_consent') && !has_analytics_consent()) {
            return false;
        }

        return true;
    }

    /**
     * Update tracking IDs
     */
    public static function update_tracking_ids($gtm_id = '', $ga_id = '') {
        self::$gtm_id = sanitize_text_field($gtm_id);
        self::$ga_id = sanitize_text_field($ga_id);
        self::init();
    }
}

// Add theme customizer settings for analytics
function tonys_theme_customizer_analytics($wp_customize) {
    // Analytics section
    $wp_customize->add_section('tonys_theme_analytics', array(
        'title'    => __('Analytics-asetukset', 'tonys-theme'),
        'priority' => 120,
    ));

    // GTM ID
    $wp_customize->add_setting('gtm_id', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage'
    ));

    $wp_customize->add_control('gtm_id', array(
        'label'       => __('Google Tag Manager ID', 'tonys-theme'),
        'section'     => 'tonys_theme_analytics',
        'type'        => 'text',
        'description' => __('Esim. GTM-XXXXXX', 'tonys-theme')
    ));

    // GA4 ID
    $wp_customize->add_setting('ga4_id', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage'
    ));

    $wp_customize->add_control('ga4_id', array(
        'label'       => __('Google Analytics 4 ID', 'tonys-theme'),
        'section'     => 'tonys_theme_analytics',
        'type'        => 'text',
        'description' => __('Esim. G-XXXXXXXXXX', 'tonys-theme')
    ));
}
add_action('customize_register', 'tonys_theme_customizer_analytics');

// Initialize analytics with your tracking IDs
TonysThemeAnalytics::update_tracking_ids(
    get_theme_mod('gtm_id', ''), // Get from theme customizer
    get_theme_mod('ga4_id', '')  // Get from theme customizer
);

/**
 * Performance Optimizations
 */
class TonysThemePerformance {
    /**
     * Initialize performance optimizations
     */
    public static function init() {
        // Add lazy loading
        add_filter('wp_get_attachment_image_attributes', array(__CLASS__, 'add_lazy_loading'), 10, 3);
        add_filter('the_content', array(__CLASS__, 'add_lazy_loading_to_content'), 99);
        
        // Remove unnecessary features
        self::cleanup_head();
        
        // Optimize script loading
        add_action('wp_enqueue_scripts', array(__CLASS__, 'optimize_scripts'), 999);
        
        // Add preload for critical assets
        add_action('wp_head', array(__CLASS__, 'add_preload_tags'), 1);
        
        // Disable emojis
        self::disable_emojis();
        
        // Lisää välimuistin hallinta
        add_action('init', [__CLASS__, 'setup_cache_control']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'version_assets']);
        add_filter('wp_handle_upload', [__CLASS__, 'optimize_uploaded_image']);
        add_action('wp_head', [__CLASS__, 'add_critical_css'], 1);
    }

    /**
     * Add lazy loading to images
     */
    public static function add_lazy_loading($attributes, $attachment, $size) {
        if (!is_admin()) {
            $attributes['loading'] = 'lazy';
            
            // Add decoding attribute for better performance
            $attributes['decoding'] = 'async';
            
            // Add fetchpriority to hero images
            if (isset($attributes['class']) && strpos($attributes['class'], 'hero-image') !== false) {
                $attributes['fetchpriority'] = 'high';
            }
        }
        return $attributes;
    }

    /**
     * Add lazy loading to content images and iframes
     */
    public static function add_lazy_loading_to_content($content) {
        if (is_admin()) {
            return $content;
        }

        // Add lazy loading to images
        $content = preg_replace('/<img(.*?)>/', '<img$1 loading="lazy" decoding="async">', $content);
        
        // Add lazy loading to iframes
        $content = preg_replace('/<iframe(.*?)>/', '<iframe$1 loading="lazy">', $content);

        return $content;
    }

    /**
     * Clean up WordPress head
     */
    private static function cleanup_head() {
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        add_filter('the_generator', '__return_empty_string');
    }

    /**
     * Optimize script loading
     */
    public static function optimize_scripts() {
        if (!is_admin()) {
            // Move jQuery to footer
            wp_scripts()->add_data('jquery', 'group', 1);
            wp_scripts()->add_data('jquery-core', 'group', 1);
            wp_scripts()->add_data('jquery-migrate', 'group', 1);

            // Add defer to non-critical scripts
            global $wp_scripts;
            foreach ($wp_scripts->registered as $handle => $script) {
                // Skip jQuery and other critical scripts
                if (in_array($handle, array('jquery', 'jquery-core', 'jquery-migrate'))) {
                    continue;
                }
                
                // Add defer attribute
                $script->extra['defer'] = true;
            }
        }
    }

    /**
     * Add preload tags for critical assets
     */
    public static function add_preload_tags() {
        // Preload main stylesheet
        $style_path = get_template_directory_uri() . '/style.min.css';
        echo '<link rel="preload" href="' . esc_url($style_path) . '" as="style">';
        
        // Preload web fonts
        echo '<link rel="preload" href="' . esc_url(get_template_directory_uri() . '/assets/fonts/montserrat.woff2') . '" as="font" type="font/woff2" crossorigin>';
        
        // DNS prefetch for external resources
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
        echo '<link rel="dns-prefetch" href="//www.google-analytics.com">';
    }

    /**
     * Disable emoji support
     */
    private static function disable_emojis() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        
        // Remove emoji from TinyMCE
        add_filter('tiny_mce_plugins', function($plugins) {
            return array_diff($plugins, array('wpemoji'));
        });
    }
    
    /**
     * Lisää välimuistin hallinta
     */
    public static function setup_cache_control() {
        if (!is_admin()) {
            header('Cache-Control: public, max-age=31536000');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            header('Vary: Accept-Encoding');
        }
    }

    /**
     * Lisää versiointi staattisille resursseille
     */
    public static function version_assets() {
        $version = wp_get_theme()->get('Version');
        wp_style_add_data('tonys-theme-style', 'version', $version);
        wp_script_add_data('tonys-theme-script', 'version', $version);
    }

    /**
     * Optimoi ladatut kuvat
     */
    public static function optimize_uploaded_image($file) {
        if (strpos($file['type'], 'image') === false) {
            return $file;
        }

        // Pakkaa kuva
        $image = wp_get_image_editor($file['file']);
        if (!is_wp_error($image)) {
            $image->set_quality(85);
            $image->save($file['file']);

            // Luo WebP-versio
            $webp_file = pathinfo($file['file'], PATHINFO_DIRNAME) . '/' . 
                        pathinfo($file['file'], PATHINFO_FILENAME) . '.webp';
            $image->save($webp_file, 'image/webp');
        }

        return $file;
    }

    /**
     * Lisää kriittinen CSS
     */
    public static function add_critical_css() {
        $critical_css = '';
        if (is_front_page()) {
            $critical_css = file_get_contents(get_template_directory() . '/assets/css/critical-home.css');
        } elseif (is_single()) {
            $critical_css = file_get_contents(get_template_directory() . '/assets/css/critical-single.css');
        } elseif (is_archive()) {
            $critical_css = file_get_contents(get_template_directory() . '/assets/css/critical-archive.css');
        }

        if ($critical_css) {
            echo '<style id="critical-css">' . $critical_css . '</style>';
        }
    }
}
// Initialize performance optimizations
TonysThemePerformance::init();

/**
 * Affiliate Products Custom Post Type
 */
class TonysThemeAffiliateProducts {
    // Post type and taxonomy names
    const POST_TYPE = 'affiliate_product';
    const CATEGORY_TAX = 'product_category';
    const TAG_TAX = 'product_tag';
    
    /**
     * Initialize the affiliate products system
     */
    public static function init() {
        // Register post type and taxonomies
        add_action('init', array(__CLASS__, 'register_post_type'));
        add_action('init', array(__CLASS__, 'register_taxonomies'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post', array(__CLASS__, 'save_meta_box_data'));
        
        // Add schema markup
        add_action('wp_head', array(__CLASS__, 'output_schema_markup'));
        
        // Register shortcodes
        add_shortcode('product_rating', array(__CLASS__, 'rating_shortcode'));
        
        // Add columns to admin
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array(__CLASS__, 'add_admin_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array(__CLASS__, 'manage_admin_columns'), 10, 2);
    }
    
    /**
     * Register the custom post type
     */
    public static function register_post_type() {
        $labels = array(
            'name'               => __('Affiliate Products', 'tonys-theme'),
            'singular_name'      => __('Affiliate Product', 'tonys-theme'),
            'menu_name'          => __('Affiliate Products', 'tonys-theme'),
            'add_new'           => __('Add New Product', 'tonys-theme'),
            'add_new_item'      => __('Add New Product', 'tonys-theme'),
            'edit_item'         => __('Edit Product', 'tonys-theme'),
            'new_item'          => __('New Product', 'tonys-theme'),
            'view_item'         => __('View Product', 'tonys-theme'),
            'search_items'      => __('Search Products', 'tonys-theme'),
            'not_found'         => __('No products found', 'tonys-theme'),
            'not_found_in_trash'=> __('No products found in trash', 'tonys-theme')
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'publicly_queryable'  => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true, // Enable Gutenberg editor
            'menu_icon'          => 'dashicons-cart',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'rewrite'           => array('slug' => 'products'),
            'menu_position'     => 5,
            'capability_type'   => 'post'
        );

        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Register taxonomies
     */
    public static function register_taxonomies() {
        // Product Categories
        register_taxonomy(
            self::CATEGORY_TAX,
            self::POST_TYPE,
            array(
                'labels' => array(
                    'name'              => __('Product Categories', 'tonys-theme'),
                    'singular_name'     => __('Product Category', 'tonys-theme'),
                    'search_items'      => __('Search Categories', 'tonys-theme'),
                    'all_items'         => __('All Categories', 'tonys-theme'),
                    'parent_item'       => __('Parent Category', 'tonys-theme'),
                    'parent_item_colon' => __('Parent Category:', 'tonys-theme'),
                    'edit_item'         => __('Edit Category', 'tonys-theme'),
                    'update_item'       => __('Update Category', 'tonys-theme'),
                    'add_new_item'      => __('Add New Category', 'tonys-theme'),
                    'new_item_name'     => __('New Category Name', 'tonys-theme'),
                    'menu_name'         => __('Categories', 'tonys-theme'),
                ),
                'hierarchical'      => true,
                'show_ui'          => true,
                'show_in_rest'     => true,
                'show_admin_column'=> true,
                'query_var'        => true,
                'rewrite'          => array('slug' => 'product-category'),
            )
        );

        // Product Tags
        register_taxonomy(
            self::TAG_TAX,
            self::POST_TYPE,
            array(
                'labels' => array(
                    'name'              => __('Product Tags', 'tonys-theme'),
                    'singular_name'     => __('Product Tag', 'tonys-theme'),
                    'search_items'      => __('Search Tags', 'tonys-theme'),
                    'all_items'         => __('All Tags', 'tonys-theme'),
                    'edit_item'         => __('Edit Tag', 'tonys-theme'),
                    'update_item'       => __('Update Tag', 'tonys-theme'),
                    'add_new_item'      => __('Add New Tag', 'tonys-theme'),
                    'new_item_name'     => __('New Tag Name', 'tonys-theme'),
                    'menu_name'         => __('Tags', 'tonys-theme'),
                ),
                'hierarchical'      => false,
                'show_ui'          => true,
                'show_in_rest'     => true,
                'show_admin_column'=> true,
                'query_var'        => true,
                'rewrite'          => array('slug' => 'product-tag'),
            )
        );
    }
    
    /**
     * Add meta boxes
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'product_details',
            __('Product Details', 'tonys-theme'),
            array(__CLASS__, 'render_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }
    
    /**
     * Render meta box content
     */
    public static function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('product_meta_box', 'product_meta_box_nonce');

        // Get existing values
        $rating = get_post_meta($post->ID, '_product_rating', true);
        $price = get_post_meta($post->ID, '_product_price', true);
        $affiliate_url = get_post_meta($post->ID, '_affiliate_url', true);
        $pros = get_post_meta($post->ID, '_product_pros', true);
        $cons = get_post_meta($post->ID, '_product_cons', true);
        ?>
        <div class="product-meta-box">
            <p>
                <label for="product_rating"><?php _e('Rating (1-5)', 'tonys-theme'); ?></label>
                <select name="product_rating" id="product_rating">
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php selected($rating, $i); ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </p>
            <p>
                <label for="product_price"><?php _e('Price', 'tonys-theme'); ?></label>
                <input type="text" id="product_price" name="product_price" value="<?php echo esc_attr($price); ?>">
            </p>
            <p>
                <label for="affiliate_url"><?php _e('Affiliate URL', 'tonys-theme'); ?></label>
                <input type="url" id="affiliate_url" name="affiliate_url" value="<?php echo esc_url($affiliate_url); ?>" class="widefat">
            </p>
            <div class="pros-cons">
                <div class="pros">
                    <label for="product_pros"><?php _e('Pros (one per line)', 'tonys-theme'); ?></label>
                    <textarea id="product_pros" name="product_pros" rows="5" class="widefat"><?php echo esc_textarea($pros); ?></textarea>
                </div>
                <div class="cons">
                    <label for="product_cons"><?php _e('Cons (one per line)', 'tonys-theme'); ?></label>
                    <textarea id="product_cons" name="product_cons" rows="5" class="widefat"><?php echo esc_textarea($cons); ?></textarea>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public static function save_meta_box_data($post_id) {
        // Security checks
        if (!isset($_POST['product_meta_box_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['product_meta_box_nonce'], 'product_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Save data
        if (isset($_POST['product_rating'])) {
            update_post_meta($post_id, '_product_rating', intval($_POST['product_rating']));
        }
        if (isset($_POST['product_price'])) {
            update_post_meta($post_id, '_product_price', sanitize_text_field($_POST['product_price']));
        }
        if (isset($_POST['affiliate_url'])) {
            update_post_meta($post_id, '_affiliate_url', esc_url_raw($_POST['affiliate_url']));
        }
        if (isset($_POST['product_pros'])) {
            update_post_meta($post_id, '_product_pros', sanitize_textarea_field($_POST['product_pros']));
        }
        if (isset($_POST['product_cons'])) {
            update_post_meta($post_id, '_product_cons', sanitize_textarea_field($_POST['product_cons']));
        }
    }
    
    /**
     * Output schema markup
     */
    public static function output_schema_markup() {
        if (!is_singular(self::POST_TYPE)) {
            return;
        }

        global $post;
        $rating = get_post_meta($post->ID, '_product_rating', true);
        $price = get_post_meta($post->ID, '_product_price', true);
        $pros = array_filter(explode("\n", get_post_meta($post->ID, '_product_pros', true)));
        $cons = array_filter(explode("\n", get_post_meta($post->ID, '_product_cons', true)));

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title(),
            'description' => get_the_excerpt(),
            'review' => array(
                '@type' => 'Review',
                'reviewRating' => array(
                    '@type' => 'Rating',
                    'ratingValue' => $rating,
                    'bestRating' => '5'
                ),
                'author' => array(
                    '@type' => 'Person',
                    'name' => get_the_author()
                )
            )
        );

        if (!empty($price)) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => preg_replace('/[^0-9.]/', '', $price),
                'priceCurrency' => 'EUR'
            );
        }

        if (has_post_thumbnail()) {
            $schema['image'] = get_the_post_thumbnail_url(null, 'full');
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
    }
    
    /**
     * Rating shortcode
     */
    public static function rating_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID()
        ), $atts);

        $rating = get_post_meta($atts['id'], '_product_rating', true);
        if (!$rating) {
            return '';
        }

        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= '<span class="star ' . ($i <= $rating ? 'filled' : 'empty') . '">★</span>';
        }

        return sprintf(
            '<div class="product-rating" data-rating="%d">%s</div>',
            $rating,
            $stars
        );
    }
    
    /**
     * Add admin columns
     */
    public static function add_admin_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['rating'] = __('Rating', 'tonys-theme');
        $new_columns['price'] = __('Price', 'tonys-theme');
        $new_columns['categories'] = __('Categories', 'tonys-theme');
        $new_columns['tags'] = __('Tags', 'tonys-theme');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Manage admin columns content
     */
    public static function manage_admin_columns($column, $post_id) {
        switch ($column) {
            case 'rating':
                $rating = get_post_meta($post_id, '_product_rating', true);
                echo $rating ? str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) : '-';
                break;
            case 'price':
                echo esc_html(get_post_meta($post_id, '_product_price', true));
                break;
            case 'categories':
                echo get_the_term_list($post_id, self::CATEGORY_TAX, '', ', ');
                break;
            case 'tags':
                echo get_the_term_list($post_id, self::TAG_TAX, '', ', ');
                break;
        }
    }
}

// Initialize affiliate products
TonysThemeAffiliateProducts::init();

/**
 * Theme Testing and Fixes
 */
class TonysThemeFixes {
    /**
     * Initialize fixes
     */
    public static function init() {
        // Fix responsive images
        add_filter('post_thumbnail_html', array(__CLASS__, 'add_responsive_class'), 10, 3);
        add_filter('the_content', array(__CLASS__, 'fix_responsive_images'));
        
        // Fix Gutenberg alignment
        add_theme_support('align-wide');
        add_theme_support('responsive-embeds');
        
        // Add custom image sizes with proper scaling
        add_image_size('product-thumbnail', 300, 300, true);
        add_image_size('product-large', 800, 600, false);
        
        // Fix mobile menu accessibility
        add_filter('wp_nav_menu_items', array(__CLASS__, 'add_mobile_menu_button'), 10, 2);
        
        // Add responsive tables
        add_filter('the_content', array(__CLASS__, 'wrap_tables'));
        
        // Fix affiliate links on archive pages
        add_filter('the_excerpt', array(__CLASS__, 'process_affiliate_links'));
    }
    
    /**
     * Add responsive class to images
     */
    public static function add_responsive_class($html, $post_id, $post_thumbnail_id) {
        return str_replace('class="', 'class="img-fluid ', $html);
    }
    
    /**
     * Fix responsive images in content
     */
    public static function fix_responsive_images($content) {
        return preg_replace('/<img(.*?)class="(.*?)"(.*?)>/i', '<img$1class="$2 img-fluid"$3>', $content);
    }
    
    /**
     * Add mobile menu button
     */
    public static function add_mobile_menu_button($items, $args) {
        if ($args->theme_location == 'primary') {
            $button = '<button class="mobile-menu-toggle" aria-expanded="false" aria-controls="mobile-menu">';
            $button .= '<span class="screen-reader-text">' . __('Menu', 'tonys-theme') . '</span>';
            $button .= '<span class="menu-icon"></span>';
            $button .= '</button>';
            
            return $button . $items;
        }
        return $items;
    }
    
    /**
     * Wrap tables for responsive display
     */
    public static function wrap_tables($content) {
        return preg_replace('/<table(.*?)>/i', '<div class="table-responsive"><table$1>', $content);
    }
    
    /**
     * Process affiliate links in excerpts
     */
    public static function process_affiliate_links($excerpt) {
        return TonysThemeAffiliateProducts::process_affiliate_links($excerpt);
    }
}

// Initialize fixes
TonysThemeFixes::init();

/**
 * Security Enhancements
 */
class TonysThemeSecurity {
    /**
     * Initialize security features
     */
    public static function init() {
        // Add security headers
        add_action('send_headers', array(__CLASS__, 'add_security_headers'));
        
        // Add rate limiting for forms
        add_action('init', array(__CLASS__, 'setup_rate_limiting'));
        
        // Add CSRF protection
        add_action('init', array(__CLASS__, 'setup_csrf_protection'));
        
        // Clean up WordPress head
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        
        // Disable XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');
        
        // Lisää 2FA-tuki
        add_filter('wp_authenticate_user', [__CLASS__, 'check_2fa'], 10, 2);
        add_action('admin_init', [__CLASS__, 'setup_2fa']);
        add_filter('registration_errors', [__CLASS__, 'check_password_strength'], 10, 3);
    }
    
    /**
     * Add security headers
     */
    public static function add_security_headers() {
        if (!is_admin()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header("Content-Security-Policy: default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; img-src 'self' https: data:;");
        }
    }
    
    /**
     * Setup rate limiting for forms
     */
    public static function setup_rate_limiting() {
        if (!session_id()) {
            session_start();
        }
        
        add_filter('preprocess_comment', array(__CLASS__, 'check_comment_rate_limit'));
        add_action('wp_login_failed', array(__CLASS__, 'track_failed_login'));
        add_filter('authenticate', array(__CLASS__, 'check_login_rate_limit'), 30, 3);
    }
    
    /**
     * Check comment rate limit
     */
    public static function check_comment_rate_limit($commentdata) {
        if (!is_user_logged_in()) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $transient_key = 'comment_rate_' . $ip;
            $attempts = get_transient($transient_key) ?: 0;
            
            if ($attempts > 5) {
                wp_die(esc_html__('Liian monta kommenttia lyhyessä ajassa. Yritä myöhemmin uudelleen.', 'tonys-theme'));
            }
            
            set_transient($transient_key, $attempts + 1, 300);
        }
        return $commentdata;
    }
    
    /**
     * Track failed login attempts
     */
    public static function track_failed_login($username) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $transient_key = 'login_attempts_' . $ip;
        $attempts = get_transient($transient_key) ?: 0;
        set_transient($transient_key, $attempts + 1, 300);
    }
    
    /**
     * Check login rate limit
     */
    public static function check_login_rate_limit($user, $username, $password) {
        if (!empty($username)) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $transient_key = 'login_attempts_' . $ip;
            $attempts = get_transient($transient_key) ?: 0;
            
            if ($attempts > 5) {
                return new WP_Error('too_many_attempts', 
                    esc_html__('Liian monta kirjautumisyritystä. Yritä myöhemmin uudelleen.', 'tonys-theme')
                );
            }
        }
        return $user;
    }
    
    /**
     * Setup CSRF protection
     */
    public static function setup_csrf_protection() {
        if (!is_admin() && !wp_doing_ajax()) {
            add_action('init', array(__CLASS__, 'start_session'));
            add_action('wp_logout', array(__CLASS__, 'end_session'));
        }
    }
    
    /**
     * Start session for CSRF token
     */
    public static function start_session() {
        if (!session_id()) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * End session
     */
    public static function end_session() {
        if (session_id()) {
            session_destroy();
        }
    }
    
    /**
     * 2FA-tuen asetukset
     */
    public static function setup_2fa() {
        if (!class_exists('RNMySQL')) {
            // Lisää Google Authenticator -tuki
            require_once get_template_directory() . '/vendor/phpgangsta/googleauthenticator/GoogleAuthenticator.php';
            
            // Lisää 2FA-asetukset käyttäjäprofiiliin
            add_action('show_user_profile', [__CLASS__, 'add_2fa_fields']);
            add_action('edit_user_profile', [__CLASS__, 'add_2fa_fields']);
            add_action('personal_options_update', [__CLASS__, 'save_2fa_fields']);
            add_action('edit_user_profile_update', [__CLASS__, 'save_2fa_fields']);
        }
    }

    /**
     * Tarkista 2FA-koodi kirjautumisen yhteydessä
     */
    public static function check_2fa($user, $password) {
        if (is_wp_error($user)) {
            return $user;
        }

        // Tarkista onko 2FA käytössä
        $secret = get_user_meta($user->ID, '_2fa_secret', true);
        if (!empty($secret)) {
            $code = isset($_POST['2fa_code']) ? $_POST['2fa_code'] : '';
            
            if (empty($code)) {
                return new WP_Error('2fa_required', __('Please enter your 2FA code', 'innovationmedia-nexus-hub'));
            }

            $ga = new PHPGangsta_GoogleAuthenticator();
            if (!$ga->verifyCode($secret, $code, 2)) {
                return new WP_Error('2fa_invalid', __('Invalid 2FA code', 'innovationmedia-nexus-hub'));
            }
        }

        return $user;
    }

    /**
     * Tarkista salasanan vahvuus
     */
    public static function check_password_strength($errors, $sanitized_user_login, $user_email) {
        if (isset($_POST['pass1']) && !empty($_POST['pass1'])) {
            $password = $_POST['pass1'];
            
            // Tarkista pituus
            if (strlen($password) < 12) {
                $errors->add('password_too_short', 
                    __('Password must be at least 12 characters long', 'innovationmedia-nexus-hub'));
            }

            // Tarkista merkit
            if (!preg_match('/[A-Z]/', $password)) {
                $errors->add('password_no_upper', 
                    __('Password must include at least one uppercase letter', 'innovationmedia-nexus-hub'));
            }
            
            if (!preg_match('/[a-z]/', $password)) {
                $errors->add('password_no_lower', 
                    __('Password must include at least one lowercase letter', 'innovationmedia-nexus-hub'));
            }
            
            if (!preg_match('/[0-9]/', $password)) {
                $errors->add('password_no_number', 
                    __('Password must include at least one number', 'innovationmedia-nexus-hub'));
            }
            
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors->add('password_no_special', 
                    __('Password must include at least one special character', 'innovationmedia-nexus-hub'));
            }

            // Tarkista yleiset salasanat
            $common_passwords = file(get_template_directory() . '/inc/common-passwords.txt', FILE_IGNORE_NEW_LINES);
            if (in_array(strtolower($password), $common_passwords)) {
                $errors->add('password_common', 
                    __('This password is too common. Please choose a stronger password', 'innovationmedia-nexus-hub'));
            }
        }

        return $errors;
    }
}
// Initialize security features
TonysThemeSecurity::init();

/**
 * Virhekirjaus ja monitorointi
 */
class TonysThemeLogger {
    private static $log_dir;
    private static $log_file;
    private static $error_levels = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];

    public static function init() {
        self::$log_dir = WP_CONTENT_DIR . '/logs/tonys-theme';
        self::$log_file = self::$log_dir . '/error.log';

        // Luo lokikansio jos ei ole olemassa
        if (!file_exists(self::$log_dir)) {
            wp_mkdir_p(self::$log_dir);
        }

        // Aseta virheenkäsittelijät
        set_error_handler([__CLASS__, 'handleError']);
        set_exception_handler([__CLASS__, 'handleException']);
        register_shutdown_function([__CLASS__, 'handleFatalError']);

        // Ajasta lokien siivous
        if (!wp_next_scheduled('tonys_theme_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'tonys_theme_cleanup_logs');
        }
        add_action('tonys_theme_cleanup_logs', [__CLASS__, 'cleanupLogs']);

        // REST API endpoint virheille
        add_action('rest_api_init', function() {
            register_rest_route('tonys-theme/v1', '/log-error', [
                'methods' => 'POST',
                'callback' => [__CLASS__, 'handleClientError'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    public static function handleError($errno, $errstr, $errfile, $errline) {
        $error_type = isset(self::$error_levels[$errno]) ? self::$error_levels[$errno] : 'Unknown Error';
        
        $message = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $error_type,
            $errstr,
            $errfile,
            $errline
        );

        self::writeLog($message);

        // Lähetä sähköposti kriittisistä virheistä
        if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR) {
            self::sendErrorNotification($message);
        }

        return false;
    }

    public static function handleException($exception) {
        $message = sprintf(
            "[%s] Uncaught Exception: %s in %s on line %d\n%s\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        self::writeLog($message);
        self::sendErrorNotification($message);
    }

    public static function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR])) {
            self::handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }

    public static function handleClientError($request) {
        $params = $request->get_params();
        
        if (empty($params['message'])) {
            return new WP_Error('invalid_error', 'Error message is required');
        }

        $message = sprintf(
            "[%s] Client Error: %s\nURL: %s\nUser Agent: %s\n",
            date('Y-m-d H:i:s'),
            $params['message'],
            $params['url'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        );

        self::writeLog($message);

        return ['status' => 'success'];
    }

    private static function writeLog($message) {
        error_log($message, 3, self::$log_file);
    }

    private static function sendErrorNotification($message) {
        $to = get_option('admin_email');
        $subject = sprintf('[%s] Critical Error Detected', get_bloginfo('name'));
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        $body = sprintf(
            '<h1>Critical Error Detected</h1>
            <p>A critical error has occurred on your website:</p>
            <pre>%s</pre>
            <p>Please check the error logs for more details.</p>',
            esc_html($message)
        );

        wp_mail($to, $subject, $body, $headers);
    }

    public static function cleanupLogs() {
        if (!file_exists(self::$log_dir)) {
            return;
        }

        // Arkistoi vanhat lokit
        $archive_dir = self::$log_dir . '/archive';
        if (!file_exists($archive_dir)) {
            wp_mkdir_p($archive_dir);
        }

        // Siirrä yli 30 päivää vanhat lokit arkistoon
        $files = glob(self::$log_dir . '/*.log');
        foreach ($files as $file) {
            $file_time = filemtime($file);
            if ($file_time < strtotime('-30 days')) {
                $archive_file = $archive_dir . '/' . date('Y-m', $file_time) . '-' . basename($file);
                rename($file, $archive_file);
            }
        }

        // Poista yli 90 päivää vanhat arkistoidut lokit
        $archive_files = glob($archive_dir . '/*.log');
        foreach ($archive_files as $file) {
            if (filemtime($file) < strtotime('-90 days')) {
                unlink($file);
            }
        }
    }
}

// Alusta virhekirjaus
TonysThemeLogger::init();

/**
 * Schema.org merkinnät
 */
class TonysThemeSchema {
    public static function init() {
        add_action('wp_footer', array(__CLASS__, 'output_schema'));
        add_filter('the_content', array(__CLASS__, 'add_article_schema'));
        add_filter('comment_text', array(__CLASS__, 'add_comment_schema'));
    }
    
    public static function output_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url(),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ),
                'query-input' => 'required name=search_term_string'
            )
        );
        
        if (is_singular()) {
            global $post;
            
            if (is_single()) {
                $schema['@type'] = 'BlogPosting';
            } elseif (is_page()) {
                $schema['@type'] = 'WebPage';
            }
            
            $schema['headline'] = get_the_title();
            $schema['datePublished'] = get_the_date('c');
            $schema['dateModified'] = get_the_modified_date('c');
            
            if (has_post_thumbnail()) {
                $schema['image'] = get_the_post_thumbnail_url(null, 'full');
            }
            
            $schema['author'] = array(
                '@type' => 'Person',
                'name' => get_the_author(),
                'url' => get_author_posts_url(get_the_author_meta('ID'))
            );
        }
        
        printf('<script type="application/ld+json">%s</script>', wp_json_encode($schema));
    }
    
    public static function add_article_schema($content) {
        if (!is_singular('post')) {
            return $content;
        }
        
        // Lisää artikkelimerkinnät
        $content = sprintf(
            '<div itemscope itemtype="https://schema.org/Article">%s</div>',
            $content
        );
        
        return $content;
    }
    
    public static function add_comment_schema($comment_text) {
        // Lisää kommenttimerkinnät
        return sprintf(
            '<div itemscope itemtype="https://schema.org/Comment">%s</div>',
            $comment_text
        );
    }
}

// Alusta schema-merkinnät
TonysThemeSchema::init();

/**
 * Virheenhallinta ja ilmoitukset
 */
class TonysThemeErrors {
    public static function init() {
        // Aseta virheenkäsittelijä
        set_error_handler(array(__CLASS__, 'handleError'));
        
        // Aseta poikkeuskäsittelijä
        set_exception_handler(array(__CLASS__, 'handleException'));
        
        // Lisää admin-ilmoitukset
        add_action('admin_notices', array(__CLASS__, 'checkRequirements'));
    }
    
    /**
     * Tarkista teeman vaatimukset
     */
    public static function checkRequirements() {
        $errors = array();
        
        // Tarkista PHP-versio
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $errors[] = sprintf(
                __('Teema vaatii PHP version 7.4 tai uudemman. Nykyinen versio on %s.', 'tonys-theme'),
                PHP_VERSION
            );
        }
        
        // Tarkista WordPress-versio
        global $wp_version;
        if (version_compare($wp_version, '5.9', '<')) {
            $errors[] = sprintf(
                __('Teema vaatii WordPress version 5.9 tai uudemman. Nykyinen versio on %s.', 'tonys-theme'),
                $wp_version
            );
        }
        
        // Tarkista tarvittavat PHP-laajennukset
        $required_extensions = array('json', 'mbstring', 'xml');
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $errors[] = sprintf(
                    __('Teema vaatii PHP-%s-laajennuksen.', 'tonys-theme'),
                    $ext
                );
            }
        }
        
        // Näytä virheet jos niitä löytyy
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
            }
        }
    }
    
    /**
     * Käsittele PHP-virheet
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $error_message = sprintf(
            'PHP Virhe [%s]: %s tiedostossa %s rivillä %d',
            $errno,
            $errstr,
            $errfile,
            $errline
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($error_message);
        }
        
        // Palauta true jotta PHP:n sisäinen virheenkäsittelijä ei suoriudu
        return true;
    }
    
    /**
     * Käsittele poikkeukset
     */
    public static function handleException($exception) {
        $error_message = sprintf(
            'Poikkeus: %s tiedostossa %s rivillä %d',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($error_message);
        }
        
        // Näytä käyttäjäystävällinen virheilmoitus
        if (!is_admin()) {
            wp_die(
                esc_html__('Pahoittelemme, mutta jotain meni pieleen. Yritä myöhemmin uudelleen.', 'tonys-theme'),
                esc_html__('Virhe', 'tonys-theme'),
                array('response' => 500)
            );
        }
    }
}

// Alusta virheenhallinta
TonysThemeErrors::init();

/**
 * AMP-tuki
 */
class TonysThemeAMP {
    public static function init() {
        add_action('after_setup_theme', [__CLASS__, 'amp_support']);
        add_filter('amp_post_template_file', [__CLASS__, 'amp_template'], 10, 3);
        add_filter('amp_content_sanitizers', [__CLASS__, 'amp_sanitizers']);
    }

    public static function amp_support() {
        add_theme_support('amp', [
            'paired' => true,
            'nav_menu_toggle' => true,
            'comments_live_list' => true,
        ]);
    }

    public static function amp_template($file, $type, $post) {
        if ('single' === $type) {
            $file = get_template_directory() . '/amp.php';
        }
        return $file;
    }

    public static function amp_sanitizers($sanitizers) {
        require_once get_template_directory() . '/inc/class-amp-img-sanitizer.php';
        $sanitizers['TonysTheme_AMP_Img_Sanitizer'] = [];
        return $sanitizers;
    }
}

// Alusta AMP-tuki
TonysThemeAMP::init();

/**
 * Lataa i18n-luokka
 */
require_once get_template_directory() . '/inc/class-i18n.php';

/**
 * Lataa JavaScript-suorituskyvyn optimointi
 */
require_once get_template_directory() . '/inc/class-js-performance.php';

/**
 * Lataa lazy loading -luokka
 */
require_once get_template_directory() . '/inc/class-lazy-loading.php';

/**
 * Lataa yhteensopivuustarkistusluokka
 */
require_once get_template_directory() . '/inc/class-compatibility-checker.php';

/**
 * Lataa teeman testausvalikko
 */
require_once get_template_directory() . '/inc/class-theme-tester-menu.php';

// Lataa teeman testit
require_once get_template_directory() . '/tests/test-theme-compatibility.php';

/**
 * Lataa suorituskyvyn monitoroinnin hallintasivu
 */
require_once get_template_directory() . '/inc/class-performance-monitor-page.php';

/**
 * Lataa tietoturvaluokka
 */
require_once get_template_directory() . '/inc/class-security.php';

/**
 * Lataa hakuparannukset
 */
require_once get_template_directory() . '/inc/class-search-enhancer.php';

/**
 * Lataa sosiaalisen median jakamistoiminnot
 */
require_once get_template_directory() . '/inc/class-social-sharing.php';

/**
 * Lataa JavaScript-tiedostojen minimointiluokka
 */
require_once get_template_directory() . '/inc/class-asset-minifier.php';

/**
 * Lataa välimuistin hallinta
 */
require_once get_template_directory() . '/inc/class-cache-manager.php';

/**
 * Lataa palvelinpuolen välimuistin hallinta
 */
require_once get_template_directory() . '/inc/class-server-cache.php';

/**
 * Lataa object cache -hallinta
 */
require_once get_template_directory() . '/inc/class-object-cache.php';

/**
 * Lataa fragmenttivälimuistin hallinta
 */
require_once get_template_directory() . '/inc/class-fragment-cache.php';

/**
 * Lataa REST API -välimuistin hallinta
 */
require_once get_template_directory() . '/inc/class-rest-cache.php';

/**
 * Lataa tietokannan optimointimoduuli
 */
require_once get_template_directory() . '/inc/class-db-optimizer.php';

/**
 * HTTP/2 Server Push -toiminnallisuus
 */
class TonysThemeServerPush {
    /**
     * Alusta Server Push
     */
    public static function init() {
        if (self::is_http2()) {
            add_action('wp_head', [__CLASS__, 'add_push_headers'], 0);
        }
    }

    /**
     * Tarkista onko HTTP/2 käytössä
     */
    private static function is_http2() {
        return isset($_SERVER['SERVER_PROTOCOL']) && 
               strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'http/2') !== false;
    }

    /**
     * Lisää Link-headerit kriittisille resursseille
     */
    public static function add_push_headers() {
        $template_directory_uri = get_template_directory_uri();
        
        // Kriittiset resurssit
        $resources = [
            '/assets/css/critical.css' => 'style',
            '/assets/js/theme.min.js' => 'script',
            '/assets/fonts/montserrat-v25-latin-regular.woff2' => 'font'
        ];

        foreach ($resources as $path => $type) {
            $full_path = $template_directory_uri . $path;
            header(
                sprintf(
                    'Link: <%s>; rel=preload; as=%s',
                    esc_url($full_path),
                    esc_attr($type)
                ),
                false
            );
        }
    }
}

// Alusta Server Push
TonysThemeServerPush::init();

/**
 * Service Worker -tuki
 */
class TonysThemeServiceWorker {
    /**
     * Alusta Service Worker
     */
    public static function init() {
        // Rekisteröi Service Worker
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_service_worker']);
        
        // Lisää offline-sivun template
        add_action('template_include', [__CLASS__, 'add_offline_template']);
    }

    /**
     * Rekisteröi Service Worker
     */
    public static function register_service_worker() {
        // Lisää Service Worker vain tuotantoympäristössä
        if (!WP_DEBUG) {
            wp_enqueue_script(
                'service-worker',
                get_template_directory_uri() . '/assets/js/service-worker.js',
                array(),
                wp_get_theme()->get('Version'),
                true
            );
        }
    }

    /**
     * Lisää offline-sivun template
     */
    public static function add_offline_template($template) {
        if (is_page('offline')) {
            $new_template = locate_template(array('offline.php'));
            if (!empty($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }
}

// Alusta Service Worker
TonysThemeServiceWorker::init();

/**
 * InnovationMedia Nexus Hub -sivuston mukautetut asetukset
 */
function innovationmedia_nexus_hub_setup() {
    // Lisää tuki mukautetulle logolle
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    // Lisää tuki mukautetulle otsakekuvalle
    add_theme_support('custom-header', array(
        'default-image'      => '',
        'default-text-color' => '000',
        'width'              => 1920,
        'height'             => 500,
        'flex-width'         => true,
        'flex-height'        => true,
    ));

    // Lisää tuki mukautetulle taustavärille
    add_theme_support('custom-background', array(
        'default-color' => 'ffffff',
    ));
}
add_action('after_setup_theme', 'innovationmedia_nexus_hub_setup');
