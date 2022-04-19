import React 		from 'react';
import ReactDOM 	from 'react-dom';
import App from "./App";


// Possible elements that we may find for shortcodes
const root = document.getElementById( 'cploc_root' );

if (root) {
//	let locations = root.getAttribute( 'data-locations' );
	ReactDOM.render(<App />, root );
}