---
title: "Service Times"
slug: service-times-cp-locations
section: options-and-features-cp-locations
order: 2
excerpt: "Manage multiple service times per location with day, time, and special service designations."
status: publish
---

# Service Times

CP Locations includes a flexible service time system that lets you manage multiple services per location with day, time, and special service designations.

## Adding Service Times

1. Edit a location in **Locations**
2. Scroll to the **Service Times** section in the Location Details metabox
3. Click **Add Another Time** to add a service time
4. Fill in the fields:
   - **Day of Week** — Select from Sunday through Saturday
   - **Time** — Enter the service time (e.g., 9:00 AM)
   - **Time Description** — Optional free-text override that replaces the time display
   - **Special Service** — Check this box to exclude this time from the standard display
5. Click **Update** to save

## How Times Display

Service times are automatically grouped by day and formatted for display. The day name is pluralized only when a day has two or more services. For example, if a location has:

- Sunday at 9:00 AM
- Sunday at 11:00 AM
- Sunday at 1:00 PM
- Wednesday at 7:00 PM

The formatted output is:

> Sundays at 9:00am, 11:00am, and 1:00pm
> Wednesday at 7:00pm

This formatted string appears in the location finder, map popups, and when using the `[cp-location-data field='service_times']` shortcode.

## Time Description Override

The **Time Description** field lets you replace the standard time format with custom text. This is useful for:

- Services with variable start times: "Doors open at 9:30am, service at 10:00am"
- Informal descriptions: "After the morning service"
- Date ranges: "Every other Tuesday"

When a time description is set, it replaces the formatted time value for that entry.

## Special Services

Check the **Special Service** box to exclude a service time from the standard formatted output. Use this for:

- Holiday services (Christmas Eve, Easter)
- Temporary or seasonal services
- Services you want to track but not display in the default listing

Special services are stored in the database but excluded from both the `get_formatted_times()` output and the REST API `times` field, which uses the same formatted string.

## Displaying Service Times

Service times appear automatically in:

- The location finder map popups
- The location finder card view

You can also display them anywhere using the shortcode:

```
[cp-location-data location=23 field='service_times']
```

See the [Shortcodes](shortcodes.md) guide for more details.

## Customizing Time Format

Developers can customize the formatted times output using the `cploc_format_times` filter. See the [Developer Guide](../advanced/developer-guide.md) for details.
