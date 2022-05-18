<?php

use \CP_Locations\Controllers\Location;

global $post;
$args = empty( $args ) ? [] : $args;

if ( isset( $args[ 'location' ] ) ) {
	$location = $args[ 'location' ];
} else {
	$location = new Location( $post->id );
} 

$data = $location->get_api_data( false );
?>

<div class="cploc-map-popup">
	<div class="cploc-map-popup--thumb"><?php echo get_the_post_thumbnail( $location->post->ID, 'large' ); ?></div>
	<h4 class="cploc-map-popup--title"><?php echo $data['title']; ?></h4>
	<div class="cploc-map-popup--address"><?php echo wpautop( $data['address'] ); ?></div>
	<div class="cploc-map-popup--times"></div>
	<div class="cploc-map-popup--cta"><a href="<?php echo get_the_permalink( $location->post->ID ); ?>" class="cp-button">Learn More</a></div>
</div>
