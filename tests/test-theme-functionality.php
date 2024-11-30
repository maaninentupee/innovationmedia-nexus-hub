<?php
/**
 * Teeman toiminnallisuuksien testit
 */
class TestThemeFunctionality extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        
        // Alusta tarvittavat luokat
        require_once get_template_directory() . '/functions.php';
        
        // Luo testitiedostot
        $this->create_test_files();
    }

    public function tearDown(): void {
        parent::tearDown();
        
        // Siivoa testitiedostot
        $this->cleanup_test_files();
    }

    /**
     * Testaa navigointivalikon toiminta
     */
    public function test_navigation_menu() {
        // Luo testivalikko
        $menu_id = wp_create_nav_menu('Test Menu');
        
        // Lisää valikkokohteet
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title' => 'Test Item 1',
            'menu-item-url' => 'http://example.com/1',
            'menu-item-status' => 'publish'
        ));
        
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title' => 'Test Item 2',
            'menu-item-url' => 'http://example.com/2',
            'menu-item-status' => 'publish'
        ));

        // Aseta valikko teeman sijaintiin
        $locations = get_theme_mod('nav_menu_locations');
        $locations['primary'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);

        // Hae valikko
        $menu = wp_nav_menu(array(
            'theme_location' => 'primary',
            'echo' => false
        ));

        // Tarkista että valikko sisältää molemmat kohteet
        $this->assertStringContainsString('Test Item 1', $menu);
        $this->assertStringContainsString('Test Item 2', $menu);
        $this->assertStringContainsString('http://example.com/1', $menu);
        $this->assertStringContainsString('http://example.com/2', $menu);
    }

    /**
     * Testaa mukautetun logon toiminta
     */
    public function test_custom_logo() {
        // Luo testitiedosto
        $filename = 'test-logo.png';
        $tmp_file = wp_tempnam($filename);
        copy(get_template_directory() . '/tests/fixtures/test-logo.png', $tmp_file);

        // Lataa logo
        $attachment_id = $this->_make_attachment($tmp_file);
        
        // Aseta logo
        set_theme_mod('custom_logo', $attachment_id);

        // Tarkista että logo näkyy oikein
        $custom_logo = get_custom_logo();
        
        $this->assertStringContainsString('custom-logo', $custom_logo);
        $this->assertStringContainsString((string)$attachment_id, $custom_logo);
    }

    /**
     * Testaa lomakkeiden tietoturva
     */
    public function test_form_security() {
        // Testaa CSRF-suojaus
        $this->assertTrue(function_exists('wp_create_nonce'));
        $nonce = wp_create_nonce('test_action');
        $this->assertTrue(wp_verify_nonce($nonce, 'test_action'));

        // Testaa syötteiden validointi
        $test_data = array(
            'name' => '<script>alert("XSS")</script>John',
            'email' => '" onmouseover="alert(1)',
            'message' => "Multiline\nTest\nMessage"
        );

        $sanitized = TonysThemeSecurity::sanitize_form_data($test_data);

        // Tarkista että XSS on estetty
        $this->assertStringNotContainsString('<script>', $sanitized['name']);
        $this->assertEquals('John', $sanitized['name']);
        
        // Tarkista että sähköposti on validi
        $this->assertFalse(filter_var($test_data['email'], FILTER_VALIDATE_EMAIL));
        
        // Tarkista että monirivinen teksti on sallittu
        $this->assertStringContainsString("\n", $sanitized['message']);
    }

    /**
     * Testaa suorituskykyoptimoinnit
     */
    public function test_performance_optimizations() {
        // Testaa kuvien optimointi
        $image_file = get_template_directory() . '/tests/fixtures/test-image.jpg';
        $optimized = TonysThemePerformance::optimize_uploaded_image(array(
            'file' => $image_file,
            'type' => 'image/jpeg'
        ));

        // Tarkista että WebP-versio luotiin
        $webp_file = pathinfo($image_file, PATHINFO_DIRNAME) . '/' . 
                    pathinfo($image_file, PATHINFO_FILENAME) . '.webp';
        $this->assertFileExists($webp_file);

        // Testaa kriittisen CSS:n lataus
        ob_start();
        TonysThemePerformance::add_critical_css();
        $output = ob_get_clean();

        $this->assertStringContainsString('critical-css', $output);
    }

    /**
     * Testaa lokien hallinta
     */
    public function test_logging() {
        // Testaa virhekirjaus
        $test_message = 'Test error message';
        TonysThemeLogger::log_error($test_message);

        // Tarkista että loki luotiin
        $log_file = WP_CONTENT_DIR . '/logs/tonys-theme/error.log';
        $this->assertFileExists($log_file);
        
        // Tarkista että viesti kirjattiin
        $log_content = file_get_contents($log_file);
        $this->assertStringContainsString($test_message, $log_content);

        // Testaa lokien siivous
        touch($log_file, strtotime('-31 days'));
        TonysThemeLogger::cleanupLogs();
        
        // Tarkista että vanha loki siirrettiin arkistoon
        $archive_dir = WP_CONTENT_DIR . '/logs/tonys-theme/archive';
        $this->assertDirectoryExists($archive_dir);
        $this->assertFileExists($archive_dir . '/' . date('Y-m', strtotime('-31 days')) . '-error.log');
    }

    /**
     * Apufunktiot testitiedostojen hallintaan
     */
    private function create_test_files() {
        // Luo testitiedostot
        wp_mkdir_p(get_template_directory() . '/tests/fixtures');
        
        // Luo testikuva
        $image = imagecreatetruecolor(100, 100);
        imagejpeg($image, get_template_directory() . '/tests/fixtures/test-image.jpg');
        imagedestroy($image);
        
        // Luo testilogo
        $logo = imagecreatetruecolor(200, 50);
        imagepng($logo, get_template_directory() . '/tests/fixtures/test-logo.png');
        imagedestroy($logo);
    }

    private function cleanup_test_files() {
        // Poista testitiedostot
        $files = glob(get_template_directory() . '/tests/fixtures/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir(get_template_directory() . '/tests/fixtures');
    }
}
