<?php
/**
 * Suorituskykytestit
 */
class Performance_Tests extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        require_once get_template_directory() . '/inc/class-performance-monitor.php';
    }

    public function test_critical_css_generation() {
        $performance = new Performance_Monitor();
        $critical_css = $performance->generate_critical_css();
        
        $this->assertNotEmpty($critical_css);
        $this->assertStringContainsString('.header', $critical_css);
        $this->assertStringContainsString('.navigation', $critical_css);
    }

    public function test_image_optimization() {
        $performance = new Performance_Monitor();
        
        // Testaa kuvan optimointi
        $test_image = get_template_directory() . '/tests/fixtures/test-image.jpg';
        $optimized = $performance->optimize_image($test_image);
        
        $this->assertLessThan(filesize($test_image), filesize($optimized));
    }

    public function test_cache_headers() {
        $performance = new Performance_Monitor();
        $headers = $performance->get_cache_headers();
        
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('Expires', $headers);
    }

    public function test_resource_hints() {
        $performance = new Performance_Monitor();
        $hints = $performance->get_resource_hints();
        
        $this->assertContains('dns-prefetch', $hints);
        $this->assertContains('preconnect', $hints);
    }
}

/**
 * Saavutettavuustestit
 */
class Accessibility_Tests extends WP_UnitTestCase {
    
    public function test_aria_landmarks() {
        $html = get_echo('wp_head') . get_echo('wp_footer');
        
        $this->assertStringContainsString('role="navigation"', $html);
        $this->assertStringContainsString('role="main"', $html);
        $this->assertStringContainsString('role="complementary"', $html);
    }

    public function test_image_alt_texts() {
        $post_id = $this->factory->post->create([
            'post_content' => '<img src="test.jpg" alt="Test image">'
        ]);
        
        $post = get_post($post_id);
        $this->assertStringContainsString('alt="', $post->post_content);
    }

    public function test_form_labels() {
        ob_start();
        get_search_form();
        $form = ob_get_clean();
        
        $this->assertStringContainsString('<label', $form);
        $this->assertStringContainsString('for="', $form);
    }

    public function test_color_contrast() {
        $colors = [
            'background' => '#ffffff',
            'text' => '#333333'
        ];
        
        $contrast_ratio = $this->calculate_contrast_ratio($colors['background'], $colors['text']);
        $this->assertGreaterThan(4.5, $contrast_ratio);
    }

    private function calculate_contrast_ratio($bg, $fg) {
        // Yksinkertainen kontrastisuhteen laskenta WCAG 2.1 mukaisesti
        $bg = $this->hex_to_luminance($bg);
        $fg = $this->hex_to_luminance($fg);
        
        $l1 = max($bg, $fg);
        $l2 = min($bg, $fg);
        
        return ($l1 + 0.05) / ($l2 + 0.05);
    }

    private function hex_to_luminance($hex) {
        $rgb = sscanf($hex, "#%02x%02x%02x");
        $r = $rgb[0] / 255;
        $g = $rgb[1] / 255;
        $b = $rgb[2] / 255;
        
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
