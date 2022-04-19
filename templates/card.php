<?php

use \CP_Locations\Controllers\Location;

global $post;
$args = empty( $args ) ? [] : $args;

if ( isset( $args[ 'location' ] ) ) {
	$location = $args[ 'location' ];
} else {
	$location = new Location( $post->id );
} ?>

<div class="cploc-card">
	
	<div class="cploc-card--thumb"></div>
	<div class="cploc-card--title"></div>
	<div class="cploc-card--address"></div>
	<div class="cploc-card--distance"></div>
	<div class="cploc-card--distance"></div>
</div>
