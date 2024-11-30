<?php
/**
 * Välimuistin hallinta
 *
 * @package TonysTheme
 */

class TonysTheme_Cache_Manager {
    /**
     * Alusta välimuistin hallinta
     */
    public static function init() {
        // Lisää välimuistin hallinta vain tuotantoympäristössä
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Selaimen välimuisti
            add_action('send_headers', [__CLASS__, 'set_browser_cache_headers']);
            add_filter('style_loader_src', [__CLASS__, 'add_cache_busting'], 10, 2);
            add_filter('script_loader_src', [__CLASS__, 'add_cache_busting'], 10, 2);

            // Staattisten resurssien välimuisti
            add_action('init', [__CLASS__, 'setup_static_cache']);
        }
    }

    /**
     * Aseta selaimen välimuistin otsikkotiedot
     */
    public static function set_browser_cache_headers() {
        $cache_time = YEAR_IN_SECONDS;
        
        // Älä välimuistita, jos käyttäjä on kirjautunut sisään
        if (is_user_logged_in()) {
            nocache_headers();
            return;
        }

        // Aseta välimuistin otsikkotiedot staattisille resursseille
        if (self::is_static_resource()) {
            header('Cache-Control: public, max-age=' . $cache_time);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
            header('Pragma: public');
        } 
        // Aseta lyhyempi välimuistiaika dynaamiselle sisällölle
        else {
            $dynamic_cache_time = DAY_IN_SECONDS;
            header('Cache-Control: public, max-age=' . $dynamic_cache_time);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $dynamic_cache_time) . ' GMT');
            header('Pragma: public');
        }
    }

    /**
     * Lisää versiointi tiedostoihin välimuistin ohittamiseksi päivitysten yhteydessä
     */
    public static function add_cache_busting($src, $handle) {
        if (!$src) {
            return $src;
        }

        // Tarkista onko kyseessä teeman tiedosto
        $theme_url = get_template_directory_uri();
        if (strpos($src, $theme_url) === false) {
            return $src;
        }

        // Muunna URL tiedostopoluksi
        $file_path = str_replace(
            [get_template_directory_uri(), site_url()],
            get_template_directory(),
            $src
        );

        // Lisää tiedoston muokkausaika versionumeroksi
        if (file_exists($file_path)) {
            $version = filemtime($file_path);
            $src = add_query_arg('ver', $version, $src);
        }

        return $src;
    }

    /**
     * Määritä staattisten resurssien välimuistiasetukset
     */
    public static function setup_static_cache() {
        // Lisää .htaccess-säännöt staattisille resursseille
        if (!is_admin() && !self::has_cache_rules()) {
            self::add_cache_rules();
        }
    }

    /**
     * Tarkista onko kyseessä staattinen resurssi
     */
    private static function is_static_resource() {
        $static_files = ['css', 'js', 'gif', 'jpg', 'jpeg', 'png', 'ico', 'svg', 'woff', 'woff2', 'ttf', 'eot'];
        $extension = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);
        
        return in_array(strtolower($extension), $static_files);
    }

    /**
     * Tarkista onko välimuistisäännöt jo .htaccess-tiedostossa
     */
    private static function has_cache_rules() {
        $htaccess_path = get_home_path() . '.htaccess';
        
        if (!file_exists($htaccess_path)) {
            return false;
        }

        $htaccess_content = file_get_contents($htaccess_path);
        return strpos($htaccess_content, '# BEGIN TonysTheme Cache') !== false;
    }

    /**
     * Lisää välimuistisäännöt .htaccess-tiedostoon
     */
    private static function add_cache_rules() {
        $htaccess_path = get_home_path() . '.htaccess';
        
        if (!file_exists($htaccess_path)) {
            return;
        }

        $cache_rules = "\n# BEGIN TonysTheme Cache\n";
        $cache_rules .= "<IfModule mod_expires.c>\n";
        $cache_rules .= "ExpiresActive On\n\n";
        
        // Aseta oletusarvo
        $cache_rules .= "ExpiresDefault \"access plus 1 month\"\n\n";
        
        // HTML
        $cache_rules .= "ExpiresByType text/html \"access plus 0 seconds\"\n\n";
        
        // Kuvat
        $cache_rules .= "# Images\n";
        $cache_rules .= "ExpiresByType image/jpeg \"access plus 1 year\"\n";
        $cache_rules .= "ExpiresByType image/gif \"access plus 1 year\"\n";
        $cache_rules .= "ExpiresByType image/png \"access plus 1 year\"\n";
        $cache_rules .= "ExpiresByType image/webp \"access plus 1 year\"\n";
        $cache_rules .= "ExpiresByType image/svg+xml \"access plus 1 year\"\n";
        $cache_rules .= "ExpiresByType image/x-icon \"access plus 1 year\"\n\n";
        
        // CSS, JavaScript
        $cache_rules .= "# CSS, JavaScript\n";
        $cache_rules .= "ExpiresByType text/css \"access plus 1 year\"\n";
        $cache_rules .= "ExpiresByType text/javascript \"access plus 1 year\"\n";
        $cache_rules .= "ExpiresByType application/javascript \"access plus 1 year\"\n\n";
        
        // Fontit
        $cache_rules .= "# Fonts\n";
        $cache_rules .= "ExpiresByType font/woff \"access plus 1 year\"\n";
        $cache_rules .= "ExpiresByType font/woff2 \"access plus 1 year\"\n";
        $cache_rules .= "ExpiresByType application/font-woff \"access plus 1 year\"\n";
        $cache_rules .= "ExpiresByType application/font-woff2 \"access plus 1 year\"\n";
        
        $cache_rules .= "</IfModule>\n";
        
        // Lisää CORS-otsikot fonteille
        $cache_rules .= "\n<FilesMatch \"\.(ttf|ttc|otf|eot|woff|woff2|font.css|css)$\">\n";
        $cache_rules .= "Header set Access-Control-Allow-Origin \"*\"\n";
        $cache_rules .= "</FilesMatch>\n";
        
        $cache_rules .= "# END TonysTheme Cache\n\n";

        // Lisää säännöt .htaccess-tiedostoon
        $htaccess_content = file_get_contents($htaccess_path);
        $htaccess_content = $cache_rules . $htaccess_content;
        file_put_contents($htaccess_path, $htaccess_content);
    }
}

// Alusta välimuistin hallinta
TonysTheme_Cache_Manager::init();
