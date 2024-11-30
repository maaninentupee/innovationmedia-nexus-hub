<?php
/**
 * Sivuston footer
 *
 * @package TonysTheme
 */
?>

    <footer id="colophon" class="site-footer">
        <div class="container">
            <div class="footer-widgets">
                <?php if (is_active_sidebar('footer-1')) : ?>
                    <div class="footer-widget-area">
                        <?php dynamic_sidebar('footer-1'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="site-info">
                <div class="footer-navigation">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer',
                        'menu_id'        => 'footer-menu',
                        'menu_class'     => 'footer-menu',
                        'container'      => false,
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ));
                    ?>
                </div>

                <div class="footer-content">
                    <div class="social-links">
                        <?php
                        // Sosiaalisen median linkit (voit lisätä nämä Customizer-asetuksiin)
                        $social_links = array(
                            'facebook'  => get_theme_mod('facebook_url'),
                            'twitter'   => get_theme_mod('twitter_url'),
                            'instagram' => get_theme_mod('instagram_url'),
                            'linkedin'  => get_theme_mod('linkedin_url')
                        );

                        foreach ($social_links as $platform => $url) :
                            if ($url) :
                                printf(
                                    '<a href="%s" class="social-link %s" target="_blank" rel="noopener noreferrer">
                                        <span class="screen-reader-text">%s</span>
                                    </a>',
                                    esc_url($url),
                                    esc_attr($platform),
                                    esc_html(ucfirst($platform))
                                );
                            endif;
                        endforeach;
                        ?>
                    </div>

                    <div class="copyright">
                        <?php
                        printf(
                            /* translators: %1$s: Vuosi, %2$s: Sivuston nimi */
                            esc_html__('© %1$s %2$s. Kaikki oikeudet pidätetään.', 'tonys-theme'),
                            date_i18n(_x('Y', 'copyright date format', 'tonys-theme')),
                            get_bloginfo('name')
                        );
                        ?>
                    </div>

                    <?php if (get_theme_mod('show_privacy_links', true)) : ?>
                        <div class="privacy-links">
                            <?php
                            if (get_privacy_policy_url()) :
                                ?>
                                <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">
                                    <?php esc_html_e('Tietosuojaseloste', 'tonys-theme'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
