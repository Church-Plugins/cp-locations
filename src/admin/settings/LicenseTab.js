import { TextControl, CheckboxControl, Flex, Button, __experimentalText as Text } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';

export default function LicenseTab({ data = {}, updateField, save }) {
	const [licenseError, setLicenseError] = useState('')
	const [isFetching, setIsFetching] = useState(false)
	const [successMessage, setSuccessMessage] = useState('')

	const activateLicense = () => {
		if(isFetching) return

		setSuccessMessage('')
		setLicenseError('')
		setIsFetching(true)

		apiFetch({
			path: `/churchplugins/v1/license/activate/cploc_license`,
			method: 'POST',
			data: {
				license: data.license
			}
		}).then( response => {
			if (response.success) {
				updateField('status', 'valid')
				if (response.message) {
					setSuccessMessage(response.message)
				}
			} else {
				setLicenseError(response.message)
			}
		}).finally(() => {
			setIsFetching(false)
			save()
		})
	}

	const deactivateLicense = () => {
		if(isFetching) return
		
		setSuccessMessage('')
		setLicenseError('')
		setIsFetching(true)

		apiFetch({
			path: `/churchplugins/v1/license/deactivate/cploc_license`,
			method: 'POST'
		}).then( response => {
			if (response.success) {
				updateField('status', 'invalid')
				setSuccessMessage(__('License deactivated', 'cp-locations'))
			} else {
				setLicenseError(response.message)
			}
		}).finally(() => {
			setIsFetching(false)
			save()
		})
	}

	return (
		<>
		<Flex gap={4} align="end" justify="start">
			<TextControl
				value={data.license || ''}
				label={__('License Key', 'cp-locations')}
				onChange={value => updateField('license', value)}
				disabled={data.status === 'valid' || isFetching}				
			/>
			<Button
				variant="tertiary"
				style={{ marginBottom: '8px' }}
				size="compact"
				onClick={data.status === 'valid' ? deactivateLicense : activateLicense}
				isBusy={isFetching}
			>
				{ data.status === 'valid' ? __( 'Deactivate', 'cp-locations' ) : __('Activate', 'cp-locations')}
			</Button>
		</Flex>
		{
			licenseError &&
			<Text isDestructive>
				{licenseError}
			</Text>
		}
		{
			successMessage &&
			<Text isSuccess>
				{successMessage}
			</Text>
		}
		<CheckboxControl
			checked={!!data.beta || false}
			label={__('Enable Beta Updates', 'cp-locations')}
			onChange={value => updateField('beta', value)}
			help={__('Check this box to enable beta updates.', 'cp-locations')}
		/>
		</>
	)
}
