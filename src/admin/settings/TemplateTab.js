import { Button, Panel, PanelBody, PanelRow, Card, FormTokenField } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { useState, useEffect } from '@wordpress/element'
import { useSelect } from '@wordpress/data'
import settingsStore from './store'
import apiFetch from '@wordpress/api-fetch'

function SingleTemplate({ value, onChange }) {
	const [cmb2Fields, setCmb2Fields] = useState([])

	useEffect(() => {
		apiFetch({
			path: 'cmb2/v1/boxes/location_meta/fields'
		}).then(data => {
			console.log(data)
			setCmb2Fields(data)
		})
	}, [])

	const availableFields = [
		...Object.keys(cmb2Fields),
		'thumb',
		'title',
		'content',
		'permalink',
	]

	return (
		<div>
			<FormTokenField
				label={ __( 'Template', 'cp-locations' ) }
				help={ __( 'The template to use when displaying the location.', 'cp-locations' ) }
				value={value}
				onChange={onChange}
				suggestions={availableFields}
			/>
		</div>
	)
}

export default function TemplateTab({ data, updateField }) {
	const templates = data.custom_templates || {}

	const handleChange = (key, value) => {
		updateField('custom_templates', { ...templates, [key]: value })
	}

	const templateTypes = [
		{
			label: __( 'Tooltip', 'cp-locations' ),
			key: 'tooltip'
		},
		{
			label: __( 'Popup', 'cp-locations' ),
			key: 'popup'
		},
		{
			label: __( 'Sidebar Item', 'cp-locations' ),
			key: 'sidebar'
		},
		{
			label: __( 'Location Card', 'cp-locations' ),
			key: 'card'
		},
	]

	return (
		<>
		<Card style={{ width: '100%', maxWidth: 'max(50%, 750px)' }}>
			{
				templateTypes.map(({ label, key }) => (
					<PanelBody key={key} title={label} initialOpen={false}>
						<PanelRow>
							<SingleTemplate
								value={ templates[key] || [] }
								onChange={value => handleChange(key, value)}
							/>
						</PanelRow>
					</PanelBody>
				))
			}
		</Card>
		</>
	)
}
