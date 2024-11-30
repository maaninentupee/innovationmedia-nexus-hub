<?php
/**
 * Teeman testausvalikko
 *
 * @package TonysTheme
 */

class TonysTheme_Tester_Menu {
    /**
     * Alusta valikko
     */
    public static function init() {
        // Lisää hallintavalikko
        add_action('admin_menu', [__CLASS__, 'add_theme_tester_menu']);
        
        // Käsittele testien ajo
        add_action('admin_init', [__CLASS__, 'handle_test_run']);
    }

    /**
     * Lisää testausvalikko WordPress-hallintapaneeliin
     */
    public static function add_theme_tester_menu() {
        add_theme_page(
            __('Teeman testaus', 'tonys-theme'),          // Sivun otsikko
            __('Teeman testaus', 'tonys-theme'),          // Valikon otsikko
            'manage_options',                             // Vaadittu käyttöoikeus
            'tonys-theme-tester',                         // Valikon tunniste
            [__CLASS__, 'render_tester_page']             // Sivun renderöintifunktio
        );
    }

    /**
     * Renderöi testaussivu
     */
    public static function render_tester_page() {
        // Tarkista käyttöoikeudet
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Testien käynnistyspainike -->
            <form method="post" action="">
                <?php wp_nonce_field('tonys_theme_run_tests', 'tonys_theme_tester_nonce'); ?>
                <p>
                    <input type="submit" 
                           name="tonys_theme_run_tests" 
                           class="button button-primary" 
                           value="<?php esc_attr_e('Suorita testit', 'tonys-theme'); ?>">
                </p>
            </form>

            <!-- Testien tulokset -->
            <?php 
            if (class_exists('TonysTheme_Compatibility_Tests')) {
                echo TonysTheme_Compatibility_Tests::display_test_results();
            }
            ?>
        </div>
        <?php
    }

    /**
     * Käsittele testien ajo
     */
    public static function handle_test_run() {
        // Tarkista onko testien ajo pyydetty
        if (!isset($_POST['tonys_theme_run_tests'])) {
            return;
        }

        // Tarkista nonce
        if (!isset($_POST['tonys_theme_tester_nonce']) || 
            !wp_verify_nonce($_POST['tonys_theme_tester_nonce'], 'tonys_theme_run_tests')) {
            wp_die(__('Turvallisuustarkistus epäonnistui.', 'tonys-theme'));
        }

        // Tarkista käyttöoikeudet
        if (!current_user_can('manage_options')) {
            wp_die(__('Sinulla ei ole oikeuksia tähän toimintoon.', 'tonys-theme'));
        }

        // Suorita testit
        if (class_exists('TonysTheme_Compatibility_Tests')) {
            TonysTheme_Compatibility_Tests::run_tests();
            
            // Lisää ilmoitus
            add_settings_error(
                'tonys_theme_messages',
                'tonys_theme_message',
                __('Testit suoritettu onnistuneesti.', 'tonys-theme'),
                'updated'
            );
        }
    }
}

// Alusta valikko
TonysTheme_Tester_Menu::init();
