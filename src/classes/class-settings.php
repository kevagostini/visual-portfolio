<?php
/**
 * Plugin Settings
 *
 * @package @@plugin_name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once visual_portfolio()->plugin_path . 'vendors/class-settings-api.php';

/**
 * Visual Portfolio Settings Class
 */
class Visual_Portfolio_Settings {
    /**
     * Settings API instance
     *
     * @var object
     */
    public static $settings_api;

    /**
     * Visual_Portfolio_Settings constructor.
     */
    public function __construct() {
        self::init_actions();
    }

    /**
     * Get Option Value
     *
     * @param string $option - option name.
     * @param string $section - section name.
     * @param string $deprecated_default - default option value.
     *
     * @return bool|string
     */
    public static function get_option( $option, $section, $deprecated_default = '' ) {
        $options = get_option( $section );
        $result  = '';

        if ( isset( $options[ $option ] ) ) {
            $result = $options[ $option ];
        } else {
            // find default.
            $fields = self::get_settings_fields();

            if ( isset( $fields[ $section ] ) && is_array( $fields[ $section ] ) ) {
                foreach ( $fields[ $section ] as $field_data ) {
                    if ( $option === $field_data['name'] && isset( $field_data['default'] ) ) {
                        $result = $field_data['default'];
                    }
                }
            }
        }

        return 'off' === $result ? false : ( 'on' === $result ? true : $result );
    }

    /**
     * Update Option Value
     *
     * @param string $option - option name.
     * @param string $section - section name.
     * @param string $value - new option value.
     */
    public static function update_option( $option, $section, $value ) {
        $options = get_option( $section );

        if ( ! is_array( $options ) ) {
            $options = array();
        }

        $options[ $option ] = $value;

        update_option( $section, $options );
    }

    /**
     * Init actions
     */
    public static function init_actions() {
        self::$settings_api = new Visual_Portfolio_Settings_API();

        add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 11 );
    }

    /**
     * Initialize the settings
     *
     * @return void
     */
    public static function admin_init() {
        // set the settings.
        self::$settings_api->set_sections( self::get_settings_sections() );
        self::$settings_api->set_fields( self::get_settings_fields() );

        // initialize settings.
        self::$settings_api->admin_init();
    }

    /**
     * Register the admin settings menu
     *
     * @return void
     */
    public static function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=portfolio',
            esc_html__( 'Settings', '@@text_domain' ),
            esc_html__( 'Settings', '@@text_domain' ),
            'manage_options',
            'visual-portfolio-settings',
            array( __CLASS__, 'print_settings_page' )
        );
    }

    /**
     * Plugin settings sections
     *
     * @return array
     */
    public static function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'vp_general',
                'title' => esc_html__( 'General', '@@text_domain' ),
            ),
            array(
                'id'    => 'vp_images',
                'title' => esc_html__( 'Images', '@@text_domain' ),
            ),
            array(
                'id'    => 'vp_popup_gallery',
                'title' => esc_html__( 'Popup Gallery', '@@text_domain' ),
            ),
            array(
                'id'    => 'vp_watermarks',
                'title' => esc_html__( 'Watermarks', '@@text_domain' ),
            ),
            array(
                'id'    => 'vp_social_integrations',
                'title' => esc_html__( 'Social Integrations', '@@text_domain' ),
            ),
        );

        return apply_filters( 'vpf_settings_sections', $sections );
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public static function get_settings_fields() {
        $default_breakpoints = Visual_Portfolio_Breakpoints::get_default_breakpoints();

        $settings_fields = array(
            'vp_general' => array(
                array(
                    'name'    => 'portfolio_slug',
                    'label'   => esc_html__( 'Portfolio Slug', '@@text_domain' ),
                    'type'    => 'text',
                    'default' => 'portfolio',
                ),
                array(
                    'name'    => 'filter_taxonomies',
                    'label'   => esc_html__( 'Filter Taxonomies', '@@text_domain' ),
                    'desc'    => esc_html__( 'You can show custom taxonomies in the portfolio Filter. Enter some taxonomies by "," separating values. Example: "product_cat,product_tag"', '@@text_domain' ),
                    'type'    => 'text',
                    'default' => '',
                ),
                array(
                    'name'    => 'no_image',
                    'label'   => esc_html__( 'No Image', '@@text_domain' ),
                    'desc'    => esc_html__( 'This image used if the featured image of a post is not specified.', '@@text_domain' ),
                    'type'    => 'image',
                    'default' => '',
                    'options' => array(
                        'button_label' => esc_html__( 'Choose image', '@@text_domain' ),
                    ),
                ),

                // AJAX Caching and Preloading.
                array(
                    'name'    => 'ajax_caching',
                    'label'   => esc_html__( 'AJAX Cache and Preload', '@@text_domain' ),
                    'desc'    => esc_html__( 'Reduce AJAX calls request time.', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => ! class_exists( 'Visual_Portfolio_Pro' ) ? 'off' : 'on',
                    'is_pro'  => true,
                ),

                // Breakpoints.
                array(
                    'name'   => 'breakpoints_title',
                    'label'  => esc_html__( 'Responsive Breakpoints', '@@text_domain' ),
                    'type'   => 'html',
                    'is_pro' => true,
                ),
                array(
                    'name'        => 'breakpoint_xl',
                    'label'       => esc_html__( 'Extra Large', '@@text_domain' ),
                    'type'        => 'number',
                    'min'         => (int) $default_breakpoints['lg'] + 1,
                    'max'         => 3840,
                    'placeholder' => (string) $default_breakpoints['xl'],
                    'default'     => (int) $default_breakpoints['xl'],
                    // translators: %1$s - default breakpoint.
                    'desc'        => sprintf( wp_kses_post( __( 'Sets the breakpoint on extra large screen sizes (Default: %1$spx).', '@@text_domain' ) ), $default_breakpoints['xl'] ),
                    'is_pro'      => true,
                ),
                array(
                    'name'        => 'breakpoint_lg',
                    'label'       => esc_html__( 'Large', '@@text_domain' ),
                    'type'        => 'number',
                    'min'         => (int) $default_breakpoints['md'] + 1,
                    'max'         => (int) $default_breakpoints['xl'] - 1,
                    'placeholder' => (string) $default_breakpoints['lg'],
                    'default'     => (int) $default_breakpoints['lg'],
                    // translators: %1$s - default breakpoint.
                    'desc'        => sprintf( wp_kses_post( __( 'Sets the breakpoint on large screen sizes (Default: %1$spx).', '@@text_domain' ) ), $default_breakpoints['lg'] ),
                    'is_pro'      => true,
                ),
                array(
                    'name'        => 'breakpoint_md',
                    'label'       => esc_html__( 'Medium', '@@text_domain' ),
                    'type'        => 'number',
                    'min'         => (int) $default_breakpoints['sm'] + 1,
                    'max'         => (int) $default_breakpoints['lg'] - 1,
                    'placeholder' => (string) $default_breakpoints['md'],
                    'default'     => (int) $default_breakpoints['md'],
                    // translators: %1$s - default breakpoint.
                    'desc'        => sprintf( wp_kses_post( __( 'Sets the breakpoint on medium screen sizes (Default: %1$spx).', '@@text_domain' ) ), $default_breakpoints['md'] ),
                    'is_pro'      => true,
                ),
                array(
                    'name'        => 'breakpoint_sm',
                    'label'       => esc_html__( 'Small', '@@text_domain' ),
                    'type'        => 'number',
                    'min'         => (int) $default_breakpoints['xs'] + 1,
                    'max'         => (int) $default_breakpoints['md'] - 1,
                    'placeholder' => (string) $default_breakpoints['sm'],
                    'default'     => (int) $default_breakpoints['sm'],
                    // translators: %1$s - default breakpoint.
                    'desc'        => sprintf( wp_kses_post( __( 'Sets the breakpoint on small screen sizes (Default: %1$spx).', '@@text_domain' ) ), $default_breakpoints['sm'] ),
                    'is_pro'      => true,
                ),
                array(
                    'name'        => 'breakpoint_xs',
                    'label'       => esc_html__( 'Extra Small', '@@text_domain' ),
                    'type'        => 'number',
                    'min'         => 1,
                    'max'         => (int) $default_breakpoints['sm'] - 1,
                    'placeholder' => (string) $default_breakpoints['xs'],
                    'default'     => (int) $default_breakpoints['xs'],
                    // translators: %1$s - default breakpoint.
                    'desc'        => sprintf( wp_kses_post( __( 'Sets the breakpoint on extra small screen sizes (Default: %1$spx).', '@@text_domain' ) ), $default_breakpoints['xs'] ),
                    'is_pro'      => true,
                ),
            ),
            'vp_images' => array(
                array(
                    'name'    => 'lazy_loading',
                    'label'   => esc_html__( 'Lazy Loading', '@@text_domain' ),
                    'desc'    => esc_html__( 'Enable lazy loading for Visual Portfolio layouts only or for the whole website.', '@@text_domain' ),
                    'type'    => 'radio',
                    'default' => 'vp',
                    'options' => array(
                        ''     => esc_html__( 'Disabled', '@@text_domain' ),
                        'vp'   => esc_html__( 'Visual Portfolio only', '@@text_domain' ),
                        'full' => esc_html__( 'All images', '@@text_domain' ),
                    ),
                ),

                array(
                    'name'  => 'images_sizes_title',
                    'label' => esc_html__( 'Image Sizes', '@@text_domain' ),
                    'type'  => 'html',
                ),
                array(
                    'name'    => 'images_layouts_title',
                    'label'   => esc_html__( 'Layouts', '@@text_domain' ),
                    'desc'    => __( 'Image sizes used in portfolio layouts.', '@@text_domain' ),
                    'type'    => 'html',
                ),
                array(
                    'name'        => 'sm',
                    'label'       => esc_html__( 'Small', '@@text_domain' ),
                    'type'        => 'number',
                    'placeholder' => '500',
                    'default'     => 500,
                ),
                array(
                    'name'        => 'md',
                    'label'       => esc_html__( 'Medium', '@@text_domain' ),
                    'type'        => 'number',
                    'placeholder' => '800',
                    'default'     => 800,
                ),
                array(
                    'name'        => 'lg',
                    'label'       => esc_html__( 'Large', '@@text_domain' ),
                    'type'        => 'number',
                    'placeholder' => '1280',
                    'default'     => 1280,
                ),
                array(
                    'name'        => 'xl',
                    'label'       => esc_html__( 'Extra Large', '@@text_domain' ),
                    'type'        => 'number',
                    'placeholder' => '1920',
                    'default'     => 1920,
                ),
                array(
                    'name'    => 'images_popup_title',
                    'label'   => esc_html__( 'Popup Gallery', '@@text_domain' ),
                    'desc'    => __( 'Image sizes used in popup gallery images.', '@@text_domain' ),
                    'type'    => 'html',
                ),
                array(
                    'name'        => 'sm_popup',
                    'label'       => esc_html__( 'Small', '@@text_domain' ),
                    'type'        => 'number',
                    'placeholder' => '500',
                    'default'     => 500,
                ),
                array(
                    'name'        => 'md_popup',
                    'label'       => esc_html__( 'Medium', '@@text_domain' ),
                    'type'        => 'number',
                    'placeholder' => '800',
                    'default'     => 800,
                ),
                array(
                    'name'        => 'xl_popup',
                    'label'       => esc_html__( 'Large', '@@text_domain' ),
                    'type'        => 'number',
                    'placeholder' => '1920',
                    'default'     => 1920,
                ),
                array(
                    'name'    => 'images_sizes_note',
                    // translators: %s: regenerate thumbnails url.
                    'desc'    => sprintf( __( 'After publishing your changes, new image sizes may not be shown until you <a href="%s" target="_blank">Regenerate Thumbnails</a>.', '@@text_domain' ), 'https://wordpress.org/plugins/regenerate-thumbnails/' ),
                    'type'    => 'html',
                ),
            ),
            'vp_popup_gallery' => array(
                // Vendor.
                array(
                    'name'    => 'vendor',
                    'label'   => esc_html__( 'Vendor Script', '@@text_domain' ),
                    'type'    => 'select',
                    'options' => array(
                        'fancybox'   => esc_html__( 'Fancybox', '@@text_domain' ),
                        'photoswipe' => esc_html__( 'PhotoSwipe', '@@text_domain' ),
                    ),
                    'default' => 'fancybox',
                ),

                // Default WordPress Images.
                array(
                    'name'    => 'enable_on_wordpress_images',
                    'label'   => esc_html__( 'WordPress Images', '@@text_domain' ),
                    'desc'    => esc_html__( 'Enable popup for WordPress images and galleries.', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'off',
                ),

                // Deeplinking.
                array(
                    'name'    => 'deep_linking',
                    'label'   => esc_html__( 'Deep Linking', '@@text_domain' ),
                    'desc'    => esc_html__( 'Makes URL automatically change to reflect the current opened popup, and you can easily link directly to that image or video.', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => ! class_exists( 'Visual_Portfolio_Pro' ) ? 'off' : 'on',
                    'is_pro'  => true,
                ),
                array(
                    'name'    => 'deep_linking_url_to_share_images',
                    'label'   => esc_html__( 'Use Deep Linking URL to Share Images', '@@text_domain' ),
                    'desc'    => esc_html__( 'Check to share Deep Linking URLs when sharing images. When disabled, all galleries will share direct links to image files.', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'off',
                    'is_pro'  => true,
                ),

                // General Popup Settings.
                array(
                    'name'    => 'show_arrows',
                    'label'   => esc_html__( 'Display Arrows', '@@text_domain' ),
                    'desc'    => esc_html__( 'Arrows to navigate between images.', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),
                array(
                    'name'    => 'show_counter',
                    'label'   => esc_html__( 'Display Images Counter', '@@text_domain' ),
                    'desc'    => esc_html__( 'On the top left corner will be showed images counter.', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),
                array(
                    'name'    => 'show_zoom_button',
                    'label'   => esc_html__( 'Display Zoom Button', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),
                array(
                    'name'    => 'show_fullscreen_button',
                    'label'   => esc_html__( 'Display Fullscreen Button', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),
                array(
                    'name'    => 'show_share_button',
                    'label'   => esc_html__( 'Display Share Button', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),
                array(
                    'name'    => 'show_close_button',
                    'label'   => esc_html__( 'Display Close Button', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),

                // Fancybox Popup Settings.
                array(
                    'name'    => 'show_thumbs',
                    'label'   => esc_html__( 'Display Thumbnails', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),
                array(
                    'name'    => 'show_download_button',
                    'label'   => esc_html__( 'Display Download Button', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'off',
                ),
                array(
                    'name'    => 'show_slideshow',
                    'label'   => esc_html__( 'Display Slideshow', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'off',
                ),

                // Click to Zoom.
                array(
                    'name'    => 'click_to_zoom',
                    'label'   => esc_html__( 'Click to Zoom', '@@text_domain' ),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ),

                // Colors.
                array(
                    'name'    => 'background_color',
                    'label'   => esc_html__( 'Background Color', '@@text_domain' ),
                    'type'    => 'color',
                    'default' => '#1e1e1e',
                ),

                // Page iframe popup.
                array(
                    'name'    => 'pages_iframe_custom_css',
                    'label'   => esc_html__( 'Pages iFrame Custom CSS', '@@text_domain' ),
                    'desc'    => esc_html__( 'When you display pages in popup iframe, you may not need some page elements like header and footer. Hide it using custom CSS with classname `.vp-popup-iframe`.', '@@text_domain' ),
                    'type'    => 'textarea',
                    'default' => ! class_exists( 'Visual_Portfolio_Pro' ) ? '' : '
.vp-popup-iframe #site-header,
.vp-popup-iframe #masthead,
.vp-popup-iframe #site-footer,
.vp-popup-iframe #colophon {
    display: none;
}',
                    'is_pro'  => true,
                ),
            ),
            'vp_watermarks' => array(
                array(
                    'name'    => 'social_pro_info',
                    'desc'    => '
                        <div class="vpf-settings-info-pro">
                            <h3>' . esc_html__( 'PRO Feature', '@@text_domain' ) . '</h3>
                            <div>
                                <p>' . esc_html__( 'Data protection such as watermarks.', '@@text_domain' ) . '</p>
                                <a class="vpf-settings-info-pro-button" target="_blank" rel="noopener noreferrer" href="https://visualportfolio.co/pro/?utm_source=freeplugin&utm_medium=link&utm_campaign=plugin_settings&utm_content=@@plugin_version">' . esc_html__( 'Read More', '@@text_domain' ) . '</a>
                            </div>
                        </div>
                    ',
                    'type'    => 'html',
                ),
            ),
            'vp_social_integrations' => array(
                array(
                    'name'    => 'social_pro_info',
                    'desc'    => '
                        <div class="vpf-settings-info-pro">
                            <h3>' . esc_html__( 'PRO Feature', '@@text_domain' ) . '</h3>
                            <div>
                                <p>' . esc_html__( 'Social feeds such as Instagram, Youtube, Flickr, Twitter, etc...', '@@text_domain' ) . '</p>
                                <a class="vpf-settings-info-pro-button" target="_blank" rel="noopener noreferrer" href="https://visualportfolio.co/pro/?utm_source=freeplugin&utm_medium=link&utm_campaign=plugin_settings&utm_content=@@plugin_version">' . esc_html__( 'Read More', '@@text_domain' ) . '</a>
                            </div>
                        </div>
                    ',
                    'type'    => 'html',
                ),
            ),
        );

        return apply_filters( 'vpf_settings_fields', $settings_fields );
    }

    /**
     * The plugin page handler
     *
     * @return void
     */
    public static function print_settings_page() {
        self::$settings_api->admin_enqueue_scripts();

        echo '<div class="wrap">';
        echo '<h2>' . esc_html__( 'Visual Portfolio Settings', '@@text_domain' ) . '</h2>';

        self::$settings_api->show_navigation();
        self::$settings_api->show_forms();

        echo '</div>';

        ?>
        <script>
            (function( $ ) {
                // update controls.
                function updateControls() {
                    // popup gallery settings.
                    var $popupGalleryContainer = $('#vp_popup_gallery');
                    var vendor = $popupGalleryContainer.find('tr.vendor select').val();

                    $popupGalleryContainer.find('tr.show_download_button, tr.show_slideshow, tr.show_thumbs')[ 'fancybox' === vendor ? 'show' : 'hide' ]();
                }

                updateControls();
                $('form').on('change', updateControls);

                // Don't allow adding input number values that > then max attribute and < min attribute.
                $('form').on('input', '[type="number"]', function(e) {
                    var current = parseFloat( this.value );
                    var min = parseFloat(this.min);
                    var max = parseFloat(this.max);

                    if ('' !== this.value) {
                        if (!Number.isNaN(min) && current < min) {
                            this.value = min;
                        }
                        if (!Number.isNaN(max) && current > max) {
                            this.value = max;
                        }
                    }
                });

                <?php if ( ! class_exists( 'Visual_Portfolio_Pro' ) ) : ?>
                    // disable pro inputs.
                    $('.vpf-settings-control-pro').find('input, textarea').attr('disabled', 'disabled');
                <?php endif; ?>
            })(jQuery);
        </script>
        <?php
    }
}

new Visual_Portfolio_Settings();
