<?php
/**
 * Sosiaalisen median jakamistoiminnot
 *
 * @package TonysTheme
 */

class TonysTheme_Social_Sharing {
    /**
     * Alusta jakamistoiminnot
     */
    public static function init() {
        // Lisää jakamisnapit sisältöön
        add_filter('the_content', [__CLASS__, 'add_sharing_buttons']);
        
        // Lisää meta-tagit jakamista varten
        add_action('wp_head', [__CLASS__, 'add_social_meta_tags']);
        
        // Lisää tyylit
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_styles']);
    }

    /**
     * Lisää jakamisnapit
     */
    public static function add_sharing_buttons($content) {
        // Näytä vain yksittäisissä artikkeleissa ja sivuissa
        if (!is_singular(['post', 'page'])) {
            return $content;
        }

        // Hae jakamislinkit
        $buttons = self::get_sharing_buttons();
        
        // Lisää napit sisällön jälkeen
        return $content . $buttons;
    }

    /**
     * Hae jakamisnapit
     */
    private static function get_sharing_buttons() {
        $url = urlencode(get_permalink());
        $title = urlencode(get_the_title());
        $site_name = urlencode(get_bloginfo('name'));

        // Luo jakamislinkit
        $buttons = [
            'facebook' => [
                'url' => "https://www.facebook.com/sharer/sharer.php?u={$url}",
                'icon' => 'dashicons-facebook',
                'label' => __('Jaa Facebookissa', 'tonys-theme')
            ],
            'twitter' => [
                'url' => "https://twitter.com/intent/tweet?url={$url}&text={$title}",
                'icon' => 'dashicons-twitter',
                'label' => __('Jaa Twitterissä', 'tonys-theme')
            ],
            'linkedin' => [
                'url' => "https://www.linkedin.com/shareArticle?mini=true&url={$url}&title={$title}&source={$site_name}",
                'icon' => 'dashicons-linkedin',
                'label' => __('Jaa LinkedInissä', 'tonys-theme')
            ],
            'email' => [
                'url' => "mailto:?subject={$title}&body=" . urlencode(__('Katso tämä artikkeli: ', 'tonys-theme')) . $url,
                'icon' => 'dashicons-email',
                'label' => __('Lähetä sähköpostilla', 'tonys-theme')
            ]
        ];

        // Luo HTML
        $output = '<div class="social-sharing">';
        $output .= '<h4>' . esc_html__('Jaa artikkeli:', 'tonys-theme') . '</h4>';
        $output .= '<div class="sharing-buttons">';

        foreach ($buttons as $network => $data) {
            $output .= sprintf(
                '<a href="%s" class="share-button share-%s" target="_blank" rel="noopener noreferrer">
                    <span class="dashicons %s"></span>
                    <span class="screen-reader-text">%s</span>
                </a>',
                esc_url($data['url']),
                esc_attr($network),
                esc_attr($data['icon']),
                esc_html($data['label'])
            );
        }

        $output .= '</div></div>';

        return $output;
    }

    /**
     * Lisää meta-tagit
     */
    public static function add_social_meta_tags() {
        if (!is_singular()) {
            return;
        }

        // Hae artikkelin tiedot
        $title = get_the_title();
        $description = has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 20);
        $url = get_permalink();
        $image = get_the_post_thumbnail_url(null, 'large');

        // Open Graph -tagit
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        if ($image) {
            echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        }
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

        // Twitter Card -tagit
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        if ($image) {
            echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
        }
    }

    /**
     * Lisää tyylit
     */
    public static function enqueue_styles() {
        if (!is_singular()) {
            return;
        }

        // Rekisteröi Dashicons jos ei ole jo käytössä
        wp_enqueue_style('dashicons');

        // Lisää inline-tyylit
        $styles = '
        .social-sharing {
            margin: 2em 0;
            padding: 1em;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }
        .social-sharing h4 {
            margin: 0 0 0.5em;
            font-size: 1.1em;
        }
        .sharing-buttons {
            display: flex;
            gap: 0.5em;
            flex-wrap: wrap;
        }
        .share-button {
            display: inline-flex;
            align-items: center;
            padding: 0.5em;
            border-radius: 3px;
            color: #fff;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .share-button:hover {
            opacity: 0.9;
        }
        .share-facebook {
            background: #1877f2;
        }
        .share-twitter {
            background: #1da1f2;
        }
        .share-linkedin {
            background: #0077b5;
        }
        .share-email {
            background: #777;
        }
        .share-button .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }';

        wp_add_inline_style('dashicons', $styles);
    }
}

// Alusta jakamistoiminnot
TonysTheme_Social_Sharing::init();
