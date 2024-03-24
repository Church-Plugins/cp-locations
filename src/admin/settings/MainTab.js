import { TextControl, ExternalLink, Button } from '@wordpress/components';
import { __ } from "@wordpress/i18n";

export default function MainTab({ data = {}, updateField }) {
	return (
			<>
			<TextControl
				className="cp-settings-text-field"
				value={data.mapbox_api_key || ''}
				label={ __( 'Mapbox API Key', 'cp-locations' ) }
				help={
					<span>
						{ __( 'The API key to use for the MapBox integration. To create a new key, create a free account for MapBox and copy the key from your ', 'cp-locations' ) }
						<ExternalLink href="https://account.mapbox.com/access-tokens/" target="_blank">{ __( 'Account' ) }</ExternalLink>
					</span>
				}
				onChange={value => updateField('mapbox_api_key', value)}
			/>
		</>
	)
}
