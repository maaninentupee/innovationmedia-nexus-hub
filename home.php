<?php
/**
 * Blogiarkiston pohja
 *
 * @package TonysTheme
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="container">
            <div class="content-sidebar-wrap">
                <div class="content-area">
                    <?php if (have_posts()) : ?>
                        <header class="page-header">
                            <h1 class="page-title">
                                <?php
                                if (is_home() && !is_front_page()) {
                                    single_post_title();
                                } else {
                                    esc_html_e('Blogi', 'tonys-theme');
                                }
                                ?>
                            </h1>
                        </header>

                        <div class="posts-grid">
                            <?php
                            while (have_posts()) :
                                the_post();
                                ?>
                                <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
                                    <?php if (has_post_thumbnail()) : ?>
                                        <div class="post-thumbnail">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('featured-medium'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="post-content">
                                        <header class="entry-header">
                                            <?php
                                            the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '">', '</a></h2>');
                                            ?>
                                            <div class="entry-meta">
                                                <span class="posted-on">
                                                    <?php echo get_the_date(); ?>
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

                                        <div class="entry-summary">
                                            <?php the_excerpt(); ?>
                                        </div>

                                        <footer class="entry-footer">
                                            <a href="<?php the_permalink(); ?>" class="read-more">
                                                <?php esc_html_e('Lue lisää', 'tonys-theme'); ?>
                                                <span class="screen-reader-text">
                                                    <?php echo get_the_title(); ?>
                                                </span>
                                            </a>
                                        </footer>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>

                        <?php
                        the_posts_pagination(array(
                            'prev_text'          => '<span class="screen-reader-text">' . __('Edellinen sivu', 'tonys-theme') . '</span>',
                            'next_text'          => '<span class="screen-reader-text">' . __('Seuraava sivu', 'tonys-theme') . '</span>',
                            'before_page_number' => '<span class="meta-nav screen-reader-text">' . __('Sivu', 'tonys-theme') . ' </span>',
                        ));
                        ?>

                    <?php else : ?>
                        <p><?php esc_html_e('Ei artikkeleita.', 'tonys-theme'); ?></p>
                    <?php endif; ?>
                </div>

                <?php get_sidebar('blog'); ?>
            </div>
        </div>
    </main>
</div>

<?php get_footer(); ?>
