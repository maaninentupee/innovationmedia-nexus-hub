<?php
/**
 * SEO Optimizer Class
 * 
 * Handles various SEO optimizations for the WordPress theme.
 * 
 * @package TonysTheme
 */

class TonysTheme_SEO_Optimizer {
    /**
     * Instance of this class.
     *
     * @var TonysTheme_SEO_Optimizer
     */
    private static $instance = null;

    /**
     * Get instance of this class.
     *
     * @return TonysTheme_SEO_Optimizer
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Add meta tags to head
        add_action( 'wp_head', array( $this, 'add_meta_tags' ), 1 );
        
        // Optimize title tag
        add_filter( 'pre_get_document_title', array( $this, 'optimize_title' ), 10 );
        
        // Add schema markup
        add_action( 'wp_footer', array( $this, 'add_schema_markup' ) );
        
        // Optimize permalinks
        add_filter( 'post_link', array( $this, 'optimize_permalinks' ), 10, 2 );
        
        // Add image alt text automatically if missing
        add_filter( 'the_content', array( $this, 'add_image_alt' ) );
        
        // Add XML sitemap support
        add_action( 'init', array( $this, 'register_sitemap' ) );
        
        // Add breadcrumbs support
        add_action( 'tonys_theme_before_content', array( $this, 'add_breadcrumbs' ) );

        // Add internal linking optimization
        add_filter( 'the_content', array( $this, 'optimize_internal_links' ), 20 );
        
        // Add Twitter username field to user profile
        add_action( 'init', array( $this, 'add_twitter_profile_field' ) );

        // Add heading structure optimization
        add_filter( 'the_content', array( $this, 'optimize_heading_structure' ), 5 );
        add_action( 'admin_notices', array( $this, 'heading_structure_admin_notice' ) );
    }

    /**
     * Sanitoi meta-tagin sisältö
     *
     * @param string $content Meta-tagin sisältö
     * @param string $type Meta-tagin tyyppi
     * @return string Sanitoitu sisältö
     */
    private function sanitize_meta_content($content, $type = 'text') {
        // Poista HTML ja PHP
        $content = wp_strip_all_tags($content);
        
        // Poista ylimääräiset välilyönnit
        $content = preg_replace('/\s+/', ' ', trim($content));
        
        switch ($type) {
            case 'title':
                // Rajoita pituus (60 merkkiä)
                $content = mb_substr($content, 0, 60);
                break;
                
            case 'description':
                // Rajoita pituus (160 merkkiä)
                $content = mb_substr($content, 0, 160);
                break;
                
            case 'keywords':
                // Poista erikoismerkit ja rajoita avainsanojen määrä
                $keywords = explode(',', $content);
                $keywords = array_slice($keywords, 0, 10);
                $keywords = array_map('trim', $keywords);
                $keywords = array_map('sanitize_text_field', $keywords);
                $content = implode(', ', $keywords);
                break;
                
            case 'robots':
                // Salli vain validit robotit-direktiivit
                $valid_directives = array('index', 'noindex', 'follow', 'nofollow', 'archive', 'noarchive');
                $directives = explode(',', $content);
                $directives = array_map('trim', $directives);
                $directives = array_intersect($directives, $valid_directives);
                $content = implode(', ', $directives);
                break;
        }
        
        return esc_attr($content);
    }

    /**
     * Add meta tags to head.
     */
    public function add_meta_tags() {
        global $post;

        // Basic meta tags
        echo '<meta name="robots" content="' . $this->sanitize_meta_content('index, follow', 'robots') . '" />' . "\n";
        
        if ( is_single() || is_page() ) {
            // Get post excerpt or generate one from content
            $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words( strip_shortcodes( $post->post_content ), 20 );
            
            echo '<meta name="description" content="' . $this->sanitize_meta_content($excerpt, 'description') . '" />' . "\n";
            
            // Open Graph tags
            echo '<meta property="og:title" content="' . esc_attr( get_the_title() ) . '" />' . "\n";
            echo '<meta property="og:description" content="' . $this->sanitize_meta_content($excerpt, 'description') . '" />' . "\n";
            echo '<meta property="og:type" content="article" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '" />' . "\n";
            
            // Get featured image
            if ( has_post_thumbnail() ) {
                $img_src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
                echo '<meta property="og:image" content="' . esc_url( $img_src[0] ) . '" />' . "\n";
            }

            // Twitter Card tags
            echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr( get_the_title() ) . '" />' . "\n";
            echo '<meta name="twitter:description" content="' . $this->sanitize_meta_content($excerpt, 'description') . '" />' . "\n";
            
            // Add Twitter site username if set
            $twitter_username = $this->get_twitter_username();
            if ( ! empty( $twitter_username ) ) {
                echo '<meta name="twitter:site" content="@' . esc_attr( $twitter_username ) . '" />' . "\n";
            }
            
            // Add author's Twitter handle if available
            $author_twitter = get_the_author_meta( 'twitter' );
            if ( ! empty( $author_twitter ) ) {
                echo '<meta name="twitter:creator" content="@' . esc_attr( $author_twitter ) . '" />' . "\n";
            }
            
            // Add featured image for Twitter
            if ( has_post_thumbnail() ) {
                $img_src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
                echo '<meta name="twitter:image" content="' . esc_url( $img_src[0] ) . '" />' . "\n";
                
                // Add image alt text for accessibility
                $img_alt = get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true );
                if ( ! empty( $img_alt ) ) {
                    echo '<meta name="twitter:image:alt" content="' . esc_attr( $img_alt ) . '" />' . "\n";
                }
            }
        }
    }

    /**
     * Optimize title tag.
     *
     * @param string $title The title.
     * @return string
     */
    public function optimize_title( $title ) {
        if ( is_front_page() ) {
            return get_bloginfo( 'name' ) . ' - ' . get_bloginfo( 'description' );
        }
        
        if ( is_single() || is_page() ) {
            return get_the_title() . ' - ' . get_bloginfo( 'name' );
        }
        
        if ( is_category() ) {
            return single_cat_title( '', false ) . ' - ' . get_bloginfo( 'name' );
        }
        
        if ( is_tag() ) {
            return single_tag_title( '', false ) . ' - ' . get_bloginfo( 'name' );
        }
        
        if ( is_author() ) {
            return get_the_author() . ' - ' . get_bloginfo( 'name' );
        }
        
        return $title;
    }

    /**
     * Add schema markup.
     */
    public function add_schema_markup() {
        if ( is_single() ) {
            global $post;
            
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => get_the_title(),
                'datePublished' => get_the_date( 'c' ),
                'dateModified' => get_the_modified_date( 'c' ),
                'author' => array(
                    '@type' => 'Person',
                    'name' => get_the_author()
                ),
                'publisher' => array(
                    '@type' => 'Organization',
                    'name' => get_bloginfo( 'name' ),
                    'logo' => array(
                        '@type' => 'ImageObject',
                        'url' => get_site_icon_url()
                    )
                )
            );
            
            if ( has_post_thumbnail() ) {
                $img_src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
                $schema['image'] = $img_src[0];
            }
            
            echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        }
    }

    /**
     * Optimize permalinks.
     *
     * @param string $permalink The permalink.
     * @param object $post The post object.
     * @return string
     */
    public function optimize_permalinks( $permalink, $post ) {
        // Remove stop words from permalinks
        $stop_words = array( 'a', 'an', 'the', 'in', 'on', 'at', 'to', 'for', 'of', 'with' );
        $post_slug = $post->post_name;
        
        foreach ( $stop_words as $word ) {
            $post_slug = str_replace( $word . '-', '', $post_slug );
            $post_slug = str_replace( '-' . $word, '', $post_slug );
        }
        
        return str_replace( $post->post_name, $post_slug, $permalink );
    }

    /**
     * Add image alt text automatically if missing.
     *
     * @param string $content The content.
     * @return string
     */
    public function add_image_alt( $content ) {
        if ( ! preg_match_all( '/<img[^>]+>/', $content, $matches ) ) {
            return $content;
        }
        
        foreach ( $matches[0] as $img ) {
            if ( ! preg_match( '/alt=[\'"](.*?)[\'"]/i', $img, $alt ) ) {
                $new_img = preg_replace( '/<img/', '<img alt="' . get_the_title() . '"', $img );
                $content = str_replace( $img, $new_img, $content );
            }
        }
        
        return $content;
    }

    /**
     * Register XML sitemap.
     */
    public function register_sitemap() {
        if ( ! is_admin() ) {
            add_action( 'do_feed_sitemap', array( $this, 'generate_sitemap' ), 10, 1 );
            add_feed( 'sitemap', array( $this, 'generate_sitemap' ) );
        }
    }

    /**
     * Generate XML sitemap.
     */
    public function generate_sitemap() {
        header( 'Content-Type: application/xml; charset=UTF-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Add homepage
        echo '<url>' . "\n";
        echo '<loc>' . esc_url( home_url( '/' ) ) . '</loc>' . "\n";
        echo '<changefreq>daily</changefreq>' . "\n";
        echo '<priority>1.0</priority>' . "\n";
        echo '</url>' . "\n";
        
        // Add posts
        $posts = get_posts( array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ) );
        
        foreach ( $posts as $post ) {
            echo '<url>' . "\n";
            echo '<loc>' . esc_url( get_permalink( $post ) ) . '</loc>' . "\n";
            echo '<lastmod>' . get_the_modified_date( 'c', $post ) . '</lastmod>' . "\n";
            echo '<changefreq>weekly</changefreq>' . "\n";
            echo '<priority>0.8</priority>' . "\n";
            echo '</url>' . "\n";
        }
        
        // Add pages
        $pages = get_pages();
        
        foreach ( $pages as $page ) {
            echo '<url>' . "\n";
            echo '<loc>' . esc_url( get_permalink( $page ) ) . '</loc>' . "\n";
            echo '<lastmod>' . get_the_modified_date( 'c', $page ) . '</lastmod>' . "\n";
            echo '<changefreq>monthly</changefreq>' . "\n";
            echo '<priority>0.6</priority>' . "\n";
            echo '</url>' . "\n";
        }
        
        echo '</urlset>';
        exit;
    }

    /**
     * Add breadcrumbs.
     */
    public function add_breadcrumbs() {
        if ( is_front_page() ) {
            return;
        }
        
        echo '<div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">';
        echo '<span property="itemListElement" typeof="ListItem">';
        echo '<a property="item" typeof="WebPage" href="' . esc_url( home_url() ) . '">';
        echo '<span property="name">' . esc_html__( 'Home', 'tonys-theme' ) . '</span></a>';
        echo '<meta property="position" content="1">';
        echo '</span>';
        
        if ( is_single() ) {
            $categories = get_the_category();
            if ( ! empty( $categories ) ) {
                echo ' &gt; ';
                echo '<span property="itemListElement" typeof="ListItem">';
                echo '<a property="item" typeof="WebPage" href="' . esc_url( get_category_link( $categories[0]->term_id ) ) . '">';
                echo '<span property="name">' . esc_html( $categories[0]->name ) . '</span></a>';
                echo '<meta property="position" content="2">';
                echo '</span>';
            }
            echo ' &gt; ';
            echo '<span property="itemListElement" typeof="ListItem">';
            echo '<span property="name">' . esc_html( get_the_title() ) . '</span>';
            echo '<meta property="position" content="3">';
            echo '</span>';
        } elseif ( is_page() ) {
            echo ' &gt; ';
            echo '<span property="itemListElement" typeof="ListItem">';
            echo '<span property="name">' . esc_html( get_the_title() ) . '</span>';
            echo '<meta property="position" content="2">';
            echo '</span>';
        }
        
        echo '</div>';
    }

    /**
     * Optimize internal links by finding and adding relevant internal links to content.
     *
     * @param string $content The post content.
     * @return string Modified content with optimized internal links.
     */
    public function optimize_internal_links( $content ) {
        // Don't process if we're in admin or if it's not a main query
        if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        // Get current post
        $current_post = get_post();
        if ( ! $current_post || empty( $content ) ) {
            return $content;
        }

        // Get keywords from current post
        $keywords = $this->get_post_keywords( $current_post );

        // Find related posts based on keywords
        $related_posts = $this->find_related_posts( $keywords, $current_post->ID );

        // Add links to content
        foreach ( $related_posts as $related_post ) {
            // Get the title and create a link
            $title = get_the_title( $related_post );
            $link = sprintf(
                '<a href="%s" title="%s">%s</a>',
                esc_url( get_permalink( $related_post ) ),
                esc_attr( $title ),
                esc_html( $title )
            );

            // Try to find an unlinked mention of the title in content
            $pattern = '/\b' . preg_quote( $title, '/' ) . '\b(?![^<]*>)/i';
            
            // Only add link if title is found and it's not already linked
            if ( preg_match( $pattern, $content ) && ! strpos( $content, $link ) ) {
                $content = preg_replace( $pattern, $link, $content, 1 );
            }
        }

        return $content;
    }

    /**
     * Get keywords from post content and title.
     *
     * @param WP_Post $post The post object.
     * @return array Array of keywords.
     */
    private function get_post_keywords( $post ) {
        // Combine title and content
        $text = $post->post_title . ' ' . strip_tags( $post->post_content );
        
        // Convert to lowercase and remove special characters
        $text = strtolower( $text );
        $text = preg_replace( '/[^a-z0-9\s]/', '', $text );
        
        // Split into words
        $words = str_word_count( $text, 1 );
        
        // Remove common stop words
        $stop_words = array( 'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by' );
        $words = array_diff( $words, $stop_words );
        
        // Count word frequency
        $word_count = array_count_values( $words );
        
        // Sort by frequency
        arsort( $word_count );
        
        // Return top 5 keywords
        return array_slice( array_keys( $word_count ), 0, 5 );
    }

    /**
     * Find related posts based on keywords.
     *
     * @param array $keywords Array of keywords to match.
     * @param int $exclude_id Post ID to exclude from results.
     * @return array Array of post IDs.
     */
    private function find_related_posts( $keywords, $exclude_id ) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'post__not_in' => array( $exclude_id ),
            's' => implode( ' ', $keywords ),
            'orderby' => 'relevance',
        );

        $query = new WP_Query( $args );
        
        $related_posts = array();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $related_posts[] = get_the_ID();
            }
            wp_reset_postdata();
        }

        return $related_posts;
    }

    /**
     * Get Twitter username from theme options.
     *
     * @return string Twitter username without @ symbol.
     */
    private function get_twitter_username() {
        // Try to get from theme options first
        $twitter = get_theme_mod( 'tonys_theme_twitter_username' );
        
        // If not set in theme options, try to get from WordPress settings
        if ( empty( $twitter ) ) {
            $twitter = get_option( 'tonys_theme_twitter_username' );
        }
        
        // Remove @ symbol if present
        return trim( str_replace( '@', '', $twitter ) );
    }

    /**
     * Add Twitter username field to user profile.
     */
    public function add_twitter_profile_field() {
        add_action( 'show_user_profile', array( $this, 'twitter_user_profile_field' ) );
        add_action( 'edit_user_profile', array( $this, 'twitter_user_profile_field' ) );
        add_action( 'personal_options_update', array( $this, 'save_twitter_profile_field' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_twitter_profile_field' ) );
    }

    /**
     * Display Twitter username field in user profile.
     *
     * @param WP_User $user User object.
     */
    public function twitter_user_profile_field( $user ) {
        ?>
        <h3><?php esc_html_e( 'Social Media Information', 'tonys-theme' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="twitter"><?php esc_html_e( 'Twitter Username', 'tonys-theme' ); ?></label></th>
                <td>
                    <input type="text" 
                           name="twitter" 
                           id="twitter" 
                           value="<?php echo esc_attr( get_user_meta( $user->ID, 'twitter', true ) ); ?>" 
                           class="regular-text" 
                    />
                    <p class="description">
                        <?php esc_html_e( 'Enter your Twitter username without the @ symbol.', 'tonys-theme' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save Twitter username field from user profile.
     *
     * @param int $user_id User ID.
     * @return bool|void
     */
    public function save_twitter_profile_field( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        update_user_meta( $user_id, 'twitter', sanitize_text_field( $_POST['twitter'] ) );
    }

    /**
     * Optimize heading structure in content.
     *
     * @param string $content The post content.
     * @return string Modified content with optimized heading structure.
     */
    public function optimize_heading_structure( $content ) {
        // Don't process if we're in admin or if it's not a main query
        if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        // Store original content for comparison
        $original_content = $content;

        // Get all headings
        preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $content, $headings, PREG_SET_ORDER );

        if ( empty( $headings ) ) {
            // Store warning if no headings found
            $this->store_heading_warning( get_the_ID(), 'no_headings', 'Sisällöstä ei löytynyt otsikoita.' );
            return $content;
        }

        // Check heading hierarchy
        $previous_level = 0;
        $has_h1 = false;
        $warnings = array();

        foreach ( $headings as $heading ) {
            $level = (int) $heading[1];
            $text = strip_tags( $heading[2] );

            // Check for empty headings
            if ( empty( trim( $text ) ) ) {
                $warnings[] = 'Tyhjä otsikko löydetty (H' . $level . ').';
                continue;
            }

            // Check for H1
            if ( $level === 1 ) {
                if ( $has_h1 ) {
                    $warnings[] = 'Useita H1-otsikoita löydetty. Käytä vain yhtä H1-otsikkoa per sivu.';
                }
                $has_h1 = true;
            }

            // Check heading hierarchy
            if ( $previous_level > 0 && $level > $previous_level + 1 ) {
                $warnings[] = 'Otsikkotasoissa on aukko: H' . $previous_level . ' jälkeen tulee H' . $level . '.';
            }

            // Check heading length
            if ( strlen( $text ) > 60 ) {
                $warnings[] = 'Otsikko "' . substr( $text, 0, 30 ) . '..." on liian pitkä (yli 60 merkkiä).';
            }

            $previous_level = $level;
        }

        // Store warnings for admin notice
        if ( ! empty( $warnings ) ) {
            $this->store_heading_warning( get_the_ID(), 'structure', $warnings );
        }

        // Try to fix common issues
        $fixed_content = $content;

        // Fix multiple H1s by converting extras to H2
        if ( $has_h1 ) {
            $h1_count = 0;
            $fixed_content = preg_replace_callback( '/<h1([^>]*)>(.*?)<\/h1>/i',
                function( $matches ) use ( &$h1_count ) {
                    $h1_count++;
                    return $h1_count === 1 ? $matches[0] : '<h2' . $matches[1] . '>' . $matches[2] . '</h2>';
                },
                $fixed_content
            );
        }

        // Add H1 if missing (use post title)
        if ( ! $has_h1 && ! is_archive() && ! is_home() ) {
            $post_title = get_the_title();
            $fixed_content = '<h1 class="entry-title">' . esc_html( $post_title ) . '</h1>' . "\n\n" . $fixed_content;
        }

        return $fixed_content;
    }

    /**
     * Store heading structure warnings in post meta.
     *
     * @param int $post_id Post ID.
     * @param string $type Warning type.
     * @param mixed $warnings Warning message or array of warnings.
     */
    private function store_heading_warning( $post_id, $type, $warnings ) {
        if ( ! $post_id ) {
            return;
        }

        $current_warnings = get_post_meta( $post_id, '_heading_warnings', true );
        if ( ! is_array( $current_warnings ) ) {
            $current_warnings = array();
        }

        $current_warnings[$type] = $warnings;
        update_post_meta( $post_id, '_heading_warnings', $current_warnings );
    }

    /**
     * Display heading structure warnings in admin.
     */
    public function heading_structure_admin_notice() {
        global $post;
        if ( ! $post ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || $screen->base !== 'post' ) {
            return;
        }

        $warnings = get_post_meta( $post->ID, '_heading_warnings', true );
        if ( empty( $warnings ) ) {
            return;
        }

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<h3>SEO: Otsikkorakenteen varoitukset</h3>';
        echo '<ul>';

        foreach ( $warnings as $type => $messages ) {
            if ( is_array( $messages ) ) {
                foreach ( $messages as $message ) {
                    echo '<li>' . esc_html( $message ) . '</li>';
                }
            } else {
                echo '<li>' . esc_html( $messages ) . '</li>';
            }
        }

        echo '</ul>';
        echo '<p>Suositukset otsikkorakenteelle:</p>';
        echo '<ul>';
        echo '<li>Käytä vain yhtä H1-otsikkoa per sivu</li>';
        echo '<li>Varmista että otsikkotasot etenevät järjestyksessä (H1 → H2 → H3 jne.)</li>';
        echo '<li>Pidä otsikot ytimekkäinä (alle 60 merkkiä)</li>';
        echo '<li>Käytä otsikoita sisällön jäsentelyyn</li>';
        echo '</ul>';
        echo '</div>';

        // Clear warnings after displaying them
        delete_post_meta( $post->ID, '_heading_warnings' );
    }
}

// Initialize the SEO optimizer
TonysTheme_SEO_Optimizer::get_instance();
