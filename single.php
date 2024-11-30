<?php
/**
 * Yksittäisen artikkelin pohja
 *
 * @package TonysTheme
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="container">
            <div class="content-sidebar-wrap">
                <div class="content-area">
                    <?php
                    while (have_posts()) :
                        the_post();
                        ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <header class="entry-header">
                                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>

                                <div class="entry-meta">
                                    <span class="posted-on">
                                        <?php
                                        printf(
                                            /* translators: %s: post date */
                                            esc_html_x('Julkaistu %s', 'post date', 'tonys-theme'),
                                            '<time datetime="' . esc_attr(get_the_date('c')) . '">' . esc_html(get_the_date()) . '</time>'
                                        );
                                        ?>
                                    </span>

                                    <span class="byline">
                                        <?php
                                        printf(
                                            /* translators: %s: post author */
                                            esc_html_x('Kirjoittanut %s', 'post author', 'tonys-theme'),
                                            '<a href="' . esc_url(get_author_posts_url(get_the_author_meta('ID'))) . '">' . esc_html(get_the_author()) . '</a>'
                                        );
                                        ?>
                                    </span>

                                    <?php
                                    $categories_list = get_the_category_list(esc_html__(', ', 'tonys-theme'));
                                    if ($categories_list) {
                                        printf(
                                            /* translators: 1: list of categories */
                                            '<span class="cat-links">%1$s</span>',
                                            $categories_list // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        );
                                    }
                                    ?>
                                </div>
                            </header>

                            <?php if (has_post_thumbnail()) : ?>
                                <div class="post-thumbnail">
                                    <?php the_post_thumbnail('featured-large', array('class' => 'featured-image')); ?>
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

                            <footer class="entry-footer">
                                <?php
                                $tags_list = get_the_tag_list('', esc_html__(', ', 'tonys-theme'));
                                if ($tags_list) {
                                    printf(
                                        /* translators: 1: list of tags */
                                        '<div class="tags-links">%1$s</div>',
                                        $tags_list // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    );
                                }

                                // Jaa artikkelisi -osio
                                if (function_exists('sharing_display')) {
                                    sharing_display('', true);
                                }

                                // Kirjoittajan tiedot
                                $author_bio = get_the_author_meta('description');
                                if ($author_bio) :
                                    ?>
                                    <div class="author-bio">
                                        <h2 class="author-title">
                                            <?php
                                            printf(
                                                /* translators: %s: post author */
                                                esc_html__('Kirjoittaja: %s', 'tonys-theme'),
                                                '<span class="author-name">' . get_the_author() . '</span>'
                                            );
                                            ?>
                                        </h2>
                                        <div class="author-avatar">
                                            <?php echo get_avatar(get_the_author_meta('ID'), 100); ?>
                                        </div>
                                        <div class="author-description">
                                            <?php echo wp_kses_post($author_bio); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </footer>
                        </article>

                        <?php
                        // Jos kommentit ovat käytössä ja artikkeli ei ole suojattu
                        if (comments_open() || get_comments_number()) :
                            comments_template();
                        endif;

                        // Navigaatio edelliseen/seuraavaan artikkeliin
                        the_post_navigation(array(
                            'prev_text' => '<span class="nav-subtitle">' . esc_html__('Edellinen:', 'tonys-theme') . '</span> <span class="nav-title">%title</span>',
                            'next_text' => '<span class="nav-subtitle">' . esc_html__('Seuraava:', 'tonys-theme') . '</span> <span class="nav-title">%title</span>',
                        ));

                    endwhile;
                    ?>
                </div>

                <?php get_sidebar(); ?>
            </div>
        </div>
    </main>
</div>

<?php get_footer(); ?>
