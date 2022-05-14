import { useRef, createRef, useEffect, useState } from 'react';
import { MapContainer, Marker, Popup, TileLayer, Tooltip,useMap, latLngBounds, ZoomControl } from 'react-leaflet';
import Controllers_WP_REST_Request from './Controllers/WP_REST_Request';
import debounce from '@mui/utils/debounce';
import SearchInput from './Elements/SearchInput';
import CircularProgress from '@mui/material/CircularProgress';
import { CupertinoPane } from 'cupertino-pane';
import { MyLocation, LocationSearching, LocationDisabled } from '@mui/icons-material';
import Toast from '../../includes/ChurchPlugins/assets/js/toast';

import { distance, point } from "turf";
import L from "leaflet";
import markerIcon from '../../assets/images/marker-icon.png'; // "leaflet/dist/images/marker-icon.png";
import markerIconAlt from '../../assets/images/marker-icon-alt.png'; // "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
	iconUrl: markerIcon,
	iconSize: [26.5, 35],
	iconRetinaUrl: undefined,
	shadowUrl: undefined,
});

const pcIcon = L.icon({
	iconUrl: markerIconAlt,
	iconSize: [35, 35],
});

let fitBoundsTimeout;

function ChangeView ({locations, userGeo}) {
	
	if (typeof fitBoundsTimeout === 'number') {
		clearTimeout(fitBoundsTimeout);
	}
	
	if (!locations.length) {
		return null;
	}

	const map = useMap();
	const features = [...locations];
	
	if ( userGeo ) {
		features.push( { geodata : { center: userGeo.center } } );
	}
	
	fitBoundsTimeout = setTimeout( () => map.fitBounds(features.map((feature) => feature.geodata.center), {padding: [100, 100]} ), 100 );
	return null;
}

const App = () => {
	let markerRef = useRef([]);
	const [loading, setLoading] = useState(false);
	const [error, setError] = useState(false);
	const [locations, setLocations] = useState([]);
	const [initLocations, setInitLocations] = useState([]);
	const [userGeo, setUserGeo] = useState( false );
	const [mode, setMode] = useState( 'map' );
	const [listPane, setListPane] = useState({} );
	const mapRef = useRef();
	const searchCenter = [51.505, -0.09];
	
	const onClick = ( index ) => {
		markerRef.current[index].openPopup();
	}
	
	const disableScroll = () => {
		console.log('disable scroll');
		document.body.style.overflow = 'hidden';
	}
	
	const enableScroll = () => {
		console.log('enable scroll');
		document.body.style.overflow = 'scroll';
	}
	
	const closePopups = () => {
		locations.map((location, index) => ( markerRef.current[index].closePopup() ));
	}
	
	const getMyLocation = () => {
		navigator.geolocation.getCurrentPosition((position) => {
			setUserGeo( {
				attr : { postcode : 'current location' },
				center : [ position.coords.latitude, position.coords.longitude ],
			} );
			console.log('Latitude is :', position.coords.latitude);
			console.log('Longitude is :', position.coords.longitude);
		}, () => {
			Toast.error( 'Location sharing is disabled in your browser.' );
		} );
	}
	
	const handleSearchInputChange = debounce((value) => {
		
		if ( 5 !== value.length ) {
			setUserGeo( false );
			return;
		}

		(
			async () => {
				try {
					setLoading(true);
					const restRequest = new Controllers_WP_REST_Request();
					const data = await restRequest.get({endpoint: 'locations/postcode/' + value});
					setUserGeo( data );
				} catch (error) {
					setError(error);
				} finally {
					setLoading(false);
				}
			}
		)();
		
	}, 100);
	
	useEffect(() => {
		(
			async () => {
				try {
					setLoading(true);
					const restRequest = new Controllers_WP_REST_Request();
					const data = await restRequest.get({endpoint: 'locations'});
					setLocations(JSON.parse(JSON.stringify(data.locations)));
					setInitLocations( JSON.parse(JSON.stringify(data.locations)) );
				} catch (error) {
					setError(error);
				} finally {
					setLoading(false);
				}
			}
		)();
	}, [] );
	
	const switchPaneMode = () => {
		
	}
	
	useEffect( () => {
//		return;
		document.body.style.overflow = 'hidden';
		document.addEventListener('touchstart', (e, x) => {
			debugger;
			if ( document.getElementById('cploc-map-pane').contains(e.target) ) {
				return;
			}
			
			document.body.style.overflow = 'scroll';
		} );

		document.addEventListener('touchend', (e, x) => {
			setTimeout( () => document.body.style.overflow = 'hidden', 1000 );
		} );

		const locationPane = new CupertinoPane( '.cploc-map--locations-mobile', {
			parentElement: 'body',
			breaks: {
				bottom: { enabled: true, height: 80 }
			},
			initialBreak: 'bottom',
			touchMoveStopPropagation: true,
			buttonDestroy: false,
			fitScreenHeight: false,
//			onDragStart: () => document.body.style.overflow = 'hidden',
//			onDragEnd: () => document.body.style.overflow = 'scroll',
		} );
		
		locationPane.present({animate: true}).then();
		setListPane( locationPane );
	}, [] );
	
	useEffect( () => {
		let data = [];
		
		if ( ! userGeo ) {
			setLocations( JSON.parse(JSON.stringify(initLocations)) );
			return;
		}
		
		const userCenter = point([...userGeo.center].reverse());
		for (const location of locations) {
			location.distance = distance(
				userCenter,
				point( [...location.geodata.center].reverse() ),
				'miles'
			);
			
			location.distanceDesc = 1 > location.distance ? '< 1' : location.distance.toFixed(1);
			
			// don't show locations more than 100 miles away
			if ( location.distance < 100 ) {
				data.push(location);
			}
		}
		
		data.sort((a, b) => {
			if (a.distance > b.distance) {
				return 1;
			}
			
			if( a.distance < b.distance) {
				return -1;
			}
			
			return 0;
		})
		
		setLocations(data);
	}, [userGeo])
	
	return error ? (
			<pre>{JSON.stringify(error, null, 2)}</pre>
	) : ( 
		<div className="cploc-container cploc">
			
			{loading && (
				<div className="cploc-container--loading">
					<CircularProgress/>
				</div>
			)}
			
				<div className="cploc-map" style={mode === 'map' ? {} : { display: 'none' }}>
					
					<div className="cploc-map--locations">
						
						<div className="cploc-map--locations--mode">
							<span className="cploc--mode-switch" onClick={() => { closePopups(); setMode('list') }}>Hide map</span>
						</div>
						
						{userGeo && (
							<div className="cploc-map--locations--search">
								{locations.length ? (<span>Showing results for</span>) : (<span>No results found for</span>)} '{userGeo.attr.postcode}'
							</div>
						)}
	
						{locations.map((location, index) => (
							<div className="cploc-map--locations--location cploc-map-location" key={index} onClick={() => onClick(index)}>
								<div className="cploc-map-location--thumb"><div style={{backgroundImage: 'url(' + location.thumb.thumb + ')'}} /></div>
								<div className="cploc-map-location--content">
									<h3 className="cploc-map-location--title">{location.title}</h3>
									<div className="cploc-map-location--address">{location.geodata.attr.place}, {location.geodata.attr.region} {(userGeo && location.distanceDesc + 'mi') && (<span className="cploc-map-location--distance">({location.distanceDesc})</span>)}</div>
	
									<div className="cploc-map-location--times"></div>
								</div>
							</div>
						))}
					</div>
					<div className="cploc-map--map">
						<div className="cploc-map--controls">
							<SearchInput onValueChange={handleSearchInputChange} className="cploc-map--search" />
							<button className="cploc-map--my-location" onClick={getMyLocation}><MyLocation /></button>
						</div>
	
						<MapContainer scrollWheelZoom={false} zoomControl={false} dragging={!L.Browser.mobile}>
							<TileLayer
								attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
								url="https://api.mapbox.com/styles/v1/mapbox-map-design/ckshxkppe0gge18nz20i0nrwq/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoidGFubmVybW91c2hleSIsImEiOiJjbDFlaWEwZ2IwaHpjM2NsZjh4Z2s3MHk2In0.QGwQkxVGACSg4yQnFhmjuw"
							/>
							
							<ChangeView locations={locations} userGeo={userGeo} />
	
							{userGeo && (
								<Marker icon={pcIcon} position={userGeo.center} />
							)}
							
							{locations.map((location, index) => (
								<Marker ref={(el) => (markerRef.current[index] = el)} key={index} position={location.geodata.center}>
									{0 && (<Tooltip direction="center" permanent={true}>{location.title}</Tooltip>)}
									<Popup><div dangerouslySetInnerHTML={{__html: location.templates.popup }} /></Popup>
								</Marker>	
							))}
							
							<ZoomControl position="bottomleft"  />
						</MapContainer>
	
						<div id="cploc-map-pane" className="cploc-map--locations-mobile" onTouchMove={disableScroll} onTouchStart={disableScroll} onTouchEnd={enableScroll}>
							{locations.map((location, index) => (
								<div className="cploc-map--locations--location cploc-map-location" key={index} onClick={() => onClick(index)}>
									<div className="cploc-map-location--thumb"><div style={{backgroundImage: 'url(' + location.thumb.thumb + ')'}} /></div>
									<div className="cploc-map-location--content">
										<h3 className="cploc-map-location--title">{location.title}</h3>
										<div className="cploc-map-location--address">{location.geodata.attr.place}, {location.geodata.attr.region} {(userGeo && location.distanceDesc + 'mi') && (<span className="cploc-map-location--distance">({location.distanceDesc})</span>)}</div>
		
										<div className="cploc-map-location--times"></div>
									</div>
								</div>
							))}
							
							<div className="cploc-map--locations--mode" onClick={switchPaneMode}>Switch Mode</div>
						</div>
						
					</div>
				</div>
			
				<div className="cploc-list" style={mode === 'list' ? {} : { display: 'none' }}>
	        <div>
		        <SearchInput onValueChange={handleSearchInputChange} className="cploc-map--search" />
	        </div>

					<div className="cploc-list--meta">
						{userGeo && (
							<span className="cploc-list--search">
								{locations.length ? (<span>Showing results for</span>) : (<span>No results found for</span>)} '{userGeo.attr.postcode}'
							</span>
						)}

						<span className="cploc--mode-switch" onClick={() => setMode('map')}>View on Map</span>
					</div>

					
					<div className="cploc-list--items" >
						{locations.map((location, index) => (
							<div className="cploc-list--item" key={index}>
								<div dangerouslySetInnerHTML={{__html: location.templates.popup }} />
								{(userGeo && location.distanceDesc + 'mi') && (<div className="cploc-list-item--distance">{location.distanceDesc} miles away</div>)}
								<div className="cp-button" onClick={() => { setMode('map'); setTimeout(() => onClick(index), 250); }}>View on Map ></div>
							</div>
						))}
					</div>
					
				</div>
			
		</div>
	);
};

export default App;
