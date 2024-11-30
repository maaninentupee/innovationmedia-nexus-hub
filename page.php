<?php
/**
 * Sivujen oletuspohja
 *
 * @package TonysTheme
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="container">
            <?php
            while (have_posts()) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <?php
                    // Näytä otsikko vain jos sitä ei ole piilotettu
                    if (!get_post_meta(get_the_ID(), '_hide_title', true)) :
                        ?>
                        <header class="entry-header">
                            <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                        </header>
                    <?php endif; ?>

                    <?php
                    // Näytä featured image jos sellainen on
                    if (has_post_thumbnail() && !get_post_meta(get_the_ID(), '_hide_featured_image', true)) :
                        ?>
                        <div class="featured-image">
                            <?php the_post_thumbnail('featured-large'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="entry-content">
                        <?php
                        the_content();

                        wp_link_pages(array(
                            'before' => '<div class="page-links">' . esc_html__('Sivut:', 'tonys-theme'),
                            'after'  => '</div>',
                        ));
                        ?>
                    </div>

                    <?php
                    // Näytä muokkauslinkit vain jos käyttäjä on kirjautunut sisään
                    if (get_edit_post_link()) :
                        ?>
                        <footer class="entry-footer">
                            <?php
                            edit_post_link(
                                sprintf(
                                    wp_kses(
                                        /* translators: %s: Name of current post. Only visible to screen readers */
                                        __('Muokkaa <span class="screen-reader-text">%s</span>', 'tonys-theme'),
                                        array(
                                            'span' => array(
                                                'class' => array(),
                                            ),
                                        )
                                    ),
                                    wp_kses_post(get_the_title())
                                ),
                                '<span class="edit-link">',
                                '</span>'
                            );
                            ?>
                        </footer>
                    <?php endif; ?>
                </article>

                <?php
                // Jos kommentit ovat käytössä ja sivu ei ole suojattu
                if (comments_open() || get_comments_number()) :
                    comments_template();
                endif;

            endwhile;
            ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>
