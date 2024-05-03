import {
	TextControl,
	RadioControl,
	__experimentalText as Text,
	Flex,
	FlexItem,
	FlexBlock,
	Button,
	Popover,
	__experimentalVStack as VStack,
	Card,
	CardBody,
	Icon
} from '@wordpress/components';
import { __ } from "@wordpress/i18n";
import { useState } from '@wordpress/element';
import { Label } from '@mui/icons-material';
import { pencil, trash } from '@wordpress/icons';

const capitalize = (string) => {
	return string.charAt(0).toUpperCase() + string.slice(1);
}

function FieldEditor({ field = null, onSubmit }) {
	const initialField = field || {}

	const [fieldType, setFieldType] = useState(initialField.type || 'text')
	const [fieldLabel, setFieldLabel] = useState(initialField.label)
	const [fieldKey, setFieldKey] = useState(initialField.key)

	const isEditing = !!field

	const handleSubmit = () => {
		onSubmit({
			type: fieldType,
			label: fieldLabel,
			key: fieldKey
		})	
	}

	return (
		<VStack style={{ width: '300px', padding: '16px' }} spacing={4}>
			<RadioControl
				label={ __( 'Field Type', 'cp-locations' ) }
				selected={fieldType}
				options={[
					{ label: __( 'Text', 'cp-locations' ), value: 'text' },
					{ label: __( 'Textarea', 'cp-locations' ), value: 'textarea' },
				]}
				onChange={value => setFieldType(value)}
				dir="horizontal"
			/>
			<TextControl
				label={ __( 'Field Label', 'cp-locations' ) }
				value={fieldLabel}
				onChange={value => setFieldLabel(value)}
				help={ __( 'The label the field will have when editing a location.', 'cp-locations' ) }
				placeholder={ __( 'Address', 'cp-locations' )}
				__nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Field Key', 'cp-locations' ) }
				value={fieldKey}
				onChange={value => setFieldKey(value)}
				placeholder={ __( 'address', 'cp-locations' ) }
				help={ __( 'The field Key.', 'cp-locations' ) }
				__nextHasNoMarginBottom
			/>
			<Button
				variant="primary"
				disabled={!fieldLabel || !fieldKey}
				onClick={handleSubmit}
				style={{ alignSelf: 'start' }}
			>
				{ isEditing ? __( 'Update', 'cp-locations' ) : __( 'Create', 'cp-locations' ) }
			</Button>
		</VStack>
	)
}

export default function LocationsTab({ data = {}, updateField }) {
	const [popoverAnchor, setPopoverAnchor] = useState()
	const [editingField, setEditingField] = useState(null)

	const existingFields = Object.values(data.custom_fields || {})

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
		<div>
			<h3>{ __('Custom Fields', 'cp-locations' ) }</h3>
			<Text variant="muted">{ __('Add custom fields to the Locations post type.', 'cp-locations' ) }</Text>
		</div>
		{
			Object.keys(existingFields).length > 0 &&
			<table className="cp-settings-custom-fields">
				<thead>
					<tr>
						<th>{ __('Field Type', 'cp-locations' ) }</th>
						<th>{ __('Label', 'cp-locations' ) }</th>
						<th>{ __('Key', 'cp-locations' ) }</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				{
					existingFields.map((field, index) => {
						return (
							<tr key={index}>
								<td>
									<Text color="var(--wp-admin-theme-color)">{capitalize(field.type)}</Text>
								</td>
								<td>
									{field.label}
								</td>
								<td>{field.key}</td>
								<td>
									<Flex gap={2} justify="start">
										<Button icon={pencil} variant="tertiary" onClick={(event) => {
											setPopoverAnchor(popoverAnchor ? null : event.target)
											setEditingField(field)
										}} />
										<Button isDestructive icon={trash} variant="text" onClick={() => {
											if(!window.confirm(__('Are you sure you want to delete this field?', 'cp-locations'))) return
											const newFields = {...data.custom_fields}
											delete newFields[field.key]
											updateField('custom_fields', newFields)
										}} />
									</Flex>
									
								</td>
							</tr>
						)
					})
				}
				</tbody>
			</table>
		}
		<Button icon="plus-alt2" variant='tertiary' onClick={(event) => {
			setPopoverAnchor(event.target)
			setEditingField(null)
		}}>
			{ __('Add Field', 'cp-locations' ) }
		</Button>		

		{
				popoverAnchor &&
				<Popover
					anchor={popoverAnchor}
					onFocusOutside={() => setPopoverAnchor(null)}
				>
					<FieldEditor
						field={editingField}
						onSubmit={(field) => {
							const newFieldArray = [...existingFields]
							// if a field is being edited, update it in the array. Otherwise, append it.
							if(editingField) {
								const fieldIndex = newFieldArray.findIndex(f => f.key === editingField.key)
								newFieldArray[fieldIndex] = field
							}else {
								newFieldArray.push(field)
							}

							const newFields = newFieldArray.reduce((acc, field) => {
								return {
									...acc,
									[field.key]: field
								}
							}, {})

							updateField('custom_fields', newFields)
							setPopoverAnchor(null)
						}}
					/>
				</Popover>
			}
		</>
	)
}
