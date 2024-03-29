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
	<a class="cploc-map-popup--thumb" href="<?php echo get_the_permalink( $location->post->ID ); ?>" style="background-image: url('<?php echo get_the_post_thumbnail_url( $location->post->ID, 'large' ); ?>')"><?php echo get_the_post_thumbnail( $location->post->ID, 'large' ); ?></a>
	<h4 class="cploc-map-popup--title"><a href="<?php echo get_the_permalink( $location->post->ID ); ?>"><?php echo $data['title']; ?></a></h4>
	<div class="cploc-map-popup--info">
		<div class="cploc-map-popup--speaker"><?php echo Helpers::get_icon( 'speaker' ); ?> <?php echo $data['pastor']; ?></div>
		<div class="cploc-map-popup--address"><?php echo Helpers::get_icon( 'location' ); ?> <?php echo $data['address']; ?></div>
		<div class="cploc-map-popup--times"><?php echo Helpers::get_icon( 'date' ); ?> <?php echo $data['times']; ?></div>
	</div>
	<div class="cploc-map-popup--cta"><a href="<?php echo get_the_permalink( $location->post->ID ); ?>" class="cp-button is-transparent">Learn More</a></div>
</div>
