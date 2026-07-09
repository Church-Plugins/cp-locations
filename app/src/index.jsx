import React from 'react';
import {createRoot} from 'react-dom/client';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import L from 'leaflet';
import 'leaflet.markercluster';
import App from "./App";


// Possible elements that we may find for shortcodes
const rootNode = document.getElementById( 'cploc_root' );

if (rootNode) {
//	let locations = root.getAttribute( 'data-locations' );
	const root = createRoot( rootNode );
	root.render( <App /> );
}