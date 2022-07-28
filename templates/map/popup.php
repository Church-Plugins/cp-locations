<?php
use ChurchPlugins\Helpers;
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
	<div class="cploc-map-popup--thumb" style="background-image: url('<?php echo get_the_post_thumbnail_url( $location->post->ID, 'large' ); ?>')"><?php echo get_the_post_thumbnail( $location->post->ID, 'large' ); ?></div>
	<h4 class="cploc-map-popup--title"><?php echo $data['title']; ?></h4>
	<div class="cploc-map-popup--info">
		<div class="cploc-map-popup--address"><?php echo Helpers::get_icon( 'location' ); ?> <?php echo $data['address']; ?></div>
		<div class="cploc-map-popup--times"><?php echo Helpers::get_icon( 'date' ); ?> <?php echo $data['times']; ?></div>
	</div>
	<div class="cploc-map-popup--cta"><a href="<?php echo get_the_permalink( $location->post->ID ); ?>">Learn More</a></div>
</div>
