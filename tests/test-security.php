<?php
/**
 * Tietoturvatestit
 */
class SecurityTest extends WP_UnitTestCase {
    private $image_optimizer;
    private $cache_optimizer;
    private $seo_optimizer;

    public function setUp() {
        parent::setUp();
        $this->image_optimizer = TonysTheme_Image_Optimizer::get_instance();
        $this->cache_optimizer = TonysTheme_Cache_Optimizer::get_instance();
        $this->seo_optimizer = TonysTheme_SEO_Optimizer::get_instance();
    }

    /**
     * Testaa tiedostonimien sanitointia
     */
    public function test_filename_sanitization() {
        $test_cases = array(
            'test.jpg' => 'test.jpg',
            'Test File.jpg' => 'test-file.jpg',
            'test$#@file.jpg' => 'testfile.jpg',
            '../test.jpg' => 'test.jpg',
            'test.php.jpg' => 'test-php.jpg'
        );

        foreach ($test_cases as $input => $expected) {
            $method = new ReflectionMethod($this->image_optimizer, 'sanitize_filename');
            $method->setAccessible(true);
            $this->assertEquals($expected, $method->invoke($this->image_optimizer, $input));
        }
    }

    /**
     * Testaa välimuistiavainten sanitointia
     */
    public function test_cache_key_sanitization() {
        $test_cases = array(
            'test_key' => 'test_key_v1.0',
            'test/key' => 'testkey_v1.0',
            'very_long_key_name_that_exceeds_limit' => substr('very_long_key_name_that_exceeds_limit_v1.0', 0, 40)
        );

        foreach ($test_cases as $input => $expected) {
            $method = new ReflectionMethod($this->cache_optimizer, 'sanitize_cache_key');
            $method->setAccessible(true);
            $this->assertEquals($expected, $method->invoke($this->cache_optimizer, $input));
        }
    }

    /**
     * Testaa meta-tagien sanitointia
     */
    public function test_meta_sanitization() {
        $test_cases = array(
            array(
                'input' => '<script>alert("xss")</script>Meta description',
                'type' => 'description',
                'expected' => 'Meta description'
            ),
            array(
                'input' => 'keyword1, keyword2, <b>keyword3</b>',
                'type' => 'keywords',
                'expected' => 'keyword1, keyword2, keyword3'
            ),
            array(
                'input' => 'index, noindex, invalid, follow',
                'type' => 'robots',
                'expected' => 'index, noindex, follow'
            )
        );

        foreach ($test_cases as $case) {
            $method = new ReflectionMethod($this->seo_optimizer, 'sanitize_meta_content');
            $method->setAccessible(true);
            $this->assertEquals(
                $case['expected'],
                $method->invoke($this->seo_optimizer, $case['input'], $case['type'])
            );
        }
    }

    /**
     * Testaa XSS-suojausta
     */
    public function test_xss_prevention() {
        $test_cases = array(
            '<script>alert("xss")</script>' => '',
            'Normal text' => 'Normal text',
            '<img src="x" onerror="alert(1)">' => 'x',
            'Text with <b>tags</b>' => 'Text with tags'
        );

        foreach ($test_cases as $input => $expected) {
            $this->assertEquals($expected, wp_strip_all_tags($input));
        }
    }

    /**
     * Testaa Content Security Policy -headereiden toimintaa
     */
    public function test_csp_headers() {
        // Aloita output buffering
        ob_start();
        
        // Suorita CSP-headereiden lisäys
        TonysTheme_Security::add_csp_headers();
        
        // Hae headerit
        $headers = xdebug_get_headers();
        
        // Tarkista CSP-headerit
        $csp_found = false;
        foreach ($headers as $header) {
            if (strpos($header, 'Content-Security-Policy:') !== false) {
                $csp_found = true;
                
                // Tarkista vaaditut direktiivit
                $this->assertStringContainsString("default-src 'self'", $header);
                $this->assertStringContainsString("script-src", $header);
                $this->assertStringContainsString("style-src", $header);
                $this->assertStringContainsString("img-src", $header);
                $this->assertStringContainsString("object-src 'none'", $header);
                
                break;
            }
        }
        
        $this->assertTrue($csp_found, 'CSP header should be present');
        
        // Siivoa
        ob_end_clean();
    }

    /**
     * Testaa Subresource Integrity -toiminnallisuutta
     */
    public function test_sri_functionality() {
        // Luo testitiedosto
        $test_js = get_template_directory() . '/assets/js/test.js';
        file_put_contents($test_js, 'console.log("test");');
        
        // Luo script tag
        $tag = '<script src="' . get_template_directory_uri() . '/assets/js/test.js"></script>';
        
        // Lisää SRI
        $result = TonysTheme_Asset_Minifier::add_sri_attributes($tag, 'test-script', get_template_directory_uri() . '/assets/js/test.js');
        
        // Tarkista että integrity ja crossorigin on lisätty
        $this->assertStringContainsString('integrity=', $result);
        $this->assertStringContainsString('sha384-', $result);
        $this->assertStringContainsString('crossorigin="anonymous"', $result);
        
        // Tarkista että hash on oikein
        $content = file_get_contents($test_js);
        $hash = hash('sha384', $content, true);
        $expected_hash = 'sha384-' . base64_encode($hash);
        
        $this->assertStringContainsString($expected_hash, $result);
        
        // Siivoa
        unlink($test_js);
    }

    /**
     * Testaa tietoturvaskannerin toimintaa
     */
    public function test_security_scanner() {
        // Suorita skannaus
        TonysTheme_Security_Scanner::run_security_scan();
        
        // Hae tulokset
        $results = get_option('tonys_theme_security_scan_results');
        
        // Tarkista että tulokset on olemassa
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('timestamp', $results);
        $this->assertArrayHasKey('issues', $results);
        
        // Tarkista että ongelmat on array
        $this->assertIsArray($results['issues']);
        
        // Tarkista jokaisen ongelman rakenne
        foreach ($results['issues'] as $issue) {
            $this->assertArrayHasKey('type', $issue);
            $this->assertArrayHasKey('message', $issue);
            $this->assertContains($issue['type'], ['critical', 'warning']);
        }
    }
}
