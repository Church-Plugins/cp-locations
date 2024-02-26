import {
	TextControl,
	RadioControl,
	__experimentalText as Text,
	Flex,
	Button,
	Popover,
	__experimentalVStack as VStack,
	CardDivider,
	CheckboxControl,
	ColorPicker
} from '@wordpress/components';
import { __ } from "@wordpress/i18n";
import { useState } from '@wordpress/element';
import { AccountCircle, Label } from '@mui/icons-material';
import { pencil, trash, settings } from '@wordpress/icons';
import { useDebounce } from '@wordpress/compose';

const capitalize = (string) => {
	return string.charAt(0).toUpperCase() + string.slice(1);
}

const slugify = (string) => {
	return string.toLowerCase().replace(/[\s\-]/g, '_').replace(/[^a-z0-9_]/g, '')
}

function CustomFieldEditor({ field = null, onSubmit }) {
	const initialField = field || {}
	const isEditing = !!field

	const [fieldType, setFieldType] = useState(initialField.type || 'text')
	const [fieldLabel, setFieldLabel] = useState(initialField.label || '')
	const [fieldKey, setFieldKey] = useState(initialField.key || '')

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
				placeholder={ __( 'Field Label', 'cp-locations' )}
				__nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Field Key', 'cp-locations' ) }
				value={fieldKey}
				onChange={value => setFieldKey(value)}
				placeholder={ __( 'field_label', 'cp-locations' ) }
				onFocus={() => !fieldKey && fieldLabel && setFieldKey(slugify(fieldLabel))}
				help={ __( 'Used internally to identify this field.', 'cp-locations' ) }
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

function CustomFields({ data, updateField }) {
	const [popoverAnchor, setPopoverAnchor] = useState()
	const [editingField, setEditingField] = useState(null)

	const existingFields = Object.values(data.custom_fields || {})

	return (
		<>
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
				<CustomFieldEditor
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

function LocationTypeEditor({ type = null, onSubmit }) {
	const initialType = type || {}
	const isEditing = !!type

	const [typeName, setTypeName] = useState(initialType.name)
	const [typeKey, setTypeKey] = useState(initialType.key)
	const [typeColor, setTypeColor] = useState(initialType.color)
	const [colorEditorOpen, setColorEditorOpen] = useState(false)

	const handleSubmit = () => {
		onSubmit({
			name: typeName,
			key: typeKey,
			color: typeColor
		})	
	}

	return (
		<VStack style={{ width: '300px', padding: '16px' }} spacing={4}>
			<TextControl
				label={ __( 'Location Type', 'cp-locations' ) }
				value={typeName}
				onChange={setTypeName}
				help={ __( 'The name of the location type.', 'cp-locations' ) }
				placeholder={ __( 'Office', 'cp-locations' )}
				__nextHasNoMarginBottom
			/>
			<TextControl
				label={ __( 'Key', 'cp-locations' ) }
				value={typeKey}
				onChange={setTypeKey}
				onFocus={() => !typeKey && typeName && setTypeKey(slugify(typeName))}
				placeholder={ __( 'office', 'cp-locations' ) }
				help={ __( 'Used internally to identify this field.', 'cp-locations' ) }
				__nextHasNoMarginBottom
			/>

			<Flex justify="start">
				<TextControl
					label={ __( 'Color', 'cp-locations' ) }
					value={typeColor}
					onChange={setTypeColor}
					placeholder={ __( '#333', 'cp-locations' ) }
					help={ __( 'Color of the map pin.', 'cp-locations' ) }
					__nextHasNoMarginBottom
				/>
				<Button
					variant="tertiary"
					icon={settings}
					onClick={() => !colorEditorOpen && setColorEditorOpen(true)}
				>
					{
						colorEditorOpen &&
						<Popover
							placement={'bottom-start'}
							onFocusOutside={() => setColorEditorOpen(false)}
						>
							<ColorPicker
								color={typeColor}
								onChange={setTypeColor}
							/>
						</Popover>
					}
				</Button>
			</Flex>
			
			<Button
				variant="primary"
				disabled={!typeName || !typeKey || !typeColor}
				onClick={handleSubmit}
				style={{ alignSelf: 'start' }}
			>
				{ isEditing ? __( 'Update', 'cp-locations' ) : __( 'Create', 'cp-locations' ) }
			</Button>
		</VStack>
	)
}

function LocationTypes({ data, updateField }) {
	const [popoverAnchor, setPopoverAnchor] = useState(null)
	const [editingType, setEditingType] = useState(null)

	return (
		<>
		<div>
			<h3>{ __('Location Types', 'cp-locations' ) }</h3>
			<Text variant="muted">{ __('Location types allow you to specify various location types, assigning colors to each.', 'cp-locations' ) }</Text>
		</div>
		<CheckboxControl
			label={ __( 'Enabled', 'cp-locations' ) }
			checked={data.location_types_enabled || false}
			onChange={value => updateField('location_types_enabled', value)}
		/>
		{
			!!data.location_types_enabled &&
			<>
			<table className="cp-settings-custom-fields">
				<thead>
					<tr>
						<th>{ __('Location Type', 'cp-locations' ) }</th>
						<th>{ __('Key', 'cp-locations' ) }</th>
						<th>{ __('Color', 'cp-locations' ) }</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					{
						Object.values(data.location_types || {}).map((type, index) => {
							return (
								<tr key={index}>
									<td>{type.name}</td>
									<td>{type.key}</td>
									<td>
										<div style={{ width: '20px', height: '20px', backgroundColor: type.color, borderRadius: '50%' }} />
									</td>
									<td>
										<Flex gap={2} justify="start">
											<Button
												icon={pencil}
												variant="tertiary"
												onClick={(event) => {
													setPopoverAnchor(popoverAnchor ? null : event.target)
													setEditingType(type)
												}}
											/>
											<Button isDestructive icon={trash} variant="text" onClick={() => {
												if(!window.confirm(__('Are you sure you want to delete this location type?', 'cp-locations'))) return
												const newTypes = {...data.location_types}
												delete newTypes[type.key]
												updateField('location_types', newTypes)
											}} />
										</Flex>
									</td>
								</tr>
							)
						})
					}
				</tbody>
			</table>
			<Button icon="plus-alt2" variant='tertiary' onClick={(event) => {
				setPopoverAnchor(event.target)
				setEditingType(null)
			}}>
				{ __('Add Location Type', 'cp-locations' ) }
			</Button>
			{
				popoverAnchor &&
				<Popover
					anchor={popoverAnchor}
					onFocusOutside={() => setPopoverAnchor(null)}
				>
					<LocationTypeEditor
						type={editingType}
						onSubmit={(type) => {
							const newTypeArray = [...Object.values(data.location_types || {})]
							// if a type is being edited, update it in the array. Otherwise, append it.
							if(editingType) {
								const typeIndex = newTypeArray.findIndex(t => t.key === editingType.key)
								newTypeArray[typeIndex] = type
							} else {
								newTypeArray.push(type)
							}

							const newTypes = newTypeArray.reduce((acc, type) => ({
								...acc,
								[type.key]: type
							}), {})

							updateField('location_types', newTypes)
							setPopoverAnchor(null)
						}}
					/>
				</Popover>
			}
			</>
		}
		</>
	)
}

export default function LocationsTab({ data = {}, updateField }) {
	const [editingPinColor, setEditingPinColor] = useState(false)
	const updatePinColor = useDebounce((value) => {
		updateField('user_pin_color', value)
	}, 250)

	return (
		<>
		<VStack>
			<h3>{ __('Labels', 'cp-locations' ) }</h3>
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
		</VStack>
		<CardDivider />
		<VStack>
			<h3>{ __( 'User Pin Color', 'cp-locations' ) }</h3>
			<VStack>
				<AccountCircle style={{ color: data.user_pin_color || '#333' }} />
				<Flex justify="start">
					<TextControl
						value={data.user_pin_color || ''}
						placeholder='#333'
						label={ __( 'Hex Code', 'cp-locations' ) }
						help={ __( 'The color of the icon representing the user\'s location.', 'cp-locations' ) }
						onChange={value => updateField('user_pin_color', value)}
						__nextHasNoMarginBottom
					/>
					<Button
						variant="tertiary"
						icon={settings}
						onClick={() => !editingPinColor && setEditingPinColor(true)}
					>
						{
							editingPinColor &&
							<Popover
								placement={'bottom-start'}
								onFocusOutside={() => {
									setEditingPinColor(false)
								}}
								onClose={() => {
									setEditingPinColor(false)
								}}
								closeOnEscape
							>
								<ColorPicker
									color={data.user_pin_color}
									onChange={updatePinColor}
								/>
							</Popover>
						}
					</Button>
				</Flex>
			</VStack>
		</VStack>
		<CardDivider />
		<VStack>
			<LocationTypes data={data} updateField={updateField} />
		</VStack>
		<CardDivider />
		<VStack>
			<CustomFields data={data} updateField={updateField} />
		</VStack>
		</>
	)
}
