import { useEffect, useState } from 'react';
import { useMap } from 'react-leaflet';
import Controllers_WP_REST_Request from './Controllers/WP_REST_Request';
import debounce from '@mui/utils/debounce';
import CircularProgress from '@mui/material/CircularProgress';
import Toast from '../../includes/ChurchPlugins/assets/js/toast';
import useMediaQuery from '@mui/material/useMediaQuery';
import DesktopFinder from './Components/DesktopFinder';
import MobileFinder from './Components/MobileFinder';
import { GestureHandling } from 'leaflet-gesture-handling';
import { distance, point } from "turf";
import L from "leaflet";
import markerIcon from '../../assets/images/marker-icon.png'; // "leaflet/dist/images/marker-icon.png";
import markerIconAlt from '../../assets/images/marker-icon-alt.png'; // "leaflet/dist/images/marker-icon.png";
import { cplocVar } from './utils/helpers';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
	iconUrl: markerIcon,
	iconSize: [26.5, 35],
	iconRetinaUrl: undefined,
	shadowUrl: undefined,
});

L.Map.addInitHook("addHandler", "gestureHandling", GestureHandling);

const userPinColor = cplocVar('userPinColor', 'settings')
const pcIcon = L.divIcon({
	className : 'custom-div-icon',
	html      : `<div style='color: ${userPinColor}' class='marker-pin marker-pin--person'><i class='material-icons'>account_circle</i></div>`,
	iconSize  : [24, 24],
	iconAnchor: [12, 24]
});

let fitBoundsTimeout;

function ChangeView (locations, userGeo) {
	const isDesktop = useMediaQuery('(min-width:1025px)');
	
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
	
	const paddingTopLeft = [50,100];
	const paddingBottomRight = isDesktop ? [50,100] : [100, 150];
	
	fitBoundsTimeout = setTimeout( () => map.fitBounds(features.map((feature) => feature.geodata.center), {paddingTopLeft, paddingBottomRight} ), 100 );
	return null;
}

const App = () => {
	const [loading, setLoading] = useState(false);
	const [error, setError] = useState(false);
	const [locations, setLocations] = useState([]);
	const [initLocations, setInitLocations] = useState([]);
	const [userGeo, setUserGeo] = useState( false );
	const isDesktop = useMediaQuery('(min-width:1025px)');

	const urlParams = new URLSearchParams(window.location.search);
	const initialSearchValue = urlParams.get('zipcode') || urlParams.get('s') || '';

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

	const loadUserGeo = async (value) => {
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

	useEffect(() => {
		if (initialSearchValue) {
			loadUserGeo(initialSearchValue);
		}
	}, [])
	
	const handleSearchInputChange = debounce((value) => {
		
		if ( 5 !== value.length ) {
			setUserGeo( false );
			return;
		}

		loadUserGeo(value);
		
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
	
	// TODO: memoize output, this function gets called on every render for each location
	const getIconLocation = (color = null) => {
		const style = color ? `style="color:${color}" ` : '';
		return L.divIcon({
			className : 'custom-div-icon',
			html      : `<div class='marker-pin marker-pin--location'><i ${style}class='material-icons'>location_on</i></div>`,
			iconSize  : [32, 32],
			iconAnchor: [16, 32]
		});
	}

	const getIconLocationCurrent = (color = null) => {
		const style = color ? `style="color:${color}" ` : '';
		return L.divIcon({
			className : 'custom-div-icon',
			html      : `<div class='marker-pin marker-pin--current'><i ${style}class='material-icons'>location_on</i></div>`,
			iconSize  : [32, 32],
			iconAnchor: [16, 32]
		})
	}

	return error ? (
			<pre>{JSON.stringify(error, null, 2)}</pre>
	) : ( 
		<div className="cploc">
			
			{ loading && (
				<div className="cploc-container--loading">
					<CircularProgress/>
				</div>
			)}

			{isDesktop ? (
				<DesktopFinder 
					userGeo={userGeo}
					onSearch={handleSearchInputChange}
					getMyLocation={getMyLocation}
					locations={locations}
					ChangeView={ChangeView}
					iconUser={pcIcon}
					getIconLocation={getIconLocation}
					getIconLocationCurrent={getIconLocationCurrent}
					initLocations={initLocations}
					initialSearchValue={initialSearchValue}
				/>
			) : (
				<MobileFinder
					userGeo={userGeo}
					onSearch={handleSearchInputChange}
					getMyLocation={getMyLocation}
					locations={locations}
					ChangeView={ChangeView}
					iconUser={pcIcon}
					getIconLocation={getIconLocation}
					getIconLocationCurrent={getIconLocationCurrent}
					initLocations={initLocations}
					initialSearchValue={initialSearchValue}
				/>
			)}
			
		</div>
	);
};

export default App;
