import { useRef, useEffect, useState } from 'react';
import { MapContainer, Marker, TileLayer, Tooltip,useMap, ZoomControl } from 'react-leaflet';
import SearchInput from '../Elements/SearchInput';
import { CupertinoPane } from 'cupertino-pane';
import { MyLocation } from '@mui/icons-material';

const MobileFinder = ({
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
	const [listPane, setListPane] = useState({} );
	
	const switchPaneMode = () => {
		if (listPane.currentBreak() === 'top') {
      listPane.moveToBreak('bottom');
			setMode('map');
		} else {
			listPane.moveToBreak('top');
			setMode('list');
		}
	}
	
	useEffect( () => {
		var blockScroll = false;
		document.addEventListener('touchmove', (e) => {
			if ( blockScroll ) {
				e.preventDefault();
				return false;
			}	
		}, { passive: false } );
		
		document.addEventListener('touchend', (e, x) => {
			blockScroll = false;
		}, { passive: false } );

		document.addEventListener('touchstart', (e) => {
			let draggable = document.querySelectorAll('.pane .draggable');
			for ( let i = 0, len = draggable.length; i < len; i ++ ) {
				if ( draggable[i] === e.target || draggable[i].contains(e.target) ) {
					blockScroll = true;
					e.preventDefault();
				}
			}
		}, { passive: false } );
		
		const locationPane = new CupertinoPane( '.cploc-map--locations-mobile', {
			parentElement: '.cploc-map',
			breaks: {
				bottom: { enabled: true, height: document.querySelector('.cploc-map--locations--header').offsetHeight + 100 },
				middle: { enabled: false }
			},
			initialBreak: 'bottom',
			touchMoveStopPropagation: true,
			buttonDestroy: false,
			fitScreenHeight: false,
			dragBy: ['.pane .draggable', '.cploc-map--locations--header' ],
			onDragEnd : () => setMode( locationPane.currentBreak() === 'bottom' ? 'map' : 'list' ),
		} );
		
		locationPane.present({animate: true}).then();
		setListPane( locationPane );
		
	}, [] );
	
	return ( 
		<div className="cploc-container cploc-container--mobile">
			
				<div className="cploc-map">
					
					<div className="cploc-map--map">
						<div className="cploc-map--controls">
							<SearchInput onValueChange={onSearch} className="cploc-map--search" />
							<button className="cploc-map--my-location" onClick={getMyLocation}><MyLocation /></button>
						</div>
	
						<MapContainer scrollWheelZoom={false} zoomControl={false} dragging={false}>
							<TileLayer
								attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
								url="https://api.mapbox.com/styles/v1/mapbox-map-design/ckshxkppe0gge18nz20i0nrwq/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoidGFubmVybW91c2hleSIsImEiOiJjbDFlaWEwZ2IwaHpjM2NsZjh4Z2s3MHk2In0.QGwQkxVGACSg4yQnFhmjuw"
							/>
							
							<ChangeView locations={locations} userGeo={userGeo} />
	
							{userGeo && (
								<Marker icon={iconUser} position={userGeo.center} />
							)}
							
							{locations.map((location, index) => (
								<Marker ref={(el) => (markerRef.current[index] = el)} key={index} position={location.geodata.center}>
									{0 && (<Tooltip direction="center" permanent={true}>{location.title}</Tooltip>)}
								</Marker>	
							))}
						</MapContainer>
	
						<div id="cploc-map-pane" className="cploc-map--locations-mobile" >
							<div className="cploc-map--locations--header">
								<h3>{locations.length} {1 < locations.length ? (<span>Locations</span>) : (<span>Location</span>)}</h3>
							</div>
							{locations.map((location, index) => (
								<div className="cploc-map--locations--location cploc-map-location" key={index}>
									<div className="cploc-map-location--thumb"><div style={{backgroundImage: 'url(' + location.thumb.thumb + ')'}} /></div>
									<div className="cploc-map-location--content">
										<h3 className="cploc-map-location--title">{location.title}</h3>
										<div className="cploc-map-location--address">{location.geodata.attr.place}, {location.geodata.attr.region} {(userGeo && location.distanceDesc) && (<span className="cploc-map-location--distance">({location.distanceDesc}mi)</span>)}</div>
		
										<div className="cploc-map-location--times"></div>
									</div>
								</div>
							))}
							
						</div>
						
					</div>
					
					<div className="cploc-map--locations--mode" onClick={switchPaneMode}>{'list' === mode ? (<span>Map View</span>) : (<span>List View</span>) }</div>

				</div>
			
		</div>
	);
};

export default MobileFinder;
