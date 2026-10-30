---
title: "Introduction to CP Locations"
slug: introduction-cp-locations
section: getting-started-cp-locations
order: 3
excerpt: "CP Locations is a WordPress plugin that helps multi-campus churches manage and display their locations with interactive maps, service times, and location-specific content."
status: publish
---

# Introduction to CP Locations

CP Locations is a WordPress plugin that helps multi-campus churches manage and display their locations with interactive maps, service times, and location-specific content.

## What CP Locations Does

CP Locations provides everything you need to showcase your church's campuses:

- **Interactive Location Finder** — A map and list view powered by Leaflet and React that visitors can use to browse locations, search by zip code, or use their current location to find the nearest campus
- **Service Time Management** — Add multiple service times per location with day, time, and special service designations
- **Location Details** — Store and display address, phone, email, pastor, and custom subtitle for each campus
- **Automatic Geocoding** — Addresses are automatically converted to map coordinates via MapBox
- **Shortcodes** — Embed the location finder or display individual location details anywhere on your site

## Key Concepts

- **Location** — A custom post type representing a physical campus. Each location stores an address, contact information, service times, and a featured image.
- **Location Finder** — The interactive frontend display (map + list) that visitors use to browse and search locations. Powered by a React app with Leaflet maps.
- **Geocoding** — The automatic conversion of a street address into map coordinates (latitude and longitude). CP Locations uses MapBox for geocoding.
- **Service Times** — Repeatable entries on each location for day, time, and description. Grouped by day for display (e.g., "Sundays at 9:00am and 11:00am").

  ## How It Works

Each location is a custom post type with fields for address, contact information, and service times. The plugin automatically geocodes addresses using MapBox and serves location data through a REST API that powers the frontend map and list display.

Visitors interact with the location finder through the `[cp-locations]` shortcode, which renders a responsive map on desktop (with a sidebar list) and a mobile-optimized view with a sliding panel.

## Integration with Other Plugins

CP Locations works with:

- **The Events Calendar** — Add a location filter to the calendar filter bar

## Next Steps

Continue to [Installation](installation.md) to get started with CP Locations.
