import { hot } from 'react-hot-loader';
import { useRef, createRef, useEffect, useState } from 'react';
import ReactDOM from 'react-dom';
import { MapContainer, Marker, Popup, TileLayer, useMap, latLngBounds } from 'react-leaflet';
import Controllers_WP_REST_Request from './Controllers/WP_REST_Request';

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


function ChangeView({ locations }) {
	if ( ! locations.length ) {
		return null;
	}
	
    const map = useMap();
    map.fitBounds(locations.map((location) => location.geodata.center));
		map.zoomOut();
    return null;
}

const App = () => {
	let markerRef = useRef([]);
	const [loading, setLoading] = useState(false);
	const [locations, setLocations] = useState([]);
	const [markers, setMarkers] = useState([]);
	const mapRef = useRef();
	const searchCenter = [51.505, -0.09];
	const onClick = ( index ) => {
		markerRef.current[index].openPopup();
	}
	
	useEffect(() => {
		(
			async () => {
				try {
					setLoading(true);
					const restRequest = new Controllers_WP_REST_Request();
					const data = await restRequest.get({endpoint: 'locations'});
					setLocations(data.locations);
				} catch (error) {
//					setError(error);
				} finally {
//					setLoading(false);
				}
			}
		)();
	}, [] );
	
	return (
		<div>
			
			<div className="cploc-map">
				<div className="cploc-map--tabs">
					{locations.map((location, index) => (
						<div className="cploc-map--tabs--tab cploc-map-tab" key={index} onMouseOver={() => onClick(index)}>
							<div className="cploc-map-tab--thumb"><div style={{backgroundImage: 'url(' + location.thumb.thumb + ')'}} /></div>
							<div className="cploc-map-tab--content">
								<h3 className="cploc-map-tab--title">{location.title}</h3>
								<div className="cploc-map-tab--address">{location.geodata.attr.place}, {location.geodata.attr.region}</div>
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
						
						<ChangeView locations={locations} />
						
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
