<?php
/**
 * Automaattinen tietoturvaskanneri
 *
 * @package TonysTheme
 */

class TonysTheme_Security_Scanner {
    /**
     * Alusta skanneri
     */
    public static function init() {
        // Suorita skannaus päivittäin
        if (!wp_next_scheduled('tonys_theme_security_scan')) {
            wp_schedule_event(time(), 'daily', 'tonys_theme_security_scan');
        }
        add_action('tonys_theme_security_scan', [__CLASS__, 'run_security_scan']);

        // Lisää hallintapaneelin sivu
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
    }

    /**
     * Suorita tietoturvaskannaus
     */
    public static function run_security_scan() {
        $issues = [];

        // Tarkista WordPress-versio
        global $wp_version;
        if (version_compare($wp_version, '5.9', '<')) {
            $issues[] = [
                'type' => 'critical',
                'message' => 'WordPress-versio on vanhentunut. Päivitä uusimpaan versioon.'
            ];
        }

        // Tarkista tiedosto-oikeudet
        $files_to_check = [
            'wp-config.php' => '0400',
            '.htaccess' => '0444',
            'wp-content/uploads' => '0755'
        ];

        foreach ($files_to_check as $file => $required_perms) {
            $file_path = ABSPATH . $file;
            if (file_exists($file_path)) {
                $current_perms = substr(sprintf('%o', fileperms($file_path)), -4);
                if ($current_perms !== $required_perms) {
                    $issues[] = [
                        'type' => 'warning',
                        'message' => sprintf(
                            'Tiedoston %s oikeudet (%s) eivät ole suositellut (%s)',
                            $file,
                            $current_perms,
                            $required_perms
                        )
                    ];
                }
            }
        }

        // Tarkista admin-käyttäjä
        $admin_user = get_user_by('login', 'admin');
        if ($admin_user) {
            $issues[] = [
                'type' => 'warning',
                'message' => 'Oletuskäyttäjänimi "admin" on käytössä. Tämä on tietoturvariski.'
            ];
        }

        // Tarkista SSL
        if (!is_ssl()) {
            $issues[] = [
                'type' => 'warning',
                'message' => 'SSL ei ole käytössä. Suosittelemme HTTPS:n käyttöönottoa.'
            ];
        }

        // Tarkista debug.log
        $debug_log = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($debug_log) && is_readable($debug_log)) {
            $issues[] = [
                'type' => 'warning',
                'message' => 'debug.log on julkisesti luettavissa. Siirrä se web-kansion ulkopuolelle.'
            ];
        }

        // Tallenna tulokset
        update_option('tonys_theme_security_scan_results', [
            'timestamp' => current_time('timestamp'),
            'issues' => $issues
        ]);

        // Lähetä sähköposti-ilmoitus jos kriittisiä ongelmia
        $critical_issues = array_filter($issues, function($issue) {
            return $issue['type'] === 'critical';
        });

        if (!empty($critical_issues)) {
            self::send_notification($critical_issues);
        }
    }

    /**
     * Lähetä ilmoitus kriittisistä ongelmista
     */
    private static function send_notification($issues) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $message = "Hei,\n\n";
        $message .= "Tietoturvaskannaus löysi seuraavat kriittiset ongelmat sivustolta {$site_name}:\n\n";
        
        foreach ($issues as $issue) {
            $message .= "- {$issue['message']}\n";
        }
        
        $message .= "\nKäy tarkistamassa ongelmat hallintapaneelista.\n";
        
        wp_mail(
            $admin_email,
            "[{$site_name}] Kriittisiä tietoturvaongelmia havaittu",
            $message
        );
    }

    /**
     * Lisää hallintapaneelin sivu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Tietoturvaskanneri',
            'Tietoturvaskanneri',
            'manage_options',
            'security-scanner',
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Renderöi hallintapaneelin sivu
     */
    public static function render_admin_page() {
        $results = get_option('tonys_theme_security_scan_results', []);
        $last_scan = isset($results['timestamp']) ? 
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $results['timestamp']) : 
            'Ei koskaan';
        
        ?>
        <div class="wrap">
            <h1>Tietoturvaskanneri</h1>
            
            <div class="notice notice-info">
                <p>Viimeisin skannaus: <?php echo esc_html($last_scan); ?></p>
            </div>

            <?php if (!empty($results['issues'])): ?>
                <h2>Löydetyt ongelmat</h2>
                <div class="security-issues">
                    <?php foreach ($results['issues'] as $issue): ?>
                        <div class="notice notice-<?php echo $issue['type'] === 'critical' ? 'error' : 'warning'; ?>">
                            <p><?php echo esc_html($issue['message']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p>Ei löydettyjä tietoturvaongelmia!</p>
                </div>
            <?php endif; ?>

            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=security-scanner&action=scan'), 'security_scan'); ?>" 
                   class="button button-primary">
                    Suorita skannaus nyt
                </a>
            </p>
        </div>
        <?php
    }
}

// Alusta tietoturvaskanneri
TonysTheme_Security_Scanner::init();
