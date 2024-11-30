<?php
/**
 * Template for displaying single affiliate products
 */

get_header();
?>

<div class="content-area">
    <main id="main" class="site-main">
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('affiliate-product'); ?>>
                <header class="entry-header">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="product-image">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>

                    <h1 class="entry-title"><?php the_title(); ?></h1>

                    <div class="product-meta">
                        <?php
                        $rating = get_post_meta(get_the_ID(), '_product_rating', true);
                        $price = get_post_meta(get_the_ID(), '_product_price', true);
                        $affiliate_url = get_post_meta(get_the_ID(), '_affiliate_url', true);
                        ?>

                        <?php if ($rating) : ?>
                            <div class="product-rating">
                                <?php echo do_shortcode('[product_rating]'); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($price) : ?>
                            <div class="product-price">
                                <span class="label"><?php _e('Price:', 'tonys-theme'); ?></span>
                                <span class="amount"><?php echo esc_html($price); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php
                        $categories = get_the_terms(get_the_ID(), TonysThemeAffiliateProducts::CATEGORY_TAX);
                        if ($categories && !is_wp_error($categories)) : ?>
                            <div class="product-categories">
                                <span class="label"><?php _e('Categories:', 'tonys-theme'); ?></span>
                                <?php echo get_the_term_list(get_the_ID(), TonysThemeAffiliateProducts::CATEGORY_TAX, '', ', '); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($affiliate_url) : ?>
                        <div class="product-cta">
                            <a href="<?php echo esc_url($affiliate_url); ?>" class="button button-primary" target="_blank" rel="nofollow sponsored">
                                <?php _e('Check Price', 'tonys-theme'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>

                    <?php
                    $pros = get_post_meta(get_the_ID(), '_product_pros', true);
                    $cons = get_post_meta(get_the_ID(), '_product_cons', true);
                    
                    if ($pros || $cons) : ?>
                        <div class="pros-cons-wrapper">
                            <?php if ($pros) : ?>
                                <div class="pros">
                                    <h3><?php _e('Pros', 'tonys-theme'); ?></h3>
                                    <ul>
                                        <?php
                                        foreach (explode("\n", $pros) as $pro) {
                                            if (trim($pro)) {
                                                echo '<li>' . esc_html(trim($pro)) . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if ($cons) : ?>
                                <div class="cons">
                                    <h3><?php _e('Cons', 'tonys-theme'); ?></h3>
                                    <ul>
                                        <?php
                                        foreach (explode("\n", $cons) as $con) {
                                            if (trim($con)) {
                                                echo '<li>' . esc_html(trim($con)) . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <footer class="entry-footer">
                    <?php
                    $tags = get_the_terms(get_the_ID(), TonysThemeAffiliateProducts::TAG_TAX);
                    if ($tags && !is_wp_error($tags)) : ?>
                        <div class="product-tags">
                            <span class="label"><?php _e('Tags:', 'tonys-theme'); ?></span>
                            <?php echo get_the_term_list(get_the_ID(), TonysThemeAffiliateProducts::TAG_TAX, '', ', '); ?>
                        </div>
                    <?php endif; ?>

                    <div class="product-disclaimer">
                        <p class="disclaimer-text">
                            <?php _e('This post may contain affiliate links. We may earn a commission if you make a purchase through these links.', 'tonys-theme'); ?>
                        </p>
                    </div>
                </footer>
            </article>

            <?php
            // If comments are open or we have at least one comment, load up the comment template.
            if (comments_open() || get_comments_number()) :
                comments_template();
            endif;
            ?>

        <?php endwhile; ?>
    </main>
</div>

<?php
get_sidebar();
get_footer();
