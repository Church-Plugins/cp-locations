---
title: "Shortcodes"
slug: shortcodes-cp-locations
section: options-and-features-cp-locations
order: 4
excerpt: "Embed location content on your site with the location finder and location data shortcodes."
status: publish
---

# Shortcodes

CP Locations provides two shortcodes for embedding location content on your site.

## Location Finder Shortcode

```
[cp-locations]
```

Renders the full interactive location finder with map and list views. This shortcode takes no parameters — all configuration is handled through the plugin settings and location data.

Add this shortcode to any page to create your locations page. See the [Location Finder](location-finder.md) guide for details on the user experience.

## Location Data Shortcode

```
[cp-location-data location=ID field='field_name']
```

Displays a specific piece of data for a single location. Use this to embed individual location details (address, phone, service times, etc.) anywhere on your site.

### Parameters

| Parameter | Required | Default | Description |
| --- | --- | --- | --- |
| `location` | No | Current location context | The post ID of the location |
| `field` | No | *(empty)* | Which data field to display |

### Available Fields

| Field | Description |
| --- | --- |
| `title` | The location name |
| `subtitle` | The location subtitle |
| `address` | The physical address |
| `phone` | Phone number |
| `email` | Email address |
| `pastor` | Pastor/leader name |
| `service_times` | Formatted service times (grouped by day) |

Any other value for `field` will attempt to retrieve that key from the location's post meta.

### Examples

Display a specific location's address:

```
[cp-location-data location=23 field='address']
```

Display service times:

```
[cp-location-data location=23 field='service_times']
```

Display the pastor's name:

```
[cp-location-data location=23 field='pastor']
```

## Finding a Location's ID

To find the post ID for a location:

1. Navigate to **Locations** in the admin
2. Hover over a location name
3. The ID appears in the URL shown in your browser's status bar (e.g., `post=23`)

Alternatively, click **Edit** on the location — the ID is visible in the browser address bar.

## Shortcode Reference in Settings

Navigate to **Locations → Settings → Shortcodes** tab for a built-in reference of available shortcodes and their usage.
