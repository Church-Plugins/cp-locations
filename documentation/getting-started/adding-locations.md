---
title: "Adding Locations"
slug: adding-locations-cp-locations
section: getting-started-cp-locations
order: 1
excerpt: "Create and configure church locations with address, contact info, and service times."
status: publish
---

# Adding Locations

This guide covers creating and configuring church locations in CP Locations.

## Creating a Location

1. Navigate to **Locations → Add New**
2. Enter the location name (e.g., "Downtown Campus")
3. Add a description in the editor — this appears on the location's detail page
4. Set a **Featured Image** — this displays as the location thumbnail in the finder and map popup

## Configuring Location Details

Below the editor, the **Location Details** metabox contains the following fields:

### Basic Information

- **Subtitle** — A short description shown under the location name in the finder and map popups (e.g., "Main Campus" or "Meeting at Lincoln High School")
- **Pastor** — The name of the campus pastor or site leader

### Contact Information

- **Address** — The physical address of the location. When you save the location, this address is automatically geocoded (converted to map coordinates) via MapBox.
- **Geolocation** — A manual latitude/longitude override (e.g., `40.7128, -74.0060`). Use this if the automatic geocoding returns an incorrect position. Leave blank to use the auto-geocoded coordinates.
- **Phone Number** — The location's phone number
- **Email** — The location's email address

### Service Times

Add one or more service times for the location:

1. Click **Add Another Time** to add a service time row
2. Select the **Day of Week** (Sunday through Saturday)
3. Enter the **Time** (e.g., 9:00 AM)
4. Optionally enter a **Time Description** — if provided, this free-text value replaces the time display (useful for descriptions like "Doors open at 9:30am")
5. Check **Special Service** if this time should be hidden from the standard formatted times output (e.g., holiday services)
6. Repeat for additional service times

Service times are grouped by day in the display. The day name is pluralized when a day has two or more services. For example, two Sunday services at 9:00 AM and 11:00 AM display as "Sundays at 9:00am and 11:00am", while a single Wednesday service displays as "Wednesday at 7:00pm".

## Setting Display Order

Locations display in the finder in **menu order**. To set the order:

1. Edit a location
2. In the **Page Attributes** panel (right sidebar), set the **Order** value
3. Lower numbers appear first

## Publishing

Click **Publish** to make the location live. The address is geocoded on save, and the location immediately appears in the `[cp-locations]` finder.

## Editing Locations

Navigate to **Locations** to see all locations. Click any location name to edit it. To trigger a new geocoding request after changing the address, clear the **Geolocation** field and save — geocoding only runs when no coordinates are stored.

## Next Steps

- Learn how the finder displays your locations in the [Location Finder](../features/location-finder.md) guide
- Configure service time displays in [Service Times](../features/service-times.md)
- Review all plugin settings in [Settings](../features/settings.md)
