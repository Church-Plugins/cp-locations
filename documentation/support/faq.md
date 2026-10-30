---
title: "Frequently Asked Questions"
slug: faq-cp-locations
section: support-cp-locations
order: 1
excerpt: "Frequently asked questions about CP Locations."
status: publish
---

# Frequently Asked Questions

## General

### What is CP Locations?

CP Locations is a WordPress plugin for managing multi-campus church locations. It provides an interactive map and list finder, service time management, and contact details.

### Do I need a MapBox account?

Yes. CP Locations uses MapBox for map tiles and address geocoding. MapBox offers a free tier that includes 50,000 map loads and 100,000 geocoding requests per month, which is sufficient for most churches. Create an account at [account.mapbox.com](https://account.mapbox.com).

### How many locations can I add?

There is no limit on the number of locations. The map automatically adjusts to show all locations with marker clustering for nearby campuses.

## Setup

### How do I add the location finder to my site?

Add the `[cp-locations]` shortcode to any page. The shortcode renders a full interactive map and list view with no additional parameters needed. See the [Location Finder](../features/location-finder.md) guide.

### How do I change the "Locations" label?

Navigate to **Locations → Settings → Locations** tab and change the **Singular Label** and **Plural Label** fields. The admin menu name updates immediately.

### Can I change the display order of locations?

Yes. Edit each location and set the **Order** value in the **Page Attributes** panel. Lower numbers appear first in the finder.

## Features

### Does the zip code search work outside the US?

The zip code search is configured for US postal codes (5-digit format). It triggers automatically when exactly 5 digits are entered. For international sites, the "Use My Location" feature works globally using the browser's geolocation API.

### What is the search radius?

The zip code search shows locations within 100 miles of the searched zip code, sorted by distance. If no locations are within range, a "No results found" message appears.

### Can visitors use their current location?

Yes. The location finder includes a "Use My Location" button that uses the browser's geolocation API. The visitor must grant permission when prompted. Locations are then sorted by distance from their current position.

### What are "special" service times?

Special service times are excluded from the standard formatted display. Use them for holiday services, temporary services, or other times you want to track but not show in the default listing. They are stored in the database but excluded from both the formatted times output and the REST API `times` field.

## Troubleshooting

### My map is blank — what should I check?

First, verify your MapBox API key in **Locations → Settings → Main**. Then check your browser's developer console for JavaScript errors. Also ensure REST API endpoints are not blocked by a security plugin. See the [Troubleshooting](../advanced/troubleshooting.md) guide.

### A location marker is in the wrong place

Enter the correct coordinates manually in the **Geolocation** field on the location edit screen (format: `lat, lng`). This overrides the auto-geocoded position. You can find coordinates by right-clicking on Google Maps.

### How do I get support?

See the [Getting Help](getting-help.md) guide for support channels and resources.
