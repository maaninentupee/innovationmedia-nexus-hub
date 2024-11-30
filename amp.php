<?php
/**
 * AMP-template
 *
 * @package TonysTheme
 */

?>
<!doctype html>
<html amp <?php language_attributes(); ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <meta name="description" content="<?php echo esc_attr(get_bloginfo('description')); ?>">
    
    <?php do_action('amp_post_template_head', $this); ?>
    
    <style amp-custom>
        /* AMP-tyylit */
        :root {
            --vari-perus: #333;
            --vari-korostus: #007bff;
            --vari-tausta: #ffffff;
            --vari-teksti: #212529;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.6;
            margin: 0;
            color: var(--vari-teksti);
            background: var(--vari-tausta);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .site-header {
            background: var(--vari-tausta);
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .site-title {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .entry-title {
            font-size: 2rem;
            margin: 2rem 0 1rem;
        }
        
        .entry-content {
            margin: 1rem 0;
        }
        
        .entry-content img {
            max-width: 100%;
            height: auto;
        }
        
        .entry-meta {
            color: #666;
            font-size: 0.9rem;
            margin: 1rem 0;
        }
        
        .site-footer {
            background: #f8f9fa;
            padding: 2rem 0;
            margin-top: 2rem;
            text-align: center;
        }
    </style>
</head>

<body <?php body_class(); ?>>
    <header class="site-header">
        <div class="container">
            <h1 class="site-title">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <?php bloginfo('name'); ?>
                </a>
            </h1>
        </div>
    </header>

    <div class="container">
        <main id="main" role="main">
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class(); ?>>
                    <header class="entry-header">
                        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                        
                        <div class="entry-meta">
                            <?php echo esc_html(get_the_date()); ?>
                        </div>
                    </header>

                    <div class="entry-content">
                        <?php
                        // Muunna kuvat AMP-yhteensopiviksi
                        $content = get_the_content();
                        $content = apply_filters('the_content', $content);
                        $content = preg_replace('/<img([^>]+)>/i', '<amp-img$1 layout="responsive"></amp-img>', $content);
                        echo $content;
                        ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </main>
    </div>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo esc_html(date('Y')); ?> <?php bloginfo('name'); ?></p>
        </div>
    </footer>
    
    <?php do_action('amp_post_template_footer', $this); ?>
</body>
</html>
