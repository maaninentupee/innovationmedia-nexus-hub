<?php
/**
 * Sivuston header
 *
 * @package TonysTheme
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <!-- DNS Prefetch kriittisille resursseille -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="dns-prefetch" href="//ajax.googleapis.com">
    <link rel="dns-prefetch" href="//www.google-analytics.com">
    
    <!-- Preconnect fontit -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Preload kriittiset resurssit -->
    <link rel="preload" href="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/css/critical.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/css/critical.css"></noscript>
    <link rel="preload" href="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/fonts/montserrat-v25-latin-regular.woff2" as="font" type="font/woff2" crossorigin>
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?> itemscope itemtype="https://schema.org/WebPage">
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary">
        <?php esc_html_e('Siirry sisältöön', 'tonys-theme'); ?>
    </a>

    <header id="masthead" class="site-header" role="banner" itemscope itemtype="https://schema.org/WPHeader">
        <div class="container">
            <div class="site-branding" itemscope itemtype="https://schema.org/Organization">
                <?php
                if (has_custom_logo()) :
                    $custom_logo_id = get_theme_mod('custom_logo');
                    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                    ?>
                    <div class="site-logo" itemprop="logo">
                        <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                            <img src="<?php echo esc_url($logo[0]); ?>" 
                                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                                 width="<?php echo esc_attr($logo[1]); ?>"
                                 height="<?php echo esc_attr($logo[2]); ?>"
                                 loading="eager"
                                 fetchpriority="high">
                        </a>
                    </div>
                <?php else : ?>
                    <?php if (is_front_page() && is_home()) : ?>
                        <h1 class="site-title" itemprop="name">
                            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                                <?php bloginfo('name'); ?>
                            </a>
                        </h1>
                    <?php else : ?>
                        <p class="site-title" itemprop="name">
                            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                                <?php bloginfo('name'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <?php $description = get_bloginfo('description', 'display');
                    if ($description || is_customize_preview()) : ?>
                        <p class="site-description" itemprop="description">
                            <?php echo $description; ?>
                        </p>
                    <?php endif;
                endif; ?>
            </div>

            <nav id="site-navigation" class="main-navigation" role="navigation" 
                 aria-label="<?php esc_attr_e('Päävalikko', 'tonys-theme'); ?>"
                 itemscope itemtype="https://schema.org/SiteNavigationElement">
                <button class="mobile-menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="screen-reader-text"><?php esc_html_e('Valikko', 'tonys-theme'); ?></span>
                    <span class="menu-toggle-icon" aria-hidden="true"></span>
                </button>

                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'menu_class'     => 'nav-menu',
                    'container_class' => 'primary-menu-container',
                    'fallback_cb'    => false,
                ));
                ?>

                <?php if (get_theme_mod('show_search', true)) : ?>
                    <div class="header-search">
                        <?php get_search_form(); ?>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div id="content" class="site-content">
