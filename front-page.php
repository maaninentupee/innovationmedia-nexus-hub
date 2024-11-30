<?php
/**
 * Etusivun mukautettu pohja
 *
 * @package TonysTheme
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php if (have_posts()) : ?>
            <div class="hero-section">
                <div class="container">
                    <?php
                    // Näytä hero-osion sisältö, jos se on määritetty
                    if (get_theme_mod('front_hero_title')) : ?>
                        <h1><?php echo esc_html(get_theme_mod('front_hero_title')); ?></h1>
                    <?php endif;
                    
                    if (get_theme_mod('front_hero_text')) : ?>
                        <p class="hero-text"><?php echo esc_html(get_theme_mod('front_hero_text')); ?></p>
                    <?php endif; ?>

                    <?php if (get_theme_mod('front_hero_button_text') && get_theme_mod('front_hero_button_url')) : ?>
                        <a href="<?php echo esc_url(get_theme_mod('front_hero_button_url')); ?>" class="button hero-button">
                            <?php echo esc_html(get_theme_mod('front_hero_button_text')); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="featured-sections">
                <div class="container">
                    <div class="grid-layout">
                        <?php
                        // Näytä viimeisimmät artikkelit
                        $featured_posts = new WP_Query(array(
                            'posts_per_page' => 3,
                            'post_type'      => 'post',
                            'orderby'        => 'date',
                            'order'          => 'DESC',
                        ));

                        if ($featured_posts->have_posts()) :
                            while ($featured_posts->have_posts()) : $featured_posts->the_post(); ?>
                                <article class="featured-post">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <div class="featured-image">
                                            <?php the_post_thumbnail('featured-medium'); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="featured-content">
                                        <h2 class="entry-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h2>
                                        <?php the_excerpt(); ?>
                                        <a href="<?php the_permalink(); ?>" class="read-more">
                                            <?php esc_html_e('Lue lisää', 'tonys-theme'); ?>
                                        </a>
                                    </div>
                                </article>
                            <?php endwhile;
                            wp_reset_postdata();
                        endif; ?>
                    </div>
                </div>
            </div>

            <?php
            // Näytä sivun sisältö
            while (have_posts()) :
                the_post(); ?>
                <div class="page-content">
                    <div class="container">
                        <?php the_content(); ?>
                    </div>
                </div>
            <?php endwhile;

        endif; ?>

        <?php if (is_active_sidebar('front-page-widgets')) : ?>
            <div class="front-page-widgets">
                <div class="container">
                    <?php dynamic_sidebar('front-page-widgets'); ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php get_footer(); ?>
