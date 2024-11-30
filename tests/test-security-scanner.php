<?php
/**
 * Tietoturvaskannerin testit
 *
 * @package TonysTheme
 */

class Test_Security_Scanner extends WP_UnitTestCase {
    /**
     * Testaa skannerin alustus
     */
    public function test_init() {
        TonysTheme_Security_Scanner::init();
        
        $this->assertEquals(
            true,
            has_action('tonys_theme_security_scan', [TonysTheme_Security_Scanner::class, 'run_security_scan'])
        );
        
        $this->assertEquals(
            true,
            has_action('admin_menu', [TonysTheme_Security_Scanner::class, 'add_admin_menu'])
        );
    }

    /**
     * Testaa tietoturvaskannaus
     */
    public function test_security_scan() {
        // Suorita skannaus
        TonysTheme_Security_Scanner::run_security_scan();
        
        // Hae tulokset
        $results = get_option('tonys_theme_security_scan_results', []);
        
        // Varmista että tulokset on tallennettu
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('timestamp', $results);
        $this->assertArrayHasKey('issues', $results);
        
        // Varmista että aikaleima on järkevä
        $this->assertGreaterThan(0, $results['timestamp']);
        
        // Varmista että ongelmat on array
        $this->assertIsArray($results['issues']);
    }

    /**
     * Testaa admin-sivun renderöinti
     */
    public function test_admin_page_render() {
        // Tallenna testidataa
        update_option('tonys_theme_security_scan_results', [
            'timestamp' => time(),
            'issues' => [
                [
                    'type' => 'critical',
                    'message' => 'Testitapaus: Kriittinen ongelma'
                ],
                [
                    'type' => 'warning',
                    'message' => 'Testitapaus: Varoitus'
                ]
            ]
        ]);

        // Aloita output buffering
        ob_start();
        
        // Renderöi sivu
        TonysTheme_Security_Scanner::render_admin_page();
        
        // Hae output
        $output = ob_get_clean();
        
        // Tarkista että sivu sisältää odotetut elementit
        $this->assertStringContainsString('Tietoturvaskanneri', $output);
        $this->assertStringContainsString('Testitapaus: Kriittinen ongelma', $output);
        $this->assertStringContainsString('Testitapaus: Varoitus', $output);
        $this->assertStringContainsString('notice-error', $output);
        $this->assertStringContainsString('notice-warning', $output);
    }

    /**
     * Testaa tiedosto-oikeuksien tarkistus
     */
    public function test_file_permissions() {
        // Luo testitiedosto
        $test_file = WP_CONTENT_DIR . '/test-security.txt';
        file_put_contents($test_file, 'Test content');
        chmod($test_file, 0777);

        // Suorita skannaus
        TonysTheme_Security_Scanner::run_security_scan();
        
        // Hae tulokset
        $results = get_option('tonys_theme_security_scan_results', []);
        
        // Etsi varoitus liian avoimista oikeuksista
        $found_warning = false;
        foreach ($results['issues'] as $issue) {
            if (strpos($issue['message'], 'oikeudet') !== false) {
                $found_warning = true;
                break;
            }
        }
        
        $this->assertTrue($found_warning);

        // Siivoa
        unlink($test_file);
    }
}
