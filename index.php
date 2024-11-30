<?php
/**
 * Pääsivupohja
 *
 * @package TonysTheme
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="container">
            <?php
            if (have_posts()) :
                while (have_posts()) :
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <header class="entry-header">
                            <?php
                            if (is_singular()) :
                                the_title('<h1 class="entry-title">', '</h1>');
                            else :
                                the_title(
                                    sprintf('<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url(get_permalink())),
                                    '</a></h2>'
                                );
                            endif;

                            if ('post' === get_post_type()) :
                                ?>
                                <div class="entry-meta">
                                    <span class="posted-on">
                                        <?php echo get_the_date(); ?>
                                    </span>
                                    <span class="byline">
                                        <?php echo get_the_author(); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </header>

                        <?php if (has_post_thumbnail() && !is_singular()) : ?>
                            <div class="post-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('featured-medium'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="entry-content">
                            <?php
                            if (is_singular()) :
                                the_content();

                                wp_link_pages(array(
                                    'before' => '<div class="page-links">' . esc_html__('Sivut:', 'tonys-theme'),
                                    'after'  => '</div>',
                                ));
                            else :
                                the_excerpt();
                                ?>
                                <p class="read-more">
                                    <a href="<?php echo esc_url(get_permalink()); ?>" class="button">
                                        <?php esc_html_e('Lue lisää', 'tonys-theme'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if (is_singular()) : ?>
                            <footer class="entry-footer">
                                <?php
                                $categories = get_the_category_list(esc_html__(', ', 'tonys-theme'));
                                if ($categories) :
                                    printf('<span class="cat-links">%s %s</span>',
                                        esc_html__('Kategoriat:', 'tonys-theme'),
                                        $categories
                                    );
                                endif;

                                $tags = get_the_tag_list('', esc_html__(', ', 'tonys-theme'));
                                if ($tags) :
                                    printf('<span class="tags-links">%s %s</span>',
                                        esc_html__('Avainsanat:', 'tonys-theme'),
                                        $tags
                                    );
                                endif;
                                ?>
                            </footer>
                        <?php endif; ?>
                    </article>

                    <?php
                    if (is_singular()) :
                        // Jos kommentit ovat käytössä ja artikkeli ei ole suojattu
                        if (comments_open() || get_comments_number()) :
                            comments_template();
                        endif;
                    endif;

                endwhile;

                // Navigaatio
                the_posts_pagination(array(
                    'prev_text' => esc_html__('Edellinen', 'tonys-theme'),
                    'next_text' => esc_html__('Seuraava', 'tonys-theme'),
                ));

            else :
                ?>
                <div class="no-results">
                    <h1 class="page-title"><?php esc_html_e('Ei sisältöä', 'tonys-theme'); ?></h1>
                    <div class="page-content">
                        <p><?php esc_html_e('Valitettavasti mitään ei löytynyt.', 'tonys-theme'); ?></p>
                        <?php get_search_form(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>
