import { useRef, createRef, useEffect, useState } from 'react';
import { MapContainer, Marker, Popup, TileLayer, useMap, latLngBounds, ZoomControl } from 'react-leaflet';
import Controllers_WP_REST_Request from './Controllers/WP_REST_Request';
import debounce from '@mui/utils/debounce';
import SearchInput from './Elements/SearchInput';
import CircularProgress from '@mui/material/CircularProgress';

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
	const mapRef = useRef();
	const searchCenter = [51.505, -0.09];
	const onClick = ( index ) => {
		markerRef.current[index].openPopup();
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
			
			location.distanceDesc = 1 > location.distance ? '< 1 mi' : location.distance.toFixed(1) + ' mi';
			
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
		<div>
			
			<div className="cploc-map">

				{loading && (
					<div className="cploc-map--loading">
						<CircularProgress/>
					</div>
				)}
				
				<div className="cploc-map--tabs">
					{userGeo && (
						<div className="cploc-map--tabs--search">
							{locations.length ? (<span>Showing results for</span>) : (<span>No results found for</span>)} '{userGeo.attr.postcode}'
						</div>
					)}

					{locations.map((location, index) => (
						<div className="cploc-map--tabs--tab cploc-map-tab" key={index} onClick={() => onClick(index)}>
							<div className="cploc-map-tab--thumb"><div style={{backgroundImage: 'url(' + location.thumb.thumb + ')'}} /></div>
							<div className="cploc-map-tab--content">
								<h3 className="cploc-map-tab--title">{location.title}</h3>
								<div className="cploc-map-tab--address">{location.geodata.attr.place}, {location.geodata.attr.region} {(userGeo && location.distanceDesc) && (<span className="cploc-map-tab--distance">({location.distanceDesc})</span>)}</div>

								<div className="cploc-map-tab--times"></div>
							</div>
						</div>
					))}
				</div>
				<div className="cploc-map--map">
	        <SearchInput onValueChange={handleSearchInputChange} className="cploc-map--search" />

					<MapContainer scrollWheelZoom={false} zoomControl={false}>
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
								<Popup><div dangerouslySetInnerHTML={{__html: location.templates.popup }} /></Popup>
							</Marker>	
						))}
						
						<ZoomControl position="bottomleft"  />
					</MapContainer>

				</div>
			</div>
		</div>
	);
};

export default App;
