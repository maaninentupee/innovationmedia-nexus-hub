<?php
/**
 * Arkistosivun template
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="container">
        <?php if (have_posts()) : ?>
            <header class="page-header">
                <?php
                the_archive_title('<h1 class="page-title">', '</h1>');
                the_archive_description('<div class="archive-description">', '</div>');
                ?>
            </header>

            <div class="archive-grid">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('template-parts/content', get_post_type());
                endwhile;
                ?>
            </div>

            <?php
            the_posts_pagination(array(
                'prev_text' => __('Edellinen', 'tonys-theme'),
                'next_text' => __('Seuraava', 'tonys-theme'),
            ));
        else :
            get_template_part('template-parts/content', 'none');
        endif;
        ?>
    </div>
</main>

<?php
get_sidebar();
get_footer();
