import { createRoot } from "react-dom";
import { __ } from "@wordpress/i18n";
import { TabPanel,  Card, CardBody, Button } from "@wordpress/components";
import { useSelect, useDispatch } from '@wordpress/data';
import settingsStore from "./store";
import './index.scss';

import MainTab from "./MainTab";
import LocationsTab from "./LocationsTab";
import ShortcodesTab from "./ShortcodesTab";
import LicenseTab from "./LicenseTab";

function TabContent({ tab }) {
	const settingsGroup = tab.name
	
	let isStatic = false
	let Component = MainTab // default

	switch (tab.name) {
		case 'location_options':
			Component = LocationsTab
			break;
		case 'shortcodes':
			Component = ShortcodesTab
			isStatic = true
			break;
		case 'license':
			Component = LicenseTab
			break;
	}

	const { data, isSaving, error, isDirty, isHydrating } = useSelect( ( select ) => {
		return {
			data: select( settingsStore ).getSettingsGroup( settingsGroup ),
			isSaving: select( settingsStore ).isSaving(),
			error: select( settingsStore ).getError(),
			isDirty: select( settingsStore ).isDirty(),
			isHydrating: select( settingsStore ).isResolving( 'getSettingsGroup', [ settingsGroup ] )
		}
	} )

	const { persistSettingsGroup, setSettingsGroup } = useDispatch( settingsStore )

	const updateField = (key, value) => {
		setSettingsGroup(settingsGroup, { ...data, [key]: value })
	}

	const save = () => {
		persistSettingsGroup( settingsGroup, data )
	}

	return (
		<CardBody className={`cp-settings-tab-content cp-settings-tab-${settingsGroup}`}>
			{
				isStatic ?
				<Component /> :
				(isHydrating || ! data) ? 
				<div className='cp-settings-skeleton'>
					<div className='cp-settings-skeleton-line' style={{ width: '150px', height: '12px' }}></div>
					<div className='cp-settings-skeleton-line' style={{ width: '300px', height: '30px' }}></div>
					<div className='cp-settings-skeleton-line' style={{ width: '500px', height: '12px' }}></div>
					<div className='cp-settings-skeleton-line' style={{ width: '50px', height: '30px' }}></div>
				</div>  :
				<>
				<Component
					tabName={settingsGroup}
					data={data}
					isSaving={isSaving}
					error={error}
					updateField={updateField}
					save={save}
				/>
				<Button
					onClick={save}
					variant={ isDirty ? "primary" : "tertiary" }
					isBusy={isSaving}
					disabled={!isDirty}>
						{ __( 'Save', 'cp-locations' ) }
				</Button>
				</>
			}
		</CardBody>
	)
}

// Settings main entrypoint.
function Settings() {
	return (
		<div className="cp-settings-wrapper">
			<h1>{ __( 'Settings', 'cp-locations' ) }</h1>
			<Card>
				<TabPanel
					className='cp-settings-tabs'
					activeClass="active-tab"
					initialTabName={ (new URL(window.location.href)).searchParams.get('tab') || 'main_options' }
					tabs={[
						{
							name: 'main_options',
							title: __( 'Main', 'cp-locations' ),
							className: 'main-tab',
						},
						{
							name: 'location_options',
							title: __( 'Locations', 'cp-locations' ),
							className: 'locations-tab',
						},
						{
							name: 'shortcodes',
							title: __( 'Shortcodes', 'cp-locations' ),
							className: 'shortcodes-tab',
						},
						{
							name: 'license',
							title: __( 'License', 'cp-locations' ),
							className: 'license-tab',
						}
					]}
					onSelect={(tabName) => {
						// add tab query param
						const url = new URL(window.location.href)
						url.searchParams.set('tab', tabName)
						window.history.pushState({}, '', url)
					}}
				>
					{ (tab) => <TabContent tab={tab} /> }
				</TabPanel>
			</Card>
		</div>
	)
}

jQuery($ => {
	$('.cp_settings_root.cp-locations').each(function() {
		const root = createRoot(this)
		
		root.render(<Settings />)
	})
})
