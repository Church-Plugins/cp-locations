import { TextControl } from '@wordpress/components';
import { __ } from "@wordpress/i18n";

export default function LocationsTab({ data = {}, updateField }) {
	return (
			<>
			<TextControl
				className="cp-settings-text-field"
				value={data.singular_label || ''}
				label={ __( 'Singular Label', 'cp-locations' ) }
				help={ __( 'The singular label to use for Locations.', 'cp-locations' ) }
				onChange={value => updateField('singular_label', value)}
			/>
			<TextControl
				className="cp-settings-text-field"
				value={data.plural_label || ''}
				label={ __( 'Plural Label', 'cp-locations' ) }
				help={ __( 'The plural label to use for Locations.', 'cp-locations' ) }
				onChange={value => updateField('plural_label', value)}
			/>
		</>
	)
}
