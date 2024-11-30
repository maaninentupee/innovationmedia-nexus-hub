<?php
/**
 * JavaScript- ja CSS-tiedostojen optimointi
 *
 * @package TonysTheme
 */

class TonysTheme_Asset_Minifier {
    /**
     * Alusta optimoinnit
     */
    public static function init() {
        // Yhdistä ja minimoi resurssit tuotantoympäristössä
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            add_action('wp_enqueue_scripts', [__CLASS__, 'handle_script_minification'], 999);
            add_action('wp_enqueue_scripts', [__CLASS__, 'handle_style_minification'], 999);
            add_action('wp_enqueue_scripts', [__CLASS__, 'handle_module_loading'], 999);
        }

        // Lisää preload-tuki kriittisille resursseille
        add_action('wp_head', [__CLASS__, 'add_resource_hints'], 1);
        
        // Lisää kriittinen CSS
        add_action('wp_head', [__CLASS__, 'add_critical_css'], 2);
        
        // Lataa muu CSS asynkronisesti
        add_filter('style_loader_tag', [__CLASS__, 'async_css_loading'], 10, 4);
        
        // Lisää SRI (Subresource Integrity) tuki
        add_filter('script_loader_tag', [__CLASS__, 'add_sri_attributes'], 10, 3);
        add_filter('style_loader_tag', [__CLASS__, 'add_sri_attributes'], 10, 3);
    }

    /**
     * Käsittele JavaScript-tiedostojen minimointi
     */
    public static function handle_script_minification() {
        // Määritä minimoitavat skriptit (vain teeman omat skriptit)
        $scripts_to_minify = [
            'tonys-theme-navigation',
            'tonys-theme-lazy-loading',
            'tonys-theme-social-sharing'
        ];

        // Luo minimoitujen tiedostojen kansio jos sitä ei ole
        $minified_dir = get_template_directory() . '/assets/js/min';
        if (!file_exists($minified_dir)) {
            wp_mkdir_p($minified_dir);
        }

        // Yhdistetyn tiedoston polku
        $combined_file = $minified_dir . '/theme.min.js';
        $combined_url = get_template_directory_uri() . '/assets/js/min/theme.min.js';

        // Tarkista pitääkö tiedostot minimoida uudelleen
        $should_minify = self::should_minify_files($scripts_to_minify, $combined_file, 'script');

        if ($should_minify) {
            $combined_content = '';
            
            // Kerää skriptien sisältö
            foreach ($scripts_to_minify as $handle) {
                $script = wp_scripts()->registered[$handle];
                if ($script) {
                    $file_path = ABSPATH . str_replace(site_url(), '', $script->src);
                    if (file_exists($file_path)) {
                        $content = file_get_contents($file_path);
                        $combined_content .= "/* {$handle} */\n" . $content . "\n";
                    }
                }
            }

            // Minimoi yhdistetty JavaScript
            $combined_content = self::minify_js($combined_content);

            // Tallenna minimoitu sisältö
            file_put_contents($combined_file, $combined_content);
        }

        // Poista alkuperäiset skriptit käytöstä ja lataa minimoitu versio
        if (file_exists($combined_file)) {
            foreach ($scripts_to_minify as $handle) {
                wp_deregister_script($handle);
            }

            wp_enqueue_script(
                'tonys-theme-combined',
                $combined_url,
                ['jquery'],
                filemtime($combined_file),
                true
            );
        }
    }

    /**
     * Käsittele ES-moduulien lataus
     */
    public static function handle_module_loading() {
        // Määritä moduulit
        $modules = [
            'tonys-theme-navigation-module' => '/assets/js/modules/navigation.js',
            'tonys-theme-lazy-loading-module' => '/assets/js/modules/lazy-loading.js',
            'tonys-theme-social-sharing-module' => '/assets/js/modules/social-sharing.js'
        ];

        foreach ($modules as $handle => $path) {
            wp_enqueue_script(
                $handle,
                get_template_directory_uri() . $path,
                [],
                filemtime(get_template_directory() . $path),
                true
            );
            // Lisää type="module" attribuutti
            add_filter('script_loader_tag', function($tag, $script_handle) use ($handle) {
                if ($handle === $script_handle) {
                    return str_replace(' src', ' type="module" src', $tag);
                }
                return $tag;
            }, 10, 2);
        }
    }

    /**
     * Käsittele CSS-tiedostojen minimointi
     */
    public static function handle_style_minification() {
        // Määritä minimoitavat tyylit (vain teeman omat tyylit)
        $styles_to_minify = [
            'tonys-theme-style',
            'tonys-theme-block-editor',
            'tonys-theme-lazy-loading'
        ];

        // Luo minimoitujen tiedostojen kansio
        $minified_dir = get_template_directory() . '/assets/css/min';
        if (!file_exists($minified_dir)) {
            wp_mkdir_p($minified_dir);
        }

        // Yhdistetyn tiedoston polku
        $combined_file = $minified_dir . '/theme.min.css';
        $combined_url = get_template_directory_uri() . '/assets/css/min/theme.min.css';

        // Tarkista pitääkö tiedostot minimoida uudelleen
        $should_minify = self::should_minify_files($styles_to_minify, $combined_file, 'style');

        if ($should_minify) {
            $combined_content = '';
            
            // Kerää tyylien sisältö
            foreach ($styles_to_minify as $handle) {
                $style = wp_styles()->registered[$handle];
                if ($style) {
                    $file_path = ABSPATH . str_replace(site_url(), '', $style->src);
                    if (file_exists($file_path)) {
                        $content = file_get_contents($file_path);
                        // Korjaa suhteelliset polut
                        $content = self::fix_css_paths($content, dirname($style->src));
                        $combined_content .= "/* {$handle} */\n" . $content . "\n";
                    }
                }
            }

            // Minimoi yhdistetty CSS
            $combined_content = self::minify_css($combined_content);

            // Tallenna minimoitu sisältö
            file_put_contents($combined_file, $combined_content);

            // Luo kriittinen CSS
            self::generate_critical_css($combined_content);
        }

        // Poista alkuperäiset tyylit käytöstä ja lataa minimoitu versio
        if (file_exists($combined_file)) {
            foreach ($styles_to_minify as $handle) {
                wp_deregister_style($handle);
            }

            wp_enqueue_style(
                'tonys-theme-combined',
                $combined_url,
                [],
                filemtime($combined_file)
            );
        }
    }

    /**
     * Lisää kriittinen CSS
     */
    public static function add_critical_css() {
        $critical_file = get_template_directory() . '/assets/css/critical/critical.css';
        if (file_exists($critical_file)) {
            echo "<style id='critical-css'>\n";
            echo file_get_contents($critical_file);
            echo "</style>\n";
        }
    }

    /**
     * Lataa CSS asynkronisesti
     */
    public static function async_css_loading($html, $handle, $href, $media) {
        // Älä lataa kriittistä CSS:ää asynkronisesti
        if ($handle === 'tonys-theme-critical') {
            return $html;
        }

        // Lataa muut CSS-tiedostot asynkronisesti
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $tag = $dom->getElementsByTagName('link')->item(0);
        
        if ($tag) {
            $tag->setAttribute('media', 'print');
            $tag->setAttribute('onload', "this.media='all'");
            
            return $dom->saveHTML($tag);
        }

        return $html;
    }

    /**
     * Generoi kriittinen CSS
     */
    private static function generate_critical_css($css) {
        // Määritä kriittiset selektorit (esim. yläpalkki, hero, ensimmäinen sisältöalue)
        $critical_selectors = [
            'body',
            'header',
            '.site-title',
            '.main-navigation',
            '.hero',
            '.entry-header',
            '.entry-content > *:first-child'
        ];

        $critical_css = '';
        
        // Etsi kriittiset tyylit
        if (preg_match_all('/([^}]+){[^}]+}/s', $css, $matches)) {
            foreach ($matches[0] as $rule) {
                foreach ($critical_selectors as $selector) {
                    if (strpos($rule, $selector) !== false) {
                        $critical_css .= $rule . "\n";
                        break;
                    }
                }
            }
        }

        // Tallenna kriittinen CSS
        $critical_dir = get_template_directory() . '/assets/css/critical';
        if (!file_exists($critical_dir)) {
            wp_mkdir_p($critical_dir);
        }

        file_put_contents(
            $critical_dir . '/critical.css',
            self::minify_css($critical_css)
        );
    }

    /**
     * Lisää resource hints kriittisille resursseille
     */
    public static function add_resource_hints() {
        // DNS-prefetch ulkoisille resursseille
        $domains = [
            'fonts.googleapis.com',
            'fonts.gstatic.com',
            'ajax.googleapis.com',
            'www.google-analytics.com'
        ];

        foreach ($domains as $domain) {
            echo "<link rel='dns-prefetch' href='//{$domain}'>\n";
            echo "<link rel='preconnect' href='https://{$domain}' crossorigin>\n";
        }

        // Preload kriittiset fontit
        $fonts = [
            '/assets/fonts/montserrat-v25-latin-regular.woff2' => 'font/woff2'
        ];

        foreach ($fonts as $path => $type) {
            printf(
                "<link rel='preload' href='%s' as='font' type='%s' crossorigin>\n",
                esc_url(get_template_directory_uri() . $path),
                esc_attr($type)
            );
        }

        // Preload kriittinen CSS
        if (file_exists(get_template_directory() . '/assets/css/critical/critical.css')) {
            printf(
                "<link rel='preload' href='%s' as='style'>\n",
                esc_url(get_template_directory_uri() . '/assets/css/critical/critical.css')
            );
        }

        // Preload ES moduulit
        $modules = [
            '/assets/js/modules/navigation.js',
            '/assets/js/modules/lazy-loading.js'
        ];

        foreach ($modules as $module) {
            printf(
                "<link rel='modulepreload' href='%s'>\n",
                esc_url(get_template_directory_uri() . $module)
            );
        }
    }

    /**
     * Tarkista pitääkö tiedostot minimoida uudelleen
     */
    private static function should_minify_files($handles, $combined_file, $type = 'script') {
        if (!file_exists($combined_file)) {
            return true;
        }

        $combined_time = filemtime($combined_file);
        $wp_objects = $type === 'script' ? wp_scripts() : wp_styles();
        
        foreach ($handles as $handle) {
            $object = $wp_objects->registered[$handle];
            if ($object) {
                $file_path = ABSPATH . str_replace(site_url(), '', $object->src);
                if (file_exists($file_path) && filemtime($file_path) > $combined_time) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Minimoi JavaScript
     */
    private static function minify_js($js) {
        // Poista kommentit
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        $js = preg_replace('/\/\/[^\n\r]*[\n\r]/s', '', $js);
        
        // Poista turhat välilyönnit
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Poista välilyönnit operaattoreiden ympäriltä
        $js = preg_replace('/\s*([\{\};\=\(\)])\s*/', '$1', $js);
        
        // Poista turhat puolipisteet
        $js = preg_replace('/;+\}/', '}', $js);
        
        // Poista ylimääräiset rivinvaihdot
        $js = preg_replace('/[\n\r\t]+/', '', $js);
        
        return trim($js);
    }

    /**
     * Minimoi CSS
     */
    private static function minify_css($css) {
        // Poista kommentit
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Poista ylimääräiset välilyönnit
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Poista välilyönnit erikoismerkkien ympäriltä
        $css = preg_replace('/\s*([\{\}:;\(\)])\s*/', '$1', $css);
        
        // Poista puolipisteet viimeisestä säännöstä
        $css = preg_replace('/;}/', '}', $css);
        
        // Poista ylimääräiset rivinvaihdot
        $css = preg_replace('/[\n\r\t]+/', '', $css);
        
        return trim($css);
    }

    /**
     * Korjaa suhteelliset polut CSS:ssä
     */
    private static function fix_css_paths($css, $base_url) {
        // Korjaa url()-polut
        return preg_replace_callback(
            '/url\([\'"]?([^\'"\)]+)[\'"]?\)/i',
            function($matches) use ($base_url) {
                $url = $matches[1];
                
                // Ohita absoluuttiset URL:t ja data-URL:t
                if (preg_match('/^(https?:\/\/|data:)/i', $url)) {
                    return $matches[0];
                }
                
                // Muunna suhteellinen polku absoluuttiseksi
                $absolute_url = $base_url . '/' . $url;
                $absolute_url = preg_replace('/\/+/', '/', $absolute_url);
                
                return "url('{$absolute_url}')";
            },
            $css
        );
    }

    /**
     * Minimoi tiedosto
     *
     * @param string $file Tiedostopolku
     * @param string $type Tiedostotyyppi (js/css)
     * @return string|false Minimoitu sisältö tai false virhetilanteessa
     */
    public static function minify_file($file, $type) {
        try {
            // Tarkista tiedoston olemassaolo
            if (!file_exists($file)) {
                throw new Exception('Tiedostoa ei löydy: ' . $file);
            }

            // Lue tiedoston sisältö
            $content = file_get_contents($file);
            if ($content === false) {
                throw new Exception('Tiedoston lukeminen epäonnistui: ' . $file);
            }

            // Minimoi sisältö
            switch ($type) {
                case 'js':
                    $minified = self::minify_js($content);
                    break;
                    
                case 'css':
                    $minified = self::minify_css($content);
                    break;
                    
                default:
                    throw new Exception('Tuntematon tiedostotyyppi: ' . $type);
            }

            // Luo välimuistitiedosto
            $cache_dir = WP_CONTENT_DIR . '/cache/tonys-theme/minified';
            if (!file_exists($cache_dir)) {
                wp_mkdir_p($cache_dir);
            }

            $cache_file = $cache_dir . '/' . basename($file, '.' . $type) . '.min.' . $type;
            file_put_contents($cache_file, $minified);

            return $minified;

        } catch (Exception $e) {
            error_log('TonysTheme Asset Minifier: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lisää SRI (Subresource Integrity) tuki
     */
    public static function add_sri_attributes($tag, $handle, $src) {
        // Ohita WordPress-ytimen ja lisäosien tiedostot
        if (strpos($src, get_template_directory_uri()) === false) {
            return $tag;
        }

        // Hae tiedoston absoluuttinen polku
        $file_path = str_replace(
            get_template_directory_uri(),
            get_template_directory(),
            $src
        );

        // Tarkista että tiedosto on olemassa
        if (!file_exists($file_path)) {
            return $tag;
        }

        // Laske hash
        $hash = hash('sha384', file_get_contents($file_path), true);
        $integrity = 'sha384-' . base64_encode($hash);

        // Lisää integrity ja crossorigin attribuutit
        return str_replace(
            " src=",
            " integrity='{$integrity}' crossorigin='anonymous' src=",
            $tag
        );
    }
}

// Alusta optimoinnit
TonysTheme_Asset_Minifier::init();
