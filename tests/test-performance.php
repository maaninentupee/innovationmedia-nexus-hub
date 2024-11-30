<?php
/**
 * Suorituskykytestit
 */
class PerformanceTest extends WP_UnitTestCase {
    private $asset_minifier;
    private $image_optimizer;
    private $cache_optimizer;

    public function setUp() {
        parent::setUp();
        $this->asset_minifier = new TonysTheme_Asset_Minifier();
        $this->image_optimizer = TonysTheme_Image_Optimizer::get_instance();
        $this->cache_optimizer = TonysTheme_Cache_Optimizer::get_instance();
    }

    /**
     * Testaa JS-minimointia
     */
    public function test_js_minification() {
        $test_cases = array(
            array(
                'input' => "function test() {\n    console.log('test');\n}",
                'expected' => "function test(){console.log('test')}"
            ),
            array(
                'input' => "// Comment\nvar x = 1;\n/* Block comment */\nvar y = 2;",
                'expected' => "var x=1;var y=2;"
            )
        );

        foreach ($test_cases as $case) {
            $method = new ReflectionMethod($this->asset_minifier, 'minify_js');
            $method->setAccessible(true);
            $this->assertEquals(
                $case['expected'],
                $method->invoke($this->asset_minifier, $case['input'])
            );
        }
    }

    /**
     * Testaa CSS-minimointia
     */
    public function test_css_minification() {
        $test_cases = array(
            array(
                'input' => ".test {\n    color: red;\n    margin: 10px;\n}",
                'expected' => ".test{color:red;margin:10px}"
            ),
            array(
                'input' => "/* Comment */\n.test { color: red; }\n/* Another comment */",
                'expected' => ".test{color:red}"
            )
        );

        foreach ($test_cases as $case) {
            $method = new ReflectionMethod($this->asset_minifier, 'minify_css');
            $method->setAccessible(true);
            $this->assertEquals(
                $case['expected'],
                $method->invoke($this->asset_minifier, $case['input'])
            );
        }
    }

    /**
     * Testaa kuvan optimointia
     */
    public function test_image_optimization() {
        // Luo testitiedosto
        $test_image = dirname(__FILE__) . '/test-image.jpg';
        copy(dirname(__FILE__) . '/fixtures/test-image.jpg', $test_image);

        $upload = array(
            'file' => $test_image,
            'url' => 'http://example.com/test-image.jpg'
        );

        $result = $this->image_optimizer->optimize_uploaded_image($upload);

        $this->assertFileExists($test_image);
        $this->assertLessThan(filesize(dirname(__FILE__) . '/fixtures/test-image.jpg'), filesize($test_image));

        // Siivoa
        unlink($test_image);
    }

    /**
     * Testaa välimuistin toimintaa
     */
    public function test_cache_functionality() {
        // Testaa sivun välimuistia
        $content = 'Test content';
        $key = 'test_page';

        $this->cache_optimizer->save_to_cache($key, $content);
        $cached = $this->cache_optimizer->get_from_cache($key);

        $this->assertEquals($content, $cached);

        // Testaa välimuistin tyhjennystä
        $this->cache_optimizer->clear_cache();
        $cached_after_clear = $this->cache_optimizer->get_from_cache($key);

        $this->assertFalse($cached_after_clear);
    }

    /**
     * Testaa lazy loading -toimintoa
     */
    public function test_lazy_loading() {
        $content = '<img src="test.jpg" alt="Test">';
        $expected = '<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="test.jpg" alt="Test" loading="lazy">';

        $method = new ReflectionMethod($this->image_optimizer, 'add_lazy_loading');
        $method->setAccessible(true);
        $result = $method->invoke($this->image_optimizer, $content);

        $this->assertEquals($expected, $result);
    }

    /**
     * Testaa HTTP/2 Server Push -toiminnallisuutta
     */
    public function test_http2_server_push() {
        // Simuloi HTTP/2-ympäristöä
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/2.0';
        
        // Aloita output buffering
        ob_start();
        
        // Suorita Server Push
        TonysThemeServerPush::add_push_headers();
        
        // Hae headerit
        $headers = xdebug_get_headers();
        
        // Tarkista että kriittiset resurssit on lisätty
        $critical_resources = [
            'critical.css',
            'theme.min.js',
            'montserrat-v25-latin-regular.woff2'
        ];
        
        foreach ($critical_resources as $resource) {
            $found = false;
            foreach ($headers as $header) {
                if (strpos($header, $resource) !== false && 
                    strpos($header, 'rel=preload') !== false) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Resource {$resource} should be preloaded");
        }
        
        // Siivoa
        ob_end_clean();
    }

    /**
     * Testaa Service Workerin toimintaa
     */
    public function test_service_worker() {
        // Tarkista että service worker -tiedosto on olemassa
        $sw_file = get_template_directory() . '/assets/js/service-worker.js';
        $this->assertFileExists($sw_file);
        
        // Tarkista että service worker sisältää vaaditut ominaisuudet
        $sw_content = file_get_contents($sw_file);
        
        // Tarkista välimuistin määritykset
        $this->assertStringContainsString('CACHE_NAME', $sw_content);
        $this->assertStringContainsString('PRECACHE_URLS', $sw_content);
        
        // Tarkista event listenerit
        $this->assertStringContainsString("addEventListener('install'", $sw_content);
        $this->assertStringContainsString("addEventListener('activate'", $sw_content);
        $this->assertStringContainsString("addEventListener('fetch'", $sw_content);
        
        // Tarkista offline-sivu
        $this->assertStringContainsString('OFFLINE_PAGE', $sw_content);
    }

    /**
     * Testaa resurssien latausattribuutteja
     */
    public function test_resource_loading_attributes() {
        // Testaa skriptien latausattribuutteja
        $script_tag = TonysTheme_Asset_Minifier::optimize_script_loading(
            '<script src="test.js"></script>',
            'tonys-theme-main'
        );
        
        // Tarkista että defer on lisätty
        $this->assertStringContainsString('defer', $script_tag);
        
        // Testaa tyylitiedostojen latausattribuutteja
        $style_tag = TonysTheme_Asset_Minifier::optimize_style_loading(
            '<link rel="stylesheet" href="test.css">',
            'tonys-theme-critical'
        );
        
        // Tarkista että preload on lisätty
        $this->assertStringContainsString('rel="preload"', $style_tag);
        $this->assertStringContainsString('as="style"', $style_tag);
    }
}
