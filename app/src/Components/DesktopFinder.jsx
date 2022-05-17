import { useRef, useState } from 'react';
import { MapContainer, Marker, Popup, TileLayer, Tooltip, ZoomControl } from 'react-leaflet';
import SearchInput from '../Elements/SearchInput';
import { MyLocation } from '@mui/icons-material';

const DesktopFinder = ({
	userGeo,
	onSearch,
	getMyLocation,
	locations,
	ChangeView,
	iconLocation,
	iconUser
}) => {
	let markerRef = useRef([]);
	const [mode, setMode] = useState( 'map' );
	
	const onClick = ( index ) => {
		markerRef.current[index].openPopup();
	}
	
	const closePopups = () => {
		locations.map((location, index) => ( markerRef.current[index].closePopup() ));
	}
	
	return (
		<div className="cploc-container cploc-container--desktop">
			
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
							<SearchInput onValueChange={onSearch} className="cploc-map--search" />
							<button className="cploc-map--my-location" onClick={getMyLocation}><MyLocation /></button>
						</div>
	
						<MapContainer scrollWheelZoom={false} zoomControl={false}>
							<TileLayer
								attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
								url="https://api.mapbox.com/styles/v1/mapbox-map-design/ckshxkppe0gge18nz20i0nrwq/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoidGFubmVybW91c2hleSIsImEiOiJjbDFlaWEwZ2IwaHpjM2NsZjh4Z2s3MHk2In0.QGwQkxVGACSg4yQnFhmjuw"
							/>
							
							<ChangeView locations={locations} userGeo={userGeo} />
	
							{userGeo && (
								<Marker icon={iconUser} position={userGeo.center} />
							)}
							
							{locations.map((location, index) => (
								<Marker ref={(el) => (markerRef.current[index] = el)} key={index} position={location.geodata.center} onClick={(e) => e.preventDefault()}>
									{0 && (<Tooltip direction="center" permanent={true}>{location.title}</Tooltip>)}
									<Popup><div dangerouslySetInnerHTML={{__html: location.templates.popup }} /></Popup>
								</Marker>	
							))}
							
							<ZoomControl position="bottomleft"  />
						</MapContainer>
	
					</div>

				</div>
			
				<div className="cploc-list" style={mode === 'list' ? {} : { display: 'none' }}>
	        <div>
		        <SearchInput onValueChange={onSearch} className="cploc-map--search" />
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

export default DesktopFinder;
