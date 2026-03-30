# Configuration Reference

All settings are at **B2B Visitors → Settings** in the WordPress admin.

## API Configuration

### IPinfo.io API Key

| Option name | `bvip_ipinfo_key` |
|-------------|-------------------|
| Type        | `string`          |
| Default     | *(empty)*         |

The plugin uses [IPinfo.io](https://ipinfo.io) to resolve visitor IP addresses into
company names, cities, and countries. Without this key no company data is collected —
only raw visit counts, device types, and traffic sources are recorded.

**Free tier:** 50,000 lookups/month. Lookup results are cached for 24 hours per IP,
so a company visiting multiple pages only counts as one lookup per day.

## Hot Lead Alerts

### Sales Alert Email

| Option name | `bvip_alert_email`                    |
|-------------|---------------------------------------|
| Type        | `string` (email address)             |
| Default     | WordPress admin email (`admin_email`) |

The email address that receives hot lead notifications. Only one address is supported.

### Alert Threshold

| Option name | `bvip_alert_threshold` |
|-------------|------------------------|
| Type        | `int`                  |
| Default     | `3`                    |
| Range       | 1 – 50                 |

A company is considered a "hot lead" when it visits **at least this many times within
the last 7 days**. An alert is sent at most once per company per day (enforced by a
24-hour transient).

The alert email includes:
- Company name and location
- Total visit count (last 7 days)
- Top 5 pages visited with view counts
- Direct link to the dashboard

## Data Management

### Keep Data For

| Option name | `bvip_data_retention` |
|-------------|------------------------|
| Type        | `int` (days)           |
| Default     | `365`                  |
| Options     | 90, 180, 365, 0 (forever) |

Older visit, session, and click records will be pruned automatically.

> **Note:** Automatic pruning is not yet implemented in v1.0.0.
> It will be enforced via a daily WP cron job in a future release.

### Flush Analytics Cache

Clears all cached query results stored as WordPress transients (prefixed `bvip_`).
Use this after importing data or if the dashboard shows stale numbers.

Cache TTL: **15 minutes** for summary stats. IP lookups are cached for **24 hours**.

## Database Tables

| Table | Description |
|-------|-------------|
| `{prefix}bvip_visits`   | One row per page view. Stores session hash, company, IP geo, device, traffic source, page URL, and time on page. |
| `{prefix}bvip_sessions` | One row per visitor session. Stores pages viewed and total duration. |
| `{prefix}bvip_clicks`   | One row per tracked click (links, buttons, submit inputs). Stores element text, target URL, and XY position. |
