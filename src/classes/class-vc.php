<?php
/**
 * Shortcode for Visual Composer
 *
 * @package visual-portfolio/vc
 */
class Visual_Portfolio_VC {
    /**
     * Visual_Portfolio_VC constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Hooks.
     */
    public function init_hooks() {
        add_action( 'init', array( $this, 'add_shortcode' ) );
    }

    /**
     * Add shortcode to the visual composer
     */
    public function add_shortcode() {
        if ( function_exists( 'vc_map' ) ) {
            // get all visual-portfolio post types.
            // Don't use WP_Query on the admin side https://core.trac.wordpress.org/ticket/18408 .
            $vp_query = get_posts(array(
                'post_type'       => 'vp_lists',
                'posts_per_page'  => -1,
                'showposts'       => -1,
                'paged'           => -1,
            ));
            $data_vc = array();
            foreach ( $vp_query as $post ) {
                $data_vc[] = array( $post->ID, $post->post_title );
            }

            vc_map( array(
                'name' => esc_html__( 'Visual Portfolio', NK_VP_DOMAIN ),
                'base' => 'visual_portfolio',
                'controls' => 'full',
                'icon'     => 'icon-visual-portfolio',
                'params' => array(
                    array(
                        'type'        => 'dropdown',
                        'heading'     => esc_html__( 'Select visual portfolio', NK_VP_DOMAIN ),
                        'param_name'  => 'id',
                        'value'       => $data_vc,
                        'description' => '',
                        'admin_label' => true,
                    ),
                    array(
                        'type'        => 'textfield',
                        'heading'     => esc_html__( 'Custom Classes', NK_VP_DOMAIN ),
                        'param_name'  => 'class',
                        'value'       => '',
                        'description' => '',
                    ),
                    array(
                        'type'       => 'css_editor',
                        'heading'    => esc_html__( 'CSS', NK_VP_DOMAIN ),
                        'param_name' => 'vc_css',
                        'group'      => esc_html__( 'Design Options', NK_VP_DOMAIN ),
                    ),
                ),
            ) );
        }
    }
}