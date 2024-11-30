<?php
/**
 * Yhteensopivuustestit
 */
class CompatibilityTest extends WP_UnitTestCase {
    private $compatibility_checker;

    public function setUp() {
        parent::setUp();
        $this->compatibility_checker = new TonysTheme_Compatibility_Checker();
    }

    /**
     * Testaa WordPress-version yhteensopivuutta
     */
    public function test_wordpress_compatibility() {
        $versions = array('5.9', '6.0', '6.1', '6.2', '6.3');

        foreach ($versions as $version) {
            $result = $this->compatibility_checker->check_wordpress_version($version);
            $this->assertTrue($result, "Teema ei ole yhteensopiva WordPress-version {$version} kanssa");
        }
    }

    /**
     * Testaa PHP-version yhteensopivuutta
     */
    public function test_php_compatibility() {
        $versions = array('7.4', '8.0', '8.1', '8.2');

        foreach ($versions as $version) {
            $result = $this->compatibility_checker->check_php_version($version);
            $this->assertTrue($result, "Teema ei ole yhteensopiva PHP-version {$version} kanssa");
        }
    }

    /**
     * Testaa lisäosien yhteensopivuutta
     */
    public function test_plugin_compatibility() {
        $plugins = array(
            'jetpack/jetpack.php' => '12.0',
            'woocommerce/woocommerce.php' => '8.0',
            'wordpress-seo/wp-seo.php' => '20.0'
        );

        foreach ($plugins as $plugin => $version) {
            $result = $this->compatibility_checker->check_plugin_compatibility($plugin, $version);
            $this->assertTrue($result, "Teema ei ole yhteensopiva lisäosan {$plugin} version {$version} kanssa");
        }
    }

    /**
     * Testaa teeman toimintoja eri ympäristöissä
     */
    public function test_feature_compatibility() {
        // Testaa kuvien WebP-tukea
        $this->assertTrue(
            $this->compatibility_checker->check_webp_support(),
            'WebP-tuki puuttuu'
        );

        // Testaa välimuistin toimintaa
        $this->assertTrue(
            $this->compatibility_checker->check_cache_support(),
            'Välimuistituki puuttuu'
        );

        // Testaa REST API:n toimintaa
        $this->assertTrue(
            $this->compatibility_checker->check_rest_api_support(),
            'REST API -tuki puuttuu'
        );
    }

    /**
     * Testaa teeman asetuksia
     */
    public function test_theme_settings() {
        // Testaa teeman asetusten tallennusta
        $settings = array(
            'compression_quality' => 85,
            'cache_expiration' => 3600,
            'lazy_loading' => true
        );

        foreach ($settings as $key => $value) {
            update_option("tonys_theme_{$key}", $value);
            $this->assertEquals(
                $value,
                get_option("tonys_theme_{$key}"),
                "Asetuksen {$key} tallennus epäonnistui"
            );
        }
    }
}
