---
title: "Troubleshooting"
slug: troubleshooting-cp-locations
section: advanced-cp-locations
order: 2
excerpt: "Common issues with CP Locations and their solutions."
status: publish
---

# Troubleshooting

This guide covers common issues with CP Locations and their solutions.

## Map Not Displaying

### Blank or Missing Map

1. **Check MapBox API Key** — Navigate to **Locations → Settings → Main** and verify your MapBox API key is entered correctly
2. **Test the key** — Log in to [account.mapbox.com](https://account.mapbox.com) and confirm your token is active and has the correct scopes
3. **Check for JavaScript errors** — Open your browser's developer console (F12) and look for errors related to MapBox or Leaflet
4. **Caching conflicts** — Clear your site cache and any CDN cache. Ensure REST API endpoints (`/wp-json/cp-locations/v1/`) are excluded from caching

### Map Loads but No Markers

1. **Verify locations exist** — Check that you have published locations in **Locations**
2. **Check addresses** — Ensure each location has an address entered. Locations without geocoded coordinates will not appear on the map.
3. **Inspect API response** — Visit `https://yoursite.com/wp-json/cp-locations/v1/locations` in your browser. Each location should have a `geodata` object with `center` coordinates.

## Geocoding Issues

### Address Not Converting to Coordinates

1. **Verify MapBox API key** — The key is required for geocoding
2. **Check address format** — Use a standard, complete address (street, city, state, zip)
3. **Manual override** — Enter coordinates manually in the **Geolocation** field (format: `lat, lng`, e.g., `40.7128, -74.0060`)
4. **Re-save the location** — Geocoding runs on save only when no coordinates are stored. Clear the **Geolocation** field, then click **Update** to trigger a new geocoding request.

### Marker in Wrong Position

1. **Check the address** — Verify the address is correct and specific enough for accurate geocoding
2. **Use manual coordinates** — Enter the exact latitude and longitude in the **Geolocation** field to override the auto-geocoded position
3. **Find coordinates** — Use Google Maps to find the correct coordinates: right-click the location on the map and copy the lat/lng values

## Zip Code Search Not Working

1. **Verify MapBox API key** — Zip code lookup requires a valid MapBox key
2. **US zip codes only** — The search field accepts 5-digit US zip codes. It triggers automatically when exactly 5 digits are entered.
3. **Check REST API** — Test the endpoint directly: `https://yoursite.com/wp-json/cp-locations/v1/locations/postcode/90210`
4. **100-mile radius** — Only locations within 100 miles of the searched zip code are shown. If all locations are farther, a "No results found" message appears.

## Display Issues

### Wrong Location Order

Locations display in menu order. Edit each location and set the **Order** value in the **Page Attributes** panel. Lower numbers appear first.

### Missing Thumbnails

Set a **Featured Image** for each location. The thumbnail appears in the finder list, map popups, and card views.

### Mobile Display Problems

1. **Responsive breakpoint** — The mobile view activates below 1025px. Test at various screen sizes.
2. **iOS Safari** — The plugin includes iOS Safari-specific adjustments. If the layout appears broken, clear the Safari cache.
3. **Touch scrolling** — The mobile view includes gesture handling to prevent conflicts between the map and the sliding panel.

## REST API Issues

If the location finder shows errors:

1. **Check REST API access** — Visit `https://yoursite.com/wp-json/cp-locations/v1/locations` in your browser
2. **Security plugins** — Some security plugins block REST API access. Whitelist the `cp-locations/v1` namespace.
3. **Caching** — Exclude `/wp-json/` from your caching plugin's cached URLs

For additional help, see the [Getting Help](../support/getting-help.md) guide.
