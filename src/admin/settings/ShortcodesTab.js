import { Card, CardHeader, CardBody, __experimentalElevation as Elevation } from '@wordpress/components'

export default function ShortcodesTab() {
	return (
		<>
		<Card>
			<CardHeader>
				<code>[cp-locations]</code>
			</CardHeader>
			<CardBody>
				Use the [cp-locations] shortcode on your main locations page to show the locations map and cards.
			</CardBody>
			<Elevation value={3} />
		</Card>
		
		<Card>
			<CardHeader>
				<code>[cp-locations-data]</code>
			</CardHeader>
			<CardBody>
				Use the [cp-locations-data] shortcode to display information about a location.
				<br />
				Args:
				<br />
				* location (the ID of the location to show data for)
				<br />
				* field (the data to retrieve, available options are 'title', 'service_times', 'subtitle', 'address', 'email', 'phone', 'pastor')
				<br />
				Example: <code>[cp-locations location=23 field='service_times']</code>
			</CardBody>
			<Elevation value={3} />
		</Card>
		</>
	)
}
