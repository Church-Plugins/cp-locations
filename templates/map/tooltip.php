<?php
/**
 * Template for the location tooltip content.
 *
 * Override this template in your theme by creating a file at [your-theme]/cp-locations/map/tooltip.php
 * 
 * @package CP_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$location_data = $args['location_data'];

$templates = \CP_Locations\Admin\Settings::get( 'custom_templates', [], 'cploc_templates' );

$fields = isset( $templates['tooltip'] ) ? $templates['tooltip'] : [
	'title',
	'address',
	'phone',
];

?>

<div class="cploc-map-tooltip">
	<?php foreach( $fields as $field ): ?>
		<?php if ( empty( $location_data[ $field ] ) ) continue; ?>

		<?php $data = $location_data[ $field ]; ?>
		<div class="cploc-map-tooltip-field cploc-map-tooltip-field--<?php echo esc_attr( $field ); ?>">
			<?php if ( 'title' === $field ) : ?>
				<h3><?php echo esc_html( $data ); ?></h3>
			<?php elseif ( 'thumb' === $field ) : ?>
				<img src="<?php echo esc_url( $location_data[ $field ]['thumb'] ); ?>" alt="<?php echo esc_attr( $location_data['title'] ); ?>">
			<?php elseif ( 'permalink' === $field ) : ?>
				<a href="<?php echo esc_url( $location_data['permalink'] ); ?>"><?php esc_html_e( 'Learn More', 'cp-locations' ); ?></a>
			<?php else : ?>
				<p><?php echo wp_kses_post( $location_data[ $field ] ); ?></p>
			<?php endif; ?>
	<?php endforeach; ?>
</div>