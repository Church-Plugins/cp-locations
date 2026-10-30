---
title: "Settings"
slug: settings-cp-locations
section: options-and-features-cp-locations
order: 3
excerpt: "Configure CP Locations settings including MapBox API key, content labels, and license."
status: publish
---

# Settings

This guide covers all CP Locations settings. Access settings at **Locations → Settings** in your WordPress dashboard.

## Main Tab

The Main tab contains core plugin configuration.

### MapBox API Key

Enter your MapBox access token for map tiles and address geocoding. Get a free token at [account.mapbox.com](https://account.mapbox.com).

Without a valid API key, the map will not display and addresses will not be geocoded.

## Locations Tab

The Locations tab controls how locations appear in the admin and on your site.

### Content Labels

- **Singular Label** — The name for a single location (default: "Location"). Changes the admin menu label and UI references.
- **Plural Label** — The name for the collection (default: "Locations"). Changes the admin menu name and UI references.

## Shortcodes Tab

The Shortcodes tab displays usage reference for both available shortcodes. This tab is informational only — there are no settings to configure. See [Shortcodes](shortcodes.md) for full documentation.

## License Tab

Manage your CP Locations license:

- **License Key** — Enter your license key from your ChurchPlugins account
- **Activate/Deactivate** — Toggle license activation
- **License Status** — View current status (Active, Inactive, Expired)

## Saving Settings

After changing any settings:

1. Click **Save Changes** at the bottom of the tab
2. Rewrite rules are flushed automatically when settings are saved

If you change the content labels, the admin menu name updates immediately.

## Settings Filters

Developers can override any setting programmatically:

```
add_filter( 'cploc_settings_get', function( $value, $key, $group ) {
    if ( $key === 'mapbox_api_key' ) {
        return 'your-custom-key';
    }
    return $value;
}, 10, 3 );
```

For more developer customization options, see the [Developer Guide](../advanced/developer-guide.md).

## Troubleshooting

### Settings Not Saving

1. **Check user permissions** — Ensure you are logged in as an administrator
2. **Clear caching** — If using a caching plugin, clear the cache after saving settings
3. **Check for plugin conflicts** — Temporarily deactivate other plugins to rule out conflicts

### Map Not Displaying After Entering API Key

1. **Verify the key** — Log in to [account.mapbox.com](https://account.mapbox.com) and confirm your token is active
2. **Clear cache** — Clear your site cache and browser cache
3. **Check the frontend** — Add the `[cp-locations]` shortcode to a page and verify the map loads

For more troubleshooting help, see the [Troubleshooting](../advanced/troubleshooting.md) guide.
