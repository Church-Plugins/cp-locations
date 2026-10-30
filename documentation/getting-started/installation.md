---
title: "Installation"
slug: installation-cp-locations
section: getting-started-cp-locations
order: 2
excerpt: "Install and activate CP Locations on your WordPress site with MapBox configuration."
status: publish
---

# Installation

This guide walks you through installing and activating CP Locations on your WordPress site.

## Requirements

- A MapBox account (free tier available) for map tiles and geocoding

## Installing the Plugin

### From ChurchPlugins.com

1. Download the plugin ZIP file from your [ChurchPlugins account](https://churchplugins.com/account)
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins → Add New → Upload Plugin**
4. Click **Choose File** and select the downloaded ZIP
5. Click **Install Now**
6. Click **Activate Plugin**

### Via FTP

1. Download and extract the plugin ZIP file
2. Upload the `cp-locations` folder to `/wp-content/plugins/`
3. Navigate to **Plugins** in WordPress admin
4. Find **CP Locations** and click **Activate**

## Activating Your License

1. Navigate to **Locations → Settings → License** tab
2. Enter your license key from your ChurchPlugins account
3. Click **Activate**
4. Verify the license status shows **Active**

## Configuring MapBox

CP Locations uses MapBox for map tiles and address geocoding. You need a MapBox API key:

1. Create a free account at [account.mapbox.com](https://account.mapbox.com)
2. Copy your default public access token
3. Navigate to **Locations → Settings → Main** tab
4. Paste the token into the **MapBox API Key** field
5. Click **Save Changes**

The free MapBox tier includes 50,000 map loads and 100,000 geocoding requests per month, which is sufficient for most churches.

## Verifying Installation

After activation and configuration:

1. Navigate to **Locations** in the admin sidebar — you should see the location list
2. Create a test location with an address (see [Adding Locations](adding-locations.md))
3. Add the `[cp-locations]` shortcode to a page and preview it
4. Confirm the map displays with your test location

## Next Steps

Continue to [Adding Locations](adding-locations.md) to create and configure your first location.
