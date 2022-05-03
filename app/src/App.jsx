import { hot } from 'react-hot-loader';
import { useRef, createRef, useEffect, useState } from 'react';
import ReactDOM from 'react-dom';
import { MapContainer, Marker, Popup, TileLayer, useMap, latLngBounds } from 'react-leaflet';
import Controllers_WP_REST_Request from './Controllers/WP_REST_Request';
import debounce from '@mui/utils/debounce';
import SearchInput from './Elements/SearchInput';
import { PersonPinCircle } from '@mui/icons-material';

import { distance, point } from "turf";
import L from "leaflet";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
	iconUrl: markerIcon,
	iconRetinaUrl: markerIcon2x,
	shadowUrl: markerShadow,
});

const pcIcon = L.icon({
	iconUrl: 'https://docs.mapbox.com/help/demos/geocode-and-sort-stores/marker.png',
	iconSize: [56, 56],
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
	
	fitBoundsTimeout = setTimeout( () => map.fitBounds(features.map((feature) => feature.geodata.center) ), 100 );
//		map.zoomOut();
	return null;
}

const App = () => {
	let markerRef = useRef([]);
	const [loading, setLoading] = useState(false);
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
//					setError(error);
				} finally {
//					setLoading(false);
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
//					setError(error);
				} finally {
//					setLoading(false);
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
			
			
			location.distanceDesc = 1 > location.distance ? '< 1 mile' : location.distance.toFixed(1) + ' miles';
			
			// don't show locations more than 50 miles away
			if ( location.distance < 5 ) {
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
	
	return (
		<div>
			
			<div className="cploc-map">
				<div className="cploc-map--tabs">
	        <SearchInput onValueChange={handleSearchInputChange} />

					{locations.map((location, index) => (
						<div className="cploc-map--tabs--tab cploc-map-tab" key={index} onClick={() => onClick(index)}>
							<div className="cploc-map-tab--thumb"><div style={{backgroundImage: 'url(' + location.thumb.thumb + ')'}} /></div>
							<div className="cploc-map-tab--content">
								<h3 className="cploc-map-tab--title">{location.title}</h3>
								<div className="cploc-map-tab--address">{location.geodata.attr.place}, {location.geodata.attr.region}</div>
								
								{(userGeo && location.distanceDesc) && (
									<div className="cploc-map-tab--distance">{location.distanceDesc}</div>
								)}

								<div className="cploc-map-tab--times"></div>
							</div>
						</div>
					))}
				</div>
				<div className="cploc-map--map">
					<MapContainer>
						<TileLayer
							attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
							url="https://api.mapbox.com/styles/v1/mapbox/streets-v11/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoidGFubmVybW91c2hleSIsImEiOiJjbDFlaWEwZ2IwaHpjM2NsZjh4Z2s3MHk2In0.QGwQkxVGACSg4yQnFhmjuw"
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
					</MapContainer>

				</div>
			</div>
		</div>
	);
};

export default App;
