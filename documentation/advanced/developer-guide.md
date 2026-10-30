---
title: "Developer Guide"
slug: developer-guide-cp-locations
section: advanced-cp-locations
order: 1
excerpt: "Hooks, filters, REST API, and template customization for CP Locations."
status: publish
---

# Developer Guide

This guide covers hooks, filters, the REST API, and template customization for CP Locations.

## Architecture Overview

CP Locations follows the standard ChurchPlugins architecture:

- **Post Type:** `cploc_location` — Church locations with meta fields for address, contact info, and service times
- **Frontend:** React 18 app with Leaflet maps, powered by a REST API
- **Geocoding:** MapBox integration with caching

## REST API

All endpoints are public (no authentication required).

### GET /cp-locations/v1/locations

Returns all locations with geo data and rendered templates.

**Response:**

```
{
  "count": 3,
  "locations": [
    {
      "id": 1,
      "originID": 23,
      "permalink": "https://example.com/location/downtown/",
      "slug": "downtown",
      "thumb": { "thumb": "...", "thumbnail": "...", "medium": "...", "large": "...", "full": "..." },
      "title": "Downtown Campus",
      "subtitle": "Main Campus",
      "pastor": "Pastor Smith",
      "desc": "<p>Location description</p>",
      "address": "123 Main St<br>City, ST 12345",
      "phone": "555-555-5555",
      "email": "downtown@church.com",
      "times": "Sundays at 9:00am and 11:00am",
      "geodata": {
        "center": [40.7128, -74.0060],
        "attr": { "postcode": "10001", "place": "New York", "region": "New York" }
      },
      "templates": { "card": "<div>...</div>", "popup": "<div>...</div>" }
    }
  ]
}
```

### GET /cp-locations/v1/locations/{id}

Returns a single location by post ID.

### GET /cp-locations/v1/locations/postcode/{postcode}

Geocodes a zip code and returns coordinates. Used by the frontend search.

## Hooks and Filters

### Location Data

| Hook | Type | Description |
| --- | --- | --- |
| `cploc_location_meta_details` | action | Add custom metabox fields. Receives `$cmb` (CMB2 object) and `$location` (PostType instance). |
| `cploc_format_times` | filter | Customize the formatted service times HTML string |
| `cploc_location_get_api_data` | filter | Modify the REST API response for a location |
| `cpl_get_all_locations` | filter | Filter the locations query result set |

### Frontend

| Hook | Type | Description |
| --- | --- | --- |
| `cploc_app_vars` | filter | Modify the JavaScript config object passed to the React app |

### Settings

| Hook | Type | Description |
| --- | --- | --- |
| `cploc_settings_get` | filter | Override any settings value. Receives `$value`, `$key`, `$group`. |
| `cp_loc_mapbox_api_key` | filter | Override the MapBox API key |

### Templates

| Hook | Type | Description |
| --- | --- | --- |
| `cploc_template_paths` | filter | Add additional template search paths |
| `cploc_template` | filter | Override a specific template file path |

## Code Examples

### Add a Custom Meta Field

```
add_action( 'cploc_location_meta_details', function( $cmb, $location ) {
    $cmb->add_field( [
        'name' => 'Parking Info',
        'id'   => 'parking_info',
        'type' => 'textarea_small',
    ] );
}, 10, 2 );
```

### Customize Service Time Format

```
add_filter( 'cploc_format_times', function( $formatted ) {
    return str_replace( 'at ', '@ ', $formatted );
});
```

### Modify Frontend App Configuration

```
add_filter( 'cploc_app_vars', function( $vars ) {
    $vars['components']['mobileTop'] = '<p>Find a campus near you</p>';
    return $vars;
});
```

## Template Overrides

Override plugin templates by placing files in your theme:

1. Create `/wp-content/themes/your-theme/cp-locations/`
2. Copy the template you want to override from the plugin's `templates/` directory
3. Edit the copy in your theme

The template receives a `$args` array with a `location` key containing a `CP_Locations\Controllers\Location` instance. Access location data like:

```
$location = $args['location'];
echo $location->get_title();
echo $location->get_formatted_times();
echo $location->pastor; // Magic getter for post meta
```

## Constants

| Constant | Default | Description |
| --- | --- | --- |
| `CP_LOCATIONS_PREFIX` | `'cpl'` | Plugin prefix for options and meta |
