---
title: "Location Finder"
slug: location-finder-cp-locations
section: options-and-features-cp-locations
order: 1
excerpt: "The interactive map and list display that helps visitors browse and search your church locations."
status: publish
---

# Location Finder

The Location Finder is an interactive map and list display that helps visitors browse and search your church locations. It is the primary frontend feature of CP Locations.

## Adding the Finder to a Page

Add the `[cp-locations]` shortcode to any page or post. The shortcode renders a full-width, responsive location finder with no additional parameters needed.

## Desktop View (1025px and above)

On desktop, the finder displays a split-panel layout:

- **Left sidebar** — A scrollable list of all locations with thumbnails, titles, subtitles, and distance (when searching)
- **Right panel** — An interactive Leaflet map with markers for each location

### Map Interaction

- **Hover** over a list item to highlight its marker on the map
- **Hover** over a map marker to highlight the corresponding list item
- **Click** a list item or marker to navigate to that location's detail page
- **Cluster markers** automatically group nearby locations and expand when clicked
- The map auto-zooms to fit all visible locations

### Card View

Click **Show as Cards** above the list to switch from the sidebar list to a card grid view. Each card shows the location's thumbnail, title, pastor, address, service times, and a "Learn More" link.

## Mobile View (below 1025px)

On mobile devices, the finder displays a full-screen map with a sliding bottom panel:

- **Collapsed panel** — Shows a location count header; the map is fully visible
- **Expanded panel** — Slides up to reveal the full location list
- **Tap a marker** — Collapses the panel and shows the selected location's card
- **Toggle button** — A "Map View" / "List View" button at the bottom switches between map and list modes

Touch gesture handling prevents accidental map scrolling while interacting with the panel.

## Zip Code Search

Visitors can search by zip code to find nearby locations:

1. Enter a 5-digit US zip code in the search field
2. The finder geocodes the zip code via MapBox and calculates distances
3. Locations within 100 miles are shown, sorted by distance (nearest first)
4. Each location displays its distance (e.g., "12.3 mi")
5. If no locations are within 100 miles, a "No results found" message appears

## Use My Location

Click the location icon button next to the search field to use the browser's geolocation:

1. The browser prompts for location permission
2. If granted, locations are sorted by distance from the visitor's current position
3. A person icon marker appears on the map at the visitor's position
4. If denied, a message indicates that location sharing is disabled

## Map Tiles

The map uses MapBox street tiles. A valid MapBox API key must be configured in **Locations → Settings → Main** for the map to display.

## Customization

### Template Overrides

The location finder uses PHP templates for the map popup and card displays. Override these by copying the template files to your theme:

1. Create a `cp-locations/` directory in your active theme
2. Copy template files from the plugin's `templates/` directory
3. Modify the copies in your theme — they take priority over the plugin originals

Available templates:

| Template | Path | Description |
| --- | --- | --- |
| Card | `templates/card.php` | Location card structure |
| List Item | `templates/map/list-item.php` | Sidebar list item |
| Map Popup | `templates/map/popup.php` | Marker popup with details |

### Display Order

Locations appear in the order set by the **Order** field in each location's Page Attributes panel. Lower numbers appear first.

For more details on embedding location data in other contexts, see the [Shortcodes](shortcodes.md) guide.
