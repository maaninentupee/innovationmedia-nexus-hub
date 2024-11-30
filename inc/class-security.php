<?php
/**
 * Teeman tietoturvaominaisuudet
 *
 * @package TonysTheme
 */

class TonysTheme_Security {
    /**
     * Alusta tietoturvaominaisuudet
     */
    public static function init() {
        // Lisää tietoturvaotsikot
        add_action('send_headers', [__CLASS__, 'add_security_headers']);
        
        // Poista versionumerot
        add_filter('style_loader_src', [__CLASS__, 'remove_version_strings'], 10, 2);
        add_filter('script_loader_src', [__CLASS__, 'remove_version_strings'], 10, 2);
        
        // Estä käyttäjänimien listaus
        add_action('rest_endpoints', [__CLASS__, 'disable_user_enumeration']);
        
        // Lisää CAPTCHA kommentteihin
        add_filter('comment_form_defaults', [__CLASS__, 'add_comment_captcha']);
        add_filter('preprocess_comment', [__CLASS__, 'verify_comment_captcha']);
        
        // Suojaa lomakkeet
        add_action('login_form', [__CLASS__, 'add_login_protection']);
        add_filter('authenticate', [__CLASS__, 'check_login_protection'], 30, 3);
        
        // Lisää Content Security Policy -headerit
        add_action('send_headers', [__CLASS__, 'add_csp_headers']);
    }

    /**
     * Lisää tietoturvaotsikot
     */
    public static function add_security_headers() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
            
            // Lisää CSP vain jos sitä ei ole jo määritetty
            if (!headers_list('Content-Security-Policy')) {
                header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' *.wordpress.org *.google-analytics.com; style-src 'self' 'unsafe-inline' *.googleapis.com; img-src 'self' data: *.wordpress.org *.gravatar.com; font-src 'self' data: *.googleapis.com *.gstatic.com;");
            }
        }
    }

    /**
     * Poista versionumerot URL:eista
     */
    public static function remove_version_strings($src, $handle) {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    /**
     * Estä käyttäjänimien listaus
     */
    public static function disable_user_enumeration($endpoints) {
        if (isset($endpoints['/wp/v2/users'])) {
            unset($endpoints['/wp/v2/users']);
        }
        if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
            unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
        }
        return $endpoints;
    }

    /**
     * Lisää CAPTCHA kommenttilomakkeeseen
     */
    public static function add_comment_captcha($defaults) {
        $num1 = wp_rand(1, 10);
        $num2 = wp_rand(1, 10);
        
        // Tallenna oikea vastaus sessioon
        if (!session_id()) {
            session_start();
        }
        $_SESSION['comment_captcha'] = $num1 + $num2;
        
        $defaults['fields']['captcha'] = sprintf(
            '<p class="comment-form-captcha">
                <label for="captcha">%s %d + %d = ?</label>
                <input type="number" name="captcha" id="captcha" required>
            </p>',
            esc_html__('Varmistus:', 'tonys-theme'),
            $num1,
            $num2
        );
        
        return $defaults;
    }

    /**
     * Tarkista kommentin CAPTCHA
     */
    public static function verify_comment_captcha($commentdata) {
        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['comment_captcha']) || !isset($_POST['captcha'])) {
            wp_die(esc_html__('Virhe: CAPTCHA puuttuu.', 'tonys-theme'));
        }

        if (intval($_POST['captcha']) !== $_SESSION['comment_captcha']) {
            wp_die(esc_html__('Virhe: Väärä CAPTCHA-vastaus.', 'tonys-theme'));
        }

        return $commentdata;
    }

    /**
     * Lisää kirjautumislomakkeen suojaus
     */
    public static function add_login_protection() {
        $token = wp_generate_password(20, false);
        
        if (!session_id()) {
            session_start();
        }
        $_SESSION['login_token'] = $token;
        
        printf(
            '<input type="hidden" name="login_token" value="%s">',
            esc_attr($token)
        );
    }

    /**
     * Tarkista kirjautumisen suojaus
     */
    public static function check_login_protection($user, $username, $password) {
        if (!session_id()) {
            session_start();
        }

        // Ohita tarkistus, jos käyttäjä on jo kirjautunut
        if (is_user_logged_in()) {
            return $user;
        }

        if (!isset($_SESSION['login_token']) || !isset($_POST['login_token']) ||
            $_SESSION['login_token'] !== $_POST['login_token']) {
            return new WP_Error(
                'invalid_token',
                esc_html__('Virhe: Istunto on vanhentunut. Yritä uudelleen.', 'tonys-theme')
            );
        }

        return $user;
    }

    /**
     * Tarkista onko pyyntö AJAX
     */
    private static function is_ajax_request() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * Sanitoi syötteet
     *
     * @param mixed $data Sanitoitava data
     * @param string $type Datan tyyppi
     * @return mixed Sanitoitu data
     */
    public static function sanitize_data($data, $type = 'text') {
        switch ($type) {
            case 'text':
                return sanitize_text_field($data);
                
            case 'html':
                return wp_kses_post($data);
                
            case 'url':
                return esc_url_raw($data);
                
            case 'email':
                return sanitize_email($data);
                
            case 'filename':
                return sanitize_file_name($data);
                
            case 'key':
                return sanitize_key($data);
                
            case 'title':
                return sanitize_title($data);
                
            case 'sql':
                global $wpdb;
                return $wpdb->prepare('%s', $data);
                
            default:
                return sanitize_text_field($data);
        }
    }

    /**
     * Suojaa ulostulot
     *
     * @param mixed $data Suojattava data
     * @param string $type Datan tyyppi
     * @return mixed Suojattu data
     */
    public static function escape_output($data, $type = 'html') {
        switch ($type) {
            case 'html':
                return esc_html($data);
                
            case 'attr':
                return esc_attr($data);
                
            case 'url':
                return esc_url($data);
                
            case 'js':
                return esc_js($data);
                
            case 'textarea':
                return esc_textarea($data);
                
            case 'sql':
                global $wpdb;
                return $wpdb->prepare('%s', $data);
                
            default:
                return esc_html($data);
        }
    }

    /**
     * Tarkista nonce
     *
     * @param string $nonce Nonce-arvo
     * @param string $action Toiminto
     * @return bool True jos validi
     */
    public static function verify_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die('Virheellinen turvatarkistus');
        }
        return true;
    }

    /**
     * Tarkista käyttöoikeudet
     *
     * @param string $capability Vaadittu käyttöoikeus
     * @return bool True jos käyttäjällä on oikeudet
     */
    public static function check_capability($capability) {
        if (!current_user_can($capability)) {
            wp_die('Ei käyttöoikeuksia');
        }
        return true;
    }

    /**
     * Lisää turvaheaderit
     */
    public static function add_security_headers() {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
    }

    /**
     * Lisää Content Security Policy -headerit
     */
    public static function add_csp_headers() {
        if (headers_sent()) {
            return;
        }

        $csp = [
            "default-src" => ["'self'"],
            "script-src" => ["'self'", "'unsafe-inline'", "'unsafe-eval'", "*.googleapis.com", "*.gstatic.com"],
            "style-src" => ["'self'", "'unsafe-inline'", "*.googleapis.com"],
            "img-src" => ["'self'", "data:", "*.wp.com", "*.gravatar.com"],
            "font-src" => ["'self'", "data:", "*.gstatic.com"],
            "connect-src" => ["'self'"],
            "media-src" => ["'self'"],
            "object-src" => ["'none'"],
            "frame-src" => ["'self'"],
            "base-uri" => ["'self'"],
            "form-action" => ["'self'"],
            "frame-ancestors" => ["'self'"],
            "upgrade-insecure-requests" => true
        ];

        // Lisää Google Analytics tuotantoympäristössä
        if (!WP_DEBUG) {
            $csp["script-src"][] = "*.google-analytics.com";
            $csp["connect-src"][] = "*.google-analytics.com";
        }

        // Rakenna CSP-string
        $csp_string = '';
        foreach ($csp as $directive => $values) {
            if ($directive === 'upgrade-insecure-requests') {
                $csp_string .= 'upgrade-insecure-requests; ';
                continue;
            }
            $csp_string .= $directive . ' ' . implode(' ', $values) . '; ';
        }

        // Lisää headerit
        header("Content-Security-Policy: " . trim($csp_string));
        
        // Lisää report-only header kehitysympäristössä
        if (WP_DEBUG) {
            header("Content-Security-Policy-Report-Only: " . trim($csp_string));
        }
    }
}

// Alusta tietoturvaominaisuudet
TonysTheme_Security::init();
