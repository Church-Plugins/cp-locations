import { useEffect, useState } from 'react';
import { MapContainer, Marker, TileLayer } from 'react-leaflet';
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
	iconUser,
	iconLocationCurrent
}) => {
	const [mode, setMode] = useState( 'map' );
	const [listPane, setListPane] = useState({} );
	const [currentLocation, setCurrentLocation] = useState( {} );
	let fitBoundsTimeout;
	const [map, setMap] = useState(null);
	
	const onClick = ( url ) => {
		window.location = url;
	}
	
	const selectLocation = async ( index ) => {
		setCurrentLocation( locations[ index ] );
		setMode( 'location' );
		await listPane.moveToBreak('bottom');
		setMode( 'location' );
	};
	
	const switchPaneMode = async () => {
		// give instant feedback
		setMode( listPane.currentBreak() === 'bottom' ? 'list' : 'map' )
		
		await listPane.moveToBreak('list' !== mode ? 'top' : 'bottom' );
		
		// current breakpoint is set after transition, so need to set this manually
		setMode( listPane.currentBreak() === 'top' ? 'list' : 'map' )
	};
	
	useEffect( () => {
		const html = document.documentElement;
		html.style.setProperty('--cploc-app-height', window.innerHeight + 'px');
		
		if ( /iP(ad|od|hone)/i.test(window.navigator.userAgent)
		     && !!navigator.userAgent.match(/Version\/[\d\.]+.*Safari/) ) {
			html.className = html.className.concat( ' is-ios-safari' );
		}
		
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
		
		const bottomOffset = () => { return window.innerHeight - document.querySelector('.cploc-map .leaflet-container').offsetHeight };
		const headerHeight = document.querySelector('.cploc-map--locations--header').offsetHeight;
		
		const locationPane = new CupertinoPane( '.cploc-map--locations-mobile', {
			parentElement: '.cploc-map',
			breaks: {
				bottom: { enabled: true, height: headerHeight + bottomOffset() },
				middle: { enabled: false }
			},
			initialBreak: 'bottom',
			touchMoveStopPropagation: true,
			buttonDestroy: false,
			fitScreenHeight: false,
			maxFitHeight: document.querySelector('.cploc-map .leaflet-container').offsetHeight,
			bottomOffset: 15,
			topperOverflowOffset: bottomOffset(),
			dragBy: ['.pane .draggable', '.cploc-map--locations--header' ],
		} );
		
		locationPane.on('onTransitionEnd', () => setMode( locationPane.currentBreak() === 'bottom' ? 'map' : 'list' ) );

		locationPane.updateScreenHeights = () => {
			locationPane.screen_height = window.innerHeight;
			locationPane.screenHeightOffset = window.innerHeight;
			locationPane.settings.maxFitHeight = document.querySelector('.cploc-map .leaflet-container').offsetHeight;
			locationPane.settings.topperOverflowOffset = bottomOffset();
			locationPane.settings.breaks.bottom = {enabled: true, height: headerHeight + bottomOffset()};
			locationPane.settings.breaks.top = {enabled: true, height: locationPane.settings.maxFitHeight};
			locationPane.breakpoints.lockedBreakpoints = JSON.stringify(locationPane.settings.breaks);
		}
		
		locationPane.present({animate: true}).then();
		setListPane( locationPane );
		
	}, [] );
	
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
		const paddingBottomRight = [100, 100];
		fitBoundsTimeout = setTimeout(
			() => map.fitBounds(features.map((feature) => feature.geodata.center), {paddingTopLeft, paddingBottomRight}),
			100);

	}, [locations, userGeo])
	
	return ( 
		<div className="cploc-container cploc-container--mobile">
			
				<div className="cploc-map">
					
					<div className="cploc-map--map">
						<div className="cploc-map--controls">
							<SearchInput onValueChange={onSearch} className="cploc-map--search" />
							<button className="cploc-map--my-location" onClick={getMyLocation}><MyLocation /></button>
						</div>
	
						<MapContainer whenCreated={setMap} scrollWheelZoom={false} zoomControl={false} gestureHandling={true}>
							<TileLayer
								attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
								url="https://api.mapbox.com/styles/v1/mapbox-map-design/ckshxkppe0gge18nz20i0nrwq/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoidGFubmVybW91c2hleSIsImEiOiJjbDFlaWEwZ2IwaHpjM2NsZjh4Z2s3MHk2In0.QGwQkxVGACSg4yQnFhmjuw"
							/>
							
							<ChangeView locations={locations} userGeo={userGeo} />
	
							{userGeo && (
								<Marker icon={iconUser} position={userGeo.center} />
							)}
							
							{locations.map((location, index) => (
								<Marker key={index} 
								        position={location.geodata.center}
								        icon={('location' === mode && currentLocation == location) ? iconLocationCurrent : iconLocation }
								        eventHandlers={{
									        click: (e) => {
										        selectLocation( index );
									        },
								        }}/>
							))}
						</MapContainer>
	
						<div id="cploc-map-pane" className="cploc-map--locations-mobile" >
							<div className="cploc-map--locations--header">
								<h2>{locations.length} {1 < locations.length ? (<span>Locations</span>) : (<span>Location</span>)}</h2>
							</div>
							{locations.map((location, index) => (
								<div className="cploc-map--locations--location cploc-map-location" key={index} onClick={() => onClick(location.permalink)}>
									<div className="cploc-map-location--thumb"><div style={{backgroundImage: 'url(' + location.thumb.thumb + ')'}} /></div>
									<div className="cploc-map-location--content">
										<h3 className="cploc-map-location--title">{location.title}</h3>
										<div className="cploc-map-location--desc">{location.pastor}</div>
		
										<div className="cploc-map-location--times"></div>
									</div>
								</div>
							))}
							
						</div>
						
					</div>

					{'location' === mode && (
						<div className="cploc-map--locations-current">
							<div className="cploc-map--locations--location cploc-map-location" onClick={() => onClick(currentLocation.permalink)}>
								<div className="cploc-map-location--thumb">
									<div style={{backgroundImage: 'url(' + currentLocation.thumb.thumb + ')'}}/>
								</div>
								<div className="cploc-map-location--content">
									<h3 className="cploc-map-location--title">{currentLocation.title}</h3>
									<div className="cploc-map-location--desc">{currentLocation.pastor}</div>
								</div>
							</div>

						</div>
					)}
					
					<div className="cploc-map--locations--mode" onClick={switchPaneMode}>{'list' === mode ? (<span><span className="material-icons">map</span> Map View</span>) : (<span><span className="material-icons">list</span> List View</span>) }</div>

				</div>

		</div>
	);
};

export default MobileFinder;
