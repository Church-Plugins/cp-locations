export function cplocVar( key, index ) {
	if ( ! window.hasOwnProperty( 'cplocVars' ) ) {
		return '';
	}

	if ( ! window.cplocVars.hasOwnProperty( index ) ) {
		return '';
	}

	if ( ! window.cplocVars[ index ].hasOwnProperty( key ) ) {
		return '';
	}

	return window.cplocVars[ index ][ key ];
}
