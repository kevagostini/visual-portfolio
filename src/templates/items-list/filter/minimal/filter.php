<?php
/**
 * Minimal filter template.
 *
 * @var $args
 * @package @@plugin_name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<ul class="<?php echo esc_attr( $args['class'] ); ?> vp-filter__style-minimal">
    <?php
    // phpcs:ignore
    foreach ( $args['items'] as $item ) {
        ?>
        <li class="<?php echo esc_attr( $item['class'] ); ?>">
            <a href="<?php echo esc_url( $item['url'] ); ?>" data-vp-filter="<?php echo esc_attr( $item['filter'] ); ?>">
                <?php echo esc_html( $item['label'] ); ?>

                <?php
                if ( $args['show_count'] && $item['count'] ) {
                    ?>
                    <span class="vp-filter__item-count">
                        <?php echo esc_html( $item['count'] ); ?>
                    </span>
                    <?php
                }
                ?>
            </a>
        </li>
        <?php
    }
    ?>
</ul>
