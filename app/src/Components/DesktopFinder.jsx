import { useRef, useState, useEffect } from 'react';
import { MapContainer, Marker, Popup, TileLayer, Tooltip, ZoomControl, useMap } from 'react-leaflet';
import SearchInput from '../Elements/SearchInput';
import { MyLocation } from '@mui/icons-material';

const DesktopFinder = ({
	userGeo,
	onSearch,
	getMyLocation,
	locations,
	initLocations,
	iconLocation,
	iconUser,
	iconLocationCurrent
}) => {
	let markerRef = useRef([]);
	let fitBoundsTimeout;
	const [mode, setMode] = useState( 'map' );
	const [activeLocation, setActiveLocation] = useState(-1);
	const [map, setMap] = useState(null);
	
	const onClick = ( index ) => {
//		setTimeout(() => markerRef.current[index].openPopup(), 50);
		setActiveLocation(index);
		window.location = locations[index].permalink;
	}
	
	const closePopups = () => {
		locations.map((location, index) => ( markerRef.current[index].closePopup() ));
	}
	
	useEffect( () => {
		if (typeof fitBoundsTimeout === 'number') {
			clearTimeout(fitBoundsTimeout);
		}

		if (!locations.length) {
			return null;
		}

		const features = [...locations];

		if (userGeo) {
			features.push({geodata: {center: userGeo.center}});
		}

		const paddingTopLeft = [50, 100];
		const paddingBottomRight = [50, 100];
		fitBoundsTimeout = setTimeout(
			() => map.fitBounds(features.map((feature) => feature.geodata.center), {paddingTopLeft, paddingBottomRight}),
			100);

	}, [locations, userGeo])
	
	return (
		<div className="cploc-container cploc-container--desktop">
			
				<div className="cploc-map" style={mode === 'map' ? {} : { display: 'none' }}>
				
					<div className="cploc-map--locations">

						{initLocations.length > 0 && (
							<div className="cploc-map--locations--header">
								<h2>{initLocations.length} Locations</h2>
							</div>
						)}
	
						<div className="cploc-map--locations--list--cont">
							<div className="cploc-map--locations--list">
								<div className="cploc-map--locations--mode">
									<span className="cploc--mode-switch" onClick={() => {
										closePopups();
										setMode('list');
									}}>Hide map</span>
								</div>

								{userGeo !== false && (
									<div className="cploc-map--locations--search">
										{locations.length ? (
											<span>Showing results for</span>
										) : (
											<span>No results found for</span>
										)} '{userGeo.attr.postcode}'
									</div>
								)}
								{locations.map((location, index) => (
									<div className={"cploc-map--locations--location cploc-map-location" + ( activeLocation === index ? ' cploc-map-location--active' : '')} 
									     key={index} 
									     onClick={() => onClick(index)} 
									     onMouseOver={() => setActiveLocation(index)}
									>
										<div className="cploc-map-location--thumb"><div style={{backgroundImage: 'url(' + location.thumb.thumb + ')'}} /></div>
										<div className="cploc-map-location--content">
											<h3 className="cploc-map-location--title">{location.title}</h3>
											<div className="cploc-map-location--address">{location.geodata.attr.place}, {location.geodata.attr.region} {(userGeo && location.distanceDesc + 'mi') && (<span className="cploc-map-location--distance">({location.distanceDesc})</span>)}</div>
			
											<div className="cploc-map-location--times"></div>
										</div>
									</div>
								))}
							</div>
						</div>
					</div>
					<div className="cploc-map--map">
	
						<MapContainer whenCreated={setMap} scrollWheelZoom={false} zoomControl={false}>
							
							<TileLayer
								attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
								url="https://api.mapbox.com/styles/v1/mapbox-map-design/ckshxkppe0gge18nz20i0nrwq/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoidGFubmVybW91c2hleSIsImEiOiJjbDFlaWEwZ2IwaHpjM2NsZjh4Z2s3MHk2In0.QGwQkxVGACSg4yQnFhmjuw"
							/>

							<div className="cploc-map--controls">
								<SearchInput onValueChange={onSearch} className="cploc-map--search"/>
								<button className="cploc-map--my-location" onClick={getMyLocation}><MyLocation/></button>
							</div>
							
							{userGeo !== false && (
								<Marker icon={iconUser} position={userGeo.center} />
							)}
							
							{locations.map((location, index) => (
								<Marker 
									ref={(el) => (markerRef.current[index] = el)} 
									key={index} 
									position={location.geodata.center}
									icon={(activeLocation == index) ? iconLocationCurrent : iconLocation }
									eventHandlers={{
										mouseover: (e) => {
											setActiveLocation(index);
										},
										click: (e) => {
											onClick(index);
										}
									}}
								>
									{activeLocation == index && (
										<Tooltip direction="bottom" permanent>{location.title}</Tooltip>		
									) }

									{false && (
										<Popup 
											offset={[0, -15]} autoPanPadding={[50, 100]} 
											onClose={() => setActiveLocation(-1)}
										>
											<div dangerouslySetInnerHTML={{__html: location.templates.popup }} />
										</Popup>
									)}
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
						{userGeo !== false && (
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
								{(userGeo !== false && location.distanceDesc) && (<div className="cploc-list-item--distance">{location.distanceDesc} miles away</div>)}
								<div className="cp-button" onClick={() => { setMode('map'); setTimeout(() => onClick(index), 250); }}>View on Map ></div>
							</div>
						))}
					</div>
					
				</div>
			
		</div>
	);
};

export default DesktopFinder;
