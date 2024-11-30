<?php
/**
 * Image Optimizer Class
 * 
 * Handles image optimization, compression, and lazy loading.
 * 
 * @package TonysTheme
 */

class TonysTheme_Image_Optimizer {
    /**
     * Instance of this class.
     *
     * @var TonysTheme_Image_Optimizer
     */
    private static $instance = null;

    /**
     * Default compression quality.
     *
     * @var int
     */
    private $compression_quality = 82;

    /**
     * Maximum image dimensions.
     *
     * @var array
     */
    private $max_dimensions = array(
        'width' => 2000,
        'height' => 2000
    );

    /**
     * Get instance of this class.
     *
     * @return TonysTheme_Image_Optimizer
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
        // Add lazy loading to images
        add_filter( 'the_content', array( $this, 'add_lazy_loading' ), 99 );
        add_filter( 'post_thumbnail_html', array( $this, 'add_lazy_loading' ), 99 );
        add_filter( 'widget_text', array( $this, 'add_lazy_loading' ), 99 );

        // Optimize uploaded images
        add_filter( 'wp_handle_upload', array( $this, 'optimize_uploaded_image' ) );
        
        // Add image compression quality filter
        add_filter( 'jpeg_quality', array( $this, 'set_compression_quality' ) );
        add_filter( 'wp_editor_set_quality', array( $this, 'set_compression_quality' ) );

        // Add WebP support
        add_filter( 'upload_mimes', array( $this, 'add_webp_support' ) );
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'generate_webp_version' ), 10, 2 );

        // Add image dimensions limit
        add_filter( 'wp_handle_upload_prefilter', array( $this, 'limit_image_dimensions' ) );

        // Add admin settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

        // Add automatic alt text generation
        add_action( 'add_attachment', array( $this, 'generate_alt_text' ) );
        add_filter( 'wp_get_attachment_image_attributes', array( $this, 'ensure_alt_text' ), 10, 2 );

        // Add image filename optimization
        add_filter( 'sanitize_file_name', array( $this, 'optimize_image_filename' ), 10, 1 );

        // Add automatic format selection
        add_filter( 'intermediate_image_sizes_advanced', array( $this, 'optimize_image_formats' ) );

        // Add image metadata optimization
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'optimize_image_metadata' ), 10, 2 );

        // Add responsive image support
        add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_responsive_attributes' ), 20, 2 );
        add_filter( 'the_content', array( $this, 'add_responsive_images' ), 20 );

        // Add image preloading
        add_action( 'wp_head', array( $this, 'preload_featured_images' ), 1 );

        // Add LQIP (Low Quality Image Placeholder) support
        add_filter( 'wp_get_attachment_image', array( $this, 'add_lqip_support' ), 20, 5 );

        // Add image error handling
        add_filter( 'wp_get_attachment_image', array( $this, 'add_image_error_handling' ), 30, 5 );

        // Add automatic image optimization scheduling
        add_action( 'init', array( $this, 'schedule_image_optimization' ) );
        add_action( 'tonys_theme_optimize_images', array( $this, 'batch_optimize_images' ) );

        // Add image srcset optimization
        add_filter( 'wp_calculate_image_srcset', array( $this, 'optimize_image_srcset' ), 10, 5 );

        // Add WebP fallback
        add_filter( 'wp_get_attachment_image', array( $this, 'add_webp_fallback' ), 10, 5 );
    }

    /**
     * Add lazy loading to images.
     *
     * @param string $content The content.
     * @return string Modified content with lazy loading.
     */
    public function add_lazy_loading( $content ) {
        if ( is_admin() || is_feed() ) {
            return $content;
        }

        // Don't lazy load if it's a REST API request
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return $content;
        }

        // Don't lazy load in print preview
        if ( function_exists( 'is_print_preview' ) && is_print_preview() ) {
            return $content;
        }

        // Add loading="lazy" to images that don't already have it
        $content = preg_replace_callback( '/<img([^>]+?)>/i',
            function( $matches ) {
                // Skip if already has loading attribute
                if ( strpos( $matches[1], 'loading=' ) !== false ) {
                    return $matches[0];
                }

                // Add loading="lazy" attribute
                return '<img' . $matches[1] . ' loading="lazy">';
            },
            $content
        );

        // Add noscript fallback for images
        $content = preg_replace_callback( '/<img([^>]+?)>/i',
            function( $matches ) {
                $img = $matches[0];
                $noscript = '<noscript>' . str_replace( ' loading="lazy"', '', $img ) . '</noscript>';
                return $img . $noscript;
            },
            $content
        );

        return $content;
    }

    /**
     * Sanitoi tiedostonimi
     *
     * @param string $filename Tiedostonimi
     * @return string Sanitoitu tiedostonimi
     */
    private function sanitize_filename($filename) {
        // Poista erikoismerkit
        $filename = sanitize_file_name($filename);
        
        // Muunna pieniksi kirjaimiksi
        $filename = strtolower($filename);
        
        // Poista ei-alfanumeeriset merkit paitsi - ja .
        $filename = preg_replace('/[^a-z0-9\-\.]/', '', $filename);
        
        // Poista peräkkäiset viivat
        $filename = preg_replace('/-+/', '-', $filename);
        
        // Poista viivat tiedostopäätteen edestä
        $filename = str_replace('-.', '.', $filename);
        
        return $filename;
    }

    /**
     * Optimoi ladattu kuva
     *
     * @param array $upload Latauksen tiedot
     * @return array Muokatut latauksen tiedot
     */
    public function optimize_uploaded_image($upload) {
        try {
            if (!isset($upload['file']) || !file_exists($upload['file'])) {
                throw new Exception('Tiedostoa ei löydy');
            }

            // Sanitoi tiedostonimi
            $pathinfo = pathinfo($upload['file']);
            $new_filename = $this->sanitize_filename($pathinfo['filename']) . '.' . $pathinfo['extension'];
            $new_path = $pathinfo['dirname'] . '/' . $new_filename;
            
            // Nimeä tiedosto uudelleen jos nimi muuttui
            if ($new_filename !== basename($upload['file'])) {
                rename($upload['file'], $new_path);
                $upload['file'] = $new_path;
                $upload['url'] = str_replace(basename($upload['url']), $new_filename, $upload['url']);
            }

            // Tarkista tiedoston koko
            $max_size = 10 * 1024 * 1024; // 10MB
            if (filesize($upload['file']) > $max_size) {
                throw new Exception('Tiedosto on liian suuri');
            }

            // Tarkista kuvan tyyppi
            if (!$this->is_image($upload['file'])) {
                return $upload;
            }

            // Tarkista muistin käyttö
            $memory_limit = ini_get('memory_limit');
            if ($memory_limit !== '-1') {
                $memory_needed = filesize($upload['file']) * 2.2; // Arvio muistin tarpeesta
                if ($memory_needed > $this->return_bytes($memory_limit)) {
                    throw new Exception('Muisti ei riitä kuvan käsittelyyn');
                }
            }

            // Optimoi kuva
            $image = wp_get_image_editor($upload['file']);
            if (is_wp_error($image)) {
                throw new Exception($image->get_error_message());
            }

            // Säilytä metadata
            $metadata = wp_read_image_metadata($upload['file']);
            
            // Muuta kuvakokoa tarvittaessa
            $dimensions = $image->get_size();
            if ($dimensions['width'] > 2048 || $dimensions['height'] > 2048) {
                $image->resize(2048, 2048);
            }

            // Optimoi laatu
            $image->set_quality(85);

            // Tallenna optimoitu kuva
            $saved = $image->save($upload['file']);
            if (is_wp_error($saved)) {
                throw new Exception($saved->get_error_message());
            }

            // Palauta metadata
            if ($metadata) {
                wp_update_attachment_metadata(get_the_ID(), $metadata);
            }

            // Luo WebP-versio
            $this->generate_webp_version($upload['file']);

            // Päivitä statistiikka
            $this->update_optimization_stats($upload['file']);

            return $upload;

        } catch (Exception $e) {
            error_log('TonysTheme Image Optimizer: ' . $e->getMessage());
            return $upload;
        }
    }

    /**
     * Muunna muistin raja tavuiksi
     */
    private function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }

    /**
     * Päivitä optimoinnin statistiikka
     */
    private function update_optimization_stats($file) {
        $stats = get_option('tonys_theme_image_stats', [
            'optimized_count' => 0,
            'total_saved' => 0,
            'last_optimization' => 0
        ]);

        $stats['optimized_count']++;
        $stats['total_saved'] += filesize($file);
        $stats['last_optimization'] = time();

        update_option('tonys_theme_image_stats', $stats);
    }

    /**
     * Generoi WebP-versio kuvasta
     */
    public function generate_webp_version($file) {
        try {
            // Tarkista GD/Imagick-tuki
            if (!function_exists('imagewebp')) {
                throw new Exception('WebP-tuki puuttuu');
            }

            // Luo WebP-tiedostonimi
            $webp_file = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);

            // Lataa kuva muistiin
            $image_type = exif_imagetype($file);
            switch($image_type) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($file);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($file);
                    break;
                default:
                    throw new Exception('Ei-tuettu kuvaformaatti');
            }

            if (!$image) {
                throw new Exception('Kuvan lataus epäonnistui');
            }

            // Säilytä läpinäkyvyys PNG-kuville
            if ($image_type === IMAGETYPE_PNG) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }

            // Tallenna WebP-versio
            imagewebp($image, $webp_file, 85);
            imagedestroy($image);

            // Optimoi tiedostokoko
            if (filesize($webp_file) >= filesize($file)) {
                unlink($webp_file);
                throw new Exception('WebP-versio suurempi kuin alkuperäinen');
            }

        } catch (Exception $e) {
            error_log('TonysTheme WebP Converter: ' . $e->getMessage());
        }
    }

    /**
     * Set compression quality.
     *
     * @param int $quality Original quality.
     * @return int Modified quality.
     */
    public function set_compression_quality( $quality ) {
        return $this->compression_quality;
    }

    /**
     * Add WebP support.
     *
     * @param array $mimes Allowed mime types.
     * @return array Modified mime types.
     */
    public function add_webp_support( $mimes ) {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    }

    /**
     * Generate WebP version of uploaded images.
     *
     * @param array $metadata Attachment metadata.
     * @param int $attachment_id Attachment ID.
     * @return array Modified metadata.
     */
    public function generate_webp_version( $metadata, $attachment_id ) {
        if ( ! function_exists( 'imagewebp' ) ) {
            return $metadata;
        }

        $file = get_attached_file( $attachment_id );
        if ( ! $this->is_image( $file ) ) {
            return $metadata;
        }

        $image = wp_get_image_editor( $file );
        if ( is_wp_error( $image ) ) {
            return $metadata;
        }

        // Generate WebP version
        $webp_file = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file );
        $saved = $image->save( $webp_file, 'image/webp' );

        if ( ! is_wp_error( $saved ) ) {
            $metadata['webp'] = basename( $webp_file );
        }

        return $metadata;
    }

    /**
     * Limit image dimensions on upload.
     *
     * @param array $file File data array.
     * @return array Modified file data array.
     */
    public function limit_image_dimensions( $file ) {
        $image = getimagesize( $file['tmp_name'] );
        if ( ! $image ) {
            return $file;
        }

        list( $width, $height ) = $image;

        if ( $width > $this->max_dimensions['width'] || $height > $this->max_dimensions['height'] ) {
            $file['error'] = sprintf(
                'Kuvan koko (%dx%d) ylittää sallitun maksimikoon (%dx%d).',
                $width,
                $height,
                $this->max_dimensions['width'],
                $this->max_dimensions['height']
            );
        }

        return $file;
    }

    /**
     * Check if file is an image.
     *
     * @param string $file File path.
     * @return bool True if file is an image.
     */
    private function is_image( $file ) {
        $image_types = array( 'jpg', 'jpeg', 'png', 'gif' );
        $type = wp_check_filetype( $file );
        return in_array( strtolower( $type['ext'] ), $image_types );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting( 'tonys_theme_image_options', 'tonys_theme_compression_quality', array(
            'type' => 'integer',
            'default' => 82,
            'sanitize_callback' => array( $this, 'sanitize_compression_quality' )
        ) );

        register_setting( 'tonys_theme_image_options', 'tonys_theme_max_dimensions', array(
            'type' => 'array',
            'default' => array(
                'width' => 2000,
                'height' => 2000
            ),
            'sanitize_callback' => array( $this, 'sanitize_dimensions' )
        ) );

        add_settings_section(
            'tonys_theme_image_settings',
            'Kuvien optimointiasetukset',
            array( $this, 'settings_section_callback' ),
            'tonys_theme_image_options'
        );

        add_settings_field(
            'compression_quality',
            'Pakkauksen laatu (0-100)',
            array( $this, 'compression_quality_callback' ),
            'tonys_theme_image_options',
            'tonys_theme_image_settings'
        );

        add_settings_field(
            'max_dimensions',
            'Maksimikoko (pikseleinä)',
            array( $this, 'max_dimensions_callback' ),
            'tonys_theme_image_options',
            'tonys_theme_image_settings'
        );
    }

    /**
     * Add settings page to admin menu.
     */
    public function add_settings_page() {
        add_submenu_page(
            'options-general.php',
            'Kuvien optimointi',
            'Kuvien optimointi',
            'manage_options',
            'tonys-theme-image-options',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Settings section callback.
     */
    public function settings_section_callback() {
        echo '<p>Määritä kuvien optimointiasetukset.</p>';
    }

    /**
     * Compression quality field callback.
     */
    public function compression_quality_callback() {
        $quality = get_option( 'tonys_theme_compression_quality', $this->compression_quality );
        echo '<input type="number" name="tonys_theme_compression_quality" value="' . esc_attr( $quality ) . '" min="0" max="100" step="1" />';
        echo '<p class="description">Pienempi arvo = pienempi tiedostokoko, huonompi laatu. Suositus: 80-85.</p>';
    }

    /**
     * Max dimensions field callback.
     */
    public function max_dimensions_callback() {
        $dimensions = get_option( 'tonys_theme_max_dimensions', $this->max_dimensions );
        echo 'Leveys: <input type="number" name="tonys_theme_max_dimensions[width]" value="' . esc_attr( $dimensions['width'] ) . '" min="0" step="1" /> ';
        echo 'Korkeus: <input type="number" name="tonys_theme_max_dimensions[height]" value="' . esc_attr( $dimensions['height'] ) . '" min="0" step="1" />';
        echo '<p class="description">Suuremmat kuvat skaalataan automaattisesti näihin mittoihin.</p>';
    }

    /**
     * Sanitize compression quality.
     *
     * @param mixed $input Input value.
     * @return int Sanitized value.
     */
    public function sanitize_compression_quality( $input ) {
        $input = absint( $input );
        return max( 0, min( 100, $input ) );
    }

    /**
     * Sanitize dimensions.
     *
     * @param mixed $input Input value.
     * @return array Sanitized value.
     */
    public function sanitize_dimensions( $input ) {
        if ( ! is_array( $input ) ) {
            return $this->max_dimensions;
        }

        return array(
            'width' => isset( $input['width'] ) ? absint( $input['width'] ) : $this->max_dimensions['width'],
            'height' => isset( $input['height'] ) ? absint( $input['height'] ) : $this->max_dimensions['height']
        );
    }

    /**
     * Settings page callback.
     */
    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'tonys_theme_image_options' );
                do_settings_sections( 'tonys_theme_image_options' );
                submit_button( 'Tallenna asetukset' );
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Generate alt text for images using content analysis.
     *
     * @param int $attachment_id Attachment ID.
     */
    public function generate_alt_text( $attachment_id ) {
        // Skip if not an image
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return;
        }

        // Skip if alt text already exists
        $current_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
        if ( ! empty( $current_alt ) ) {
            return;
        }

        $alt_text = '';
        
        // Get image filename without extension
        $filename = pathinfo( get_post_field( 'post_title', $attachment_id ), PATHINFO_FILENAME );
        
        // Clean up filename for use as alt text
        $alt_text = str_replace( array('-', '_'), ' ', $filename );
        $alt_text = ucfirst( $alt_text );

        // Get parent post if exists
        $parent_post = get_post_parent( $attachment_id );
        if ( $parent_post ) {
            // Add post title context
            $post_title = get_the_title( $parent_post );
            $alt_text = sprintf( '%s - %s', $alt_text, $post_title );
        }

        // Get image caption if exists
        $caption = wp_get_attachment_caption( $attachment_id );
        if ( ! empty( $caption ) ) {
            $alt_text = $caption;
        }

        // Limit length and sanitize
        $alt_text = wp_trim_words( $alt_text, 10 );
        $alt_text = sanitize_text_field( $alt_text );

        // Save alt text
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
    }

    /**
     * Ensure all images have alt text.
     *
     * @param array $attr Image attributes.
     * @param WP_Post $attachment Image attachment post.
     * @return array Modified attributes.
     */
    public function ensure_alt_text( $attr, $attachment ) {
        if ( empty( $attr['alt'] ) ) {
            // Generate alt text if missing
            $this->generate_alt_text( $attachment->ID );
            $attr['alt'] = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
            
            // Fallback to attachment title if still empty
            if ( empty( $attr['alt'] ) ) {
                $attr['alt'] = get_the_title( $attachment->ID );
            }
        }
        return $attr;
    }

    /**
     * Optimize image filename for SEO.
     *
     * @param string $filename Original filename.
     * @return string Optimized filename.
     */
    public function optimize_image_filename( $filename ) {
        // Convert to lowercase
        $filename = strtolower( $filename );
        
        // Replace spaces with hyphens
        $filename = str_replace( ' ', '-', $filename );
        
        // Remove multiple hyphens
        $filename = preg_replace( '/-+/', '-', $filename );
        
        // Remove special characters
        $filename = preg_replace( '/[^a-z0-9\-\.]/', '', $filename );
        
        // Remove common words
        $remove_words = array( 'image', 'img', 'picture', 'pic', 'photo', 'dsc', 'screenshot' );
        foreach ( $remove_words as $word ) {
            $filename = str_replace( $word . '-', '', $filename );
            $filename = str_replace( '-' . $word, '', $filename );
        }
        
        return $filename;
    }

    /**
     * Optimize image formats based on content and browser support.
     *
     * @param array $sizes Image sizes.
     * @return array Modified sizes.
     */
    public function optimize_image_formats( $sizes ) {
        // Get image format preferences
        $formats = array(
            'image/webp' => 1,    // Best compression, wide support
            'image/jpeg' => 2,    // Good compression, universal support
            'image/png' => 3,     // Lossless, good for graphics
            'image/gif' => 4      // Animation support
        );

        // Check if original is transparent
        $file = get_attached_file( get_the_ID() );
        $is_transparent = $this->is_transparent_image( $file );

        if ( $is_transparent ) {
            // Use PNG for transparent images
            $formats = array(
                'image/png' => 1,
                'image/webp' => 2
            );
        }

        // Add format conversion sizes
        foreach ( $formats as $mime_type => $priority ) {
            foreach ( $sizes as $name => $size ) {
                $sizes[$name . '_' . $mime_type] = $size;
            }
        }

        return $sizes;
    }

    /**
     * Check if image has transparency.
     *
     * @param string $file Image file path.
     * @return bool True if image has transparency.
     */
    private function is_transparent_image( $file ) {
        if ( ! file_exists( $file ) ) {
            return false;
        }

        $type = wp_check_filetype( $file );
        
        switch ( $type['type'] ) {
            case 'image/png':
                $image = @imagecreatefrompng( $file );
                if ( $image ) {
                    $has_alpha = imagecolortransparent( $image ) != -1;
                    imagedestroy( $image );
                    return $has_alpha;
                }
                break;
                
            case 'image/gif':
                $image = @imagecreatefromgif( $file );
                if ( $image ) {
                    $has_alpha = imagecolortransparent( $image ) != -1;
                    imagedestroy( $image );
                    return $has_alpha;
                }
                break;
        }

        return false;
    }

    /**
     * Optimize image metadata for SEO.
     *
     * @param array $metadata Attachment metadata.
     * @param int $attachment_id Attachment ID.
     * @return array Modified metadata.
     */
    public function optimize_image_metadata( $metadata, $attachment_id ) {
        if ( ! isset( $metadata['image_meta'] ) ) {
            $metadata['image_meta'] = array();
        }

        // Get attachment post
        $attachment = get_post( $attachment_id );
        if ( ! $attachment ) {
            return $metadata;
        }

        // Optimize title
        $title = get_the_title( $attachment_id );
        $title = $this->optimize_image_filename( $title );
        $title = str_replace( array('-', '_'), ' ', $title );
        $title = ucwords( $title );
        
        // Update post title
        wp_update_post( array(
            'ID' => $attachment_id,
            'post_title' => $title
        ) );

        // Add copyright info if missing
        if ( empty( $metadata['image_meta']['copyright'] ) ) {
            $metadata['image_meta']['copyright'] = get_bloginfo( 'name' ) . ' - ' . date( 'Y' );
        }

        // Add keywords based on title and caption
        $keywords = array();
        
        // Add words from title
        $title_words = explode( ' ', strtolower( $title ) );
        $keywords = array_merge( $keywords, $title_words );
        
        // Add words from caption
        $caption = wp_get_attachment_caption( $attachment_id );
        if ( ! empty( $caption ) ) {
            $caption_words = explode( ' ', strtolower( $caption ) );
            $keywords = array_merge( $keywords, $caption_words );
        }
        
        // Remove duplicates and common words
        $keywords = array_unique( $keywords );
        $stop_words = array( 'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by' );
        $keywords = array_diff( $keywords, $stop_words );
        
        // Add to metadata
        $metadata['image_meta']['keywords'] = implode( ', ', $keywords );

        return $metadata;
    }

    /**
     * Lisää responsiiviset attribuutit kuvaan
     *
     * @param array $attr Kuvan attribuutit
     * @param WP_Post $attachment Liitetiedoston post-objekti
     * @return array Muokatut attribuutit
     */
    public function add_responsive_attributes($attr, $attachment) {
        try {
            // Tarkista onko kuva
            if (!wp_attachment_is_image($attachment->ID)) {
                return $attr;
            }

            // Hae kuvan tiedot
            $metadata = wp_get_attachment_metadata($attachment->ID);
            if (!$metadata) {
                throw new Exception('Metadataa ei löydy');
            }

            // Lisää sizes-attribuutti
            $attr['sizes'] = $this->generate_sizes_attribute($metadata);

            // Lisää srcset
            $srcset = $this->generate_srcset($metadata, $attachment->ID);
            if ($srcset) {
                $attr['srcset'] = $srcset;
            }

            // Lisää loading="lazy" jos ei ole tärkeä kuva
            if (!$this->is_important_image($attachment->ID)) {
                $attr['loading'] = 'lazy';
            }

            // Lisää decoding="async" kaikille kuville
            $attr['decoding'] = 'async';

            // Lisää fetchpriority="high" tärkeille kuville
            if ($this->is_important_image($attachment->ID)) {
                $attr['fetchpriority'] = 'high';
            }

            return $attr;

        } catch (Exception $e) {
            error_log('TonysTheme Responsive Images: ' . $e->getMessage());
            return $attr;
        }
    }

    /**
     * Generoi sizes-attribuutti
     */
    private function generate_sizes_attribute($metadata) {
        $sizes = [];

        // Mobiili
        $sizes[] = '(max-width: 576px) 100vw';

        // Tabletti
        $sizes[] = '(max-width: 992px) 50vw';

        // Desktop
        if (isset($metadata['width']) && $metadata['width'] > 0) {
            $sizes[] = '(min-width: 993px) ' . $metadata['width'] . 'px';
        } else {
            $sizes[] = '(min-width: 993px) 33vw';
        }

        return implode(', ', $sizes);
    }

    /**
     * Generoi srcset
     */
    private function generate_srcset($metadata, $attachment_id) {
        $srcset = [];
        $sizes = [300, 600, 900, 1200, 1800];
        $image_url = wp_get_attachment_url($attachment_id);
        $image_path = get_attached_file($attachment_id);

        foreach ($sizes as $size) {
            // Ohita jos koko suurempi kuin alkuperäinen
            if ($size > $metadata['width']) {
                continue;
            }

            // Luo resoluutioversio
            $editor = wp_get_image_editor($image_path);
            if (is_wp_error($editor)) {
                continue;
            }

            $editor->resize($size, null, true);
            $resized = $editor->save();

            if (!is_wp_error($resized)) {
                $resized_url = str_replace(basename($image_url), basename($resized['path']), $image_url);
                $srcset[] = $resized_url . ' ' . $size . 'w';
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Optimoi srcset-lähteet
     */
    public function optimize_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        foreach ($sources as &$source) {
            // Lisää WebP-versio jos saatavilla
            $webp_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source['url']);
            $webp_path = str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $webp_url);
            
            if (file_exists($webp_path)) {
                $source['url'] = $webp_url;
            }

            // Optimoi laatu dynaamisesti leveyden mukaan
            if ($source['width'] <= 600) {
                $source['url'] = add_query_arg('q', '60', $source['url']);
            } elseif ($source['width'] <= 1200) {
                $source['url'] = add_query_arg('q', '75', $source['url']);
            }
        }

        return $sources;
    }

    /**
     * Preload critical images
     */
    public function preload_featured_images() {
        if (is_singular()) {
            $featured_image_id = get_post_thumbnail_id();
            if ($featured_image_id) {
                $image_src = wp_get_attachment_image_src($featured_image_id, 'full');
                if ($image_src) {
                    echo '<link rel="preload" as="image" href="' . esc_url($image_src[0]) . '">';
                }
            }
        }
    }

    /**
     * Add LQIP support
     *
     * @param string  $html       Image HTML
     * @param int     $id         Attachment ID
     * @param string  $size       Image size
     * @param bool    $icon       Whether it's an icon
     * @param array   $attr       Image attributes
     * @return string
     */
    public function add_lqip_support( $html, $id, $size, $icon, $attr ) {
        if ( ! $id || $icon ) {
            return $html;
        }

        // Generate tiny placeholder
        $placeholder = $this->generate_placeholder( $id );
        if ( ! $placeholder ) {
            return $html;
        }

        // Add blur-up effect styles
        $style = 'style="background-size: cover; background-image: url(' . esc_url( $placeholder ) . ');"';
        $html = preg_replace( '/<img/', '<img ' . $style, $html );

        return $html;
    }

    /**
     * Generate placeholder image
     *
     * @param int $attachment_id Attachment ID
     * @return string|false
     */
    private function generate_placeholder( $attachment_id ) {
        $image = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
        if ( ! $image ) {
            return false;
        }

        $editor = wp_get_image_editor( get_attached_file( $attachment_id ) );
        if ( is_wp_error( $editor ) ) {
            return false;
        }

        $editor->resize( 20, 20, true );
        $editor->set_quality( 50 );

        $upload_dir = wp_upload_dir();
        $placeholder_path = $upload_dir['path'] . '/placeholder-' . $attachment_id . '.jpg';
        $placeholder_url = $upload_dir['url'] . '/placeholder-' . $attachment_id . '.jpg';

        $editor->save( $placeholder_path, 'image/jpeg' );

        return $placeholder_url;
    }

    /**
     * Add image error handling
     *
     * @param string  $html       Image HTML
     * @param int     $id         Attachment ID
     * @param string  $size       Image size
     * @param bool    $icon       Whether it's an icon
     * @param array   $attr       Image attributes
     * @return string
     */
    public function add_image_error_handling( $html, $id, $size, $icon, $attr ) {
        if ( ! $id || $icon ) {
            return $html;
        }

        // Add onerror handler
        $fallback_image = get_theme_file_uri( 'assets/images/fallback.jpg' );
        $onerror = 'this.onerror=null;this.src=\'' . esc_url( $fallback_image ) . '\';';
        $html = preg_replace( '/<img/', '<img onerror="' . $onerror . '"', $html );

        return $html;
    }

    /**
     * Schedule automatic image optimization
     */
    public function schedule_image_optimization() {
        if ( ! wp_next_scheduled( 'tonystheme_optimize_images' ) ) {
            wp_schedule_event( time(), 'daily', 'tonystheme_optimize_images' );
        }

        add_action( 'tonystheme_optimize_images', array( $this, 'batch_optimize_images' ) );
    }

    /**
     * Batch optimize images
     */
    public function batch_optimize_images() {
        $images = get_posts( array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => 50,
            'meta_query' => array(
                array(
                    'key' => '_optimized',
                    'compare' => 'NOT EXISTS'
                )
            )
        ) );

        foreach ( $images as $image ) {
            $this->optimize_image( $image->ID );
            update_post_meta( $image->ID, '_optimized', true );
        }
    }

    /**
     * Optimize single image
     *
     * @param int $attachment_id Attachment ID
     */
    private function optimize_image( $attachment_id ) {
        $file = get_attached_file( $attachment_id );
        if ( ! $file ) {
            return;
        }

        $editor = wp_get_image_editor( $file );
        if ( is_wp_error( $editor ) ) {
            return;
        }

        // Optimoi laatu
        $editor->set_quality( $this->compression_quality );

        // Generate WebP version
        $this->generate_webp_version( $file );

        // Save optimized image
        $editor->save( $file );
    }

    /**
     * Generate WebP version of image
     *
     * @param string $file Image file path
     */
    private function generate_webp_version( $file ) {
        $editor = wp_get_image_editor( $file );
        if ( is_wp_error( $editor ) ) {
            return;
        }

        $webp_file = preg_replace( '/\.[^.]+$/', '.webp', $file );
        $editor->save( $webp_file, 'image/webp' );
    }

    /**
     * Optimize image srcset.
     *
     * @param array $sources Source images.
     * @param array $size_array Image size.
     * @param string $image_src Image URL.
     * @param array $image_meta Image metadata.
     * @param int $attachment_id Attachment ID.
     * @return array Modified sources.
     */
    public function optimize_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
        if ( empty( $sources ) ) {
            return $sources;
        }

        // Sort sources by size for better browser selection
        uasort( $sources, function( $a, $b ) {
            return $a['value'] - $b['value'];
        } );

        // Add WebP versions if available
        foreach ( $sources as $width => $source ) {
            $webp_url = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $source['url'] );
            $webp_file = str_replace( site_url(), ABSPATH, $webp_url );
            
            if ( file_exists( $webp_file ) ) {
                $source['webp'] = $webp_url;
            }
        }

        return $sources;
    }

    /**
     * Add WebP fallback support.
     *
     * @param string $html Image HTML.
     * @param int $attachment_id Attachment ID.
     * @param string $size Size name.
     * @param bool $icon Whether it's an icon.
     * @param array $attr Image attributes.
     * @return string Modified HTML.
     */
    public function add_webp_fallback( $html, $attachment_id, $size, $icon, $attr ) {
        if ( $icon || ! $attachment_id ) {
            return $html;
        }

        $file = get_attached_file( $attachment_id );
        $webp_file = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file );

        if ( file_exists( $webp_file ) ) {
            $webp_url = wp_get_attachment_url( $attachment_id );
            $webp_url = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $webp_url );

            $picture = '<picture>';
            $picture .= '<source srcset="' . esc_url( $webp_url ) . '" type="image/webp">';
            $picture .= $html;
            $picture .= '</picture>';

            return $picture;
        }

        return $html;
    }
}

// Initialize the image optimizer
TonysTheme_Image_Optimizer::get_instance();
