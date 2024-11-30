<?php
/**
 * Teeman yhteensopivuustestit
 *
 * @package TonysTheme
 */

class TonysTheme_Compatibility_Tests {
    /**
     * Testattavat WordPress-versiot
     */
    private static $wp_versions = [
        '5.8',
        '5.9',
        '6.0',
        '6.1',
        '6.2',
        '6.3',
        '6.4'
    ];

    /**
     * Testattavat ominaisuudet
     */
    private static $features = [
        'core' => [
            'template-parts',
            'custom-logo',
            'post-thumbnails',
            'title-tag',
            'custom-background',
            'custom-header'
        ],
        'media' => [
            'webp',
            'lazy-loading'
        ],
        'performance' => [
            'amp',
            'critical-css'
        ]
    ];

    /**
     * Suorita yhteensopivuustestit
     */
    public static function run_tests() {
        $results = [];
        
        // Testaa teeman tuki eri WordPress-versioille
        foreach (self::$wp_versions as $version) {
            $results['versions'][$version] = self::test_wp_version($version);
        }

        // Testaa ominaisuuksien yhteensopivuus
        foreach (self::$features as $category => $features) {
            foreach ($features as $feature) {
                $results['features'][$category][$feature] = self::test_feature($feature);
            }
        }

        // Tallenna tulokset
        update_option('tonys_theme_test_results', $results);

        return $results;
    }

    /**
     * Testaa WordPress-version yhteensopivuus
     */
    private static function test_wp_version($version) {
        global $wp_version;
        
        return [
            'compatible' => version_compare($wp_version, $version, '>='),
            'current_version' => $wp_version,
            'tested_version' => $version,
            'timestamp' => current_time('mysql')
        ];
    }

    /**
     * Testaa yksittäisen ominaisuuden toimivuus
     */
    private static function test_feature($feature) {
        $result = [
            'status' => false,
            'message' => '',
            'timestamp' => current_time('mysql')
        ];

        switch ($feature) {
            case 'template-parts':
                $result['status'] = file_exists(get_template_directory() . '/template-parts');
                break;

            case 'custom-logo':
                $result['status'] = current_theme_supports('custom-logo');
                break;

            case 'post-thumbnails':
                $result['status'] = current_theme_supports('post-thumbnails');
                break;

            case 'title-tag':
                $result['status'] = current_theme_supports('title-tag');
                break;

            case 'custom-background':
                $result['status'] = current_theme_supports('custom-background');
                break;

            case 'custom-header':
                $result['status'] = current_theme_supports('custom-header');
                break;

            case 'webp':
                $result['status'] = function_exists('imagecreatefromwebp');
                break;

            case 'lazy-loading':
                $result['status'] = function_exists('wp_lazy_loading_enabled');
                break;

            case 'amp':
                $result['status'] = defined('AMP__VERSION');
                break;

            case 'critical-css':
                $result['status'] = file_exists(get_template_directory() . '/assets/css/critical.css');
                break;
        }

        return $result;
    }

    /**
     * Näytä testien tulokset admin-paneelissa
     */
    public static function display_test_results() {
        $results = get_option('tonys_theme_test_results', []);
        
        if (empty($results)) {
            return '<div class="notice notice-warning"><p>' . 
                   esc_html__('Testejä ei ole vielä suoritettu.', 'tonys-theme') . 
                   '</p></div>';
        }

        ob_start();
        ?>
        <div class="wrap">
            <h2><?php esc_html_e('Teeman yhteensopivuustestien tulokset', 'tonys-theme'); ?></h2>
            
            <h3><?php esc_html_e('WordPress-versiot', 'tonys-theme'); ?></h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Versio', 'tonys-theme'); ?></th>
                        <th><?php esc_html_e('Tila', 'tonys-theme'); ?></th>
                        <th><?php esc_html_e('Testattu', 'tonys-theme'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['versions'] as $version => $data): ?>
                    <tr>
                        <td><?php echo esc_html($version); ?></td>
                        <td><?php echo $data['compatible'] ? '✅' : '❌'; ?></td>
                        <td><?php echo esc_html($data['timestamp']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3><?php esc_html_e('Ominaisuudet', 'tonys-theme'); ?></h3>
            <?php foreach ($results['features'] as $category => $features): ?>
            <h4><?php echo esc_html(ucfirst($category)); ?></h4>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Ominaisuus', 'tonys-theme'); ?></th>
                        <th><?php esc_html_e('Tila', 'tonys-theme'); ?></th>
                        <th><?php esc_html_e('Testattu', 'tonys-theme'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($features as $feature => $data): ?>
                    <tr>
                        <td><?php echo esc_html($feature); ?></td>
                        <td><?php echo $data['status'] ? '✅' : '❌'; ?></td>
                        <td><?php echo esc_html($data['timestamp']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
