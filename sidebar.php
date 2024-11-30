<?php
/**
 * Sivupalkin näyttömalli
 *
 * @package TonysTheme
 */

// Jos sivupalkki ei ole aktiivinen, älä näytä mitään
if (!is_active_sidebar('sidebar-main') && !is_active_sidebar('sidebar-blog')) {
    return;
}

// Määritä mikä sivupalkki näytetään
$sidebar_id = 'sidebar-main';
if (is_home() || is_archive() || is_single()) {
    $sidebar_id = 'sidebar-blog';
}
?>

<aside id="secondary" class="widget-area">
    <div class="sidebar-inner">
        <?php
        // Tarkista onko hakupalkki käytössä
        if (get_theme_mod('show_sidebar_search', true)) :
            ?>
            <div class="sidebar-search">
                <?php get_search_form(); ?>
            </div>
        <?php endif; ?>

        <?php
        // Näytä sivupalkin vimpaimet
        if (is_active_sidebar($sidebar_id)) :
            dynamic_sidebar($sidebar_id);
        endif;
        ?>

        <?php
        // Näytä suositut artikkelit jos asetus on päällä
        if (get_theme_mod('show_popular_posts', true) && (is_home() || is_archive() || is_single())) :
            $popular_posts = new WP_Query(array(
                'posts_per_page' => 5,
                'meta_key'       => 'post_views_count',
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC'
            ));

            if ($popular_posts->have_posts()) :
                ?>
                <div class="widget popular-posts">
                    <h2 class="widget-title"><?php esc_html_e('Suosituimmat artikkelit', 'tonys-theme'); ?></h2>
                    <ul>
                        <?php
                        while ($popular_posts->have_posts()) :
                            $popular_posts->the_post();
                            ?>
                            <li>
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="post-thumbnail">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('thumbnail'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="post-content">
                                    <a href="<?php the_permalink(); ?>" class="post-title">
                                        <?php the_title(); ?>
                                    </a>
                                    <span class="post-date">
                                        <?php echo get_the_date(); ?>
                                    </span>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <?php
                wp_reset_postdata();
            endif;
        endif;
        ?>

        <?php
        // Näytä avainsanapilvi jos asetus on päällä
        if (get_theme_mod('show_tag_cloud', true) && (is_home() || is_archive() || is_single())) :
            $tags = get_tags(array('orderby' => 'count', 'order' => 'DESC', 'number' => 20));
            if ($tags) :
                ?>
                <div class="widget tag-cloud">
                    <h2 class="widget-title"><?php esc_html_e('Avainsanat', 'tonys-theme'); ?></h2>
                    <div class="tagcloud">
                        <?php
                        foreach ($tags as $tag) {
                            echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="tag-cloud-link">' 
                                . esc_html($tag->name) 
                                . '<span class="tag-link-count"> (' . esc_html($tag->count) . ')</span>'
                                . '</a>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif;
        endif;
        ?>

        <?php
        // Näytä sosiaalisen median linkit jos asetus on päällä
        if (get_theme_mod('show_social_sidebar', true)) :
            $social_links = array(
                'facebook'  => get_theme_mod('facebook_url'),
                'twitter'   => get_theme_mod('twitter_url'),
                'instagram' => get_theme_mod('instagram_url'),
                'linkedin'  => get_theme_mod('linkedin_url')
            );

            if (array_filter($social_links)) : // Näytä vain jos on vähintään yksi linkki
                ?>
                <div class="widget social-links">
                    <h2 class="widget-title"><?php esc_html_e('Seuraa meitä', 'tonys-theme'); ?></h2>
                    <div class="social-icons">
                        <?php
                        foreach ($social_links as $platform => $url) :
                            if ($url) :
                                printf(
                                    '<a href="%s" class="social-icon %s" target="_blank" rel="noopener noreferrer">
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
                </div>
            <?php endif;
        endif;
        ?>
    </div>
</aside>
