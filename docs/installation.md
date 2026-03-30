# Installation Guide

## Requirements

| Requirement | Minimum |
|-------------|---------|
| WordPress   | 5.8     |
| PHP         | 7.4     |
| MySQL       | 5.7 / MariaDB 10.3 |

## Install Steps

### Option A — Upload via WordPress Admin

1. Download the plugin `.zip` file.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Choose the `.zip` file and click **Install Now**.
4. Click **Activate Plugin**.

### Option B — Manual Upload

1. Extract the `.zip` file.
2. Upload the `b2b-visitor-intelligence/` folder to `/wp-content/plugins/`.
3. Go to **Plugins** in the WordPress admin and activate the plugin.

### Option C — Git Clone (development)

```bash
cd /path/to/wp-content/plugins/
git clone https://github.com/Anupkankale/b2b.git b2b-visitor-intelligence
```

Then activate through the WordPress admin.

## On Activation

When activated the plugin automatically:

- Creates three database tables:
  - `{prefix}bvip_visits` — one row per page view
  - `{prefix}bvip_sessions` — one row per visitor session
  - `{prefix}bvip_clicks` — one row per tracked click
- Schedules an hourly WP cron job (`bvip_check_alerts`) for hot lead detection.

## First-Run Checklist

- [ ] Activate the plugin.
- [ ] Go to **B2B Visitors → Settings** and enter your **IPinfo.io API key**.
  - Free tier: 50,000 requests/month at [ipinfo.io](https://ipinfo.io).
- [ ] Set the **Sales Alert Email** (defaults to the WordPress admin email).
- [ ] Set the **Alert Threshold** (default: 3 visits within 7 days triggers an alert).
- [ ] Visit your own site from an office/corporate network connection.
- [ ] Return to **B2B Visitors → Dashboard** — your company should appear within seconds.

## On Deactivation

Deactivating the plugin removes the scheduled cron job. All collected data is retained.

## On Deletion

Deleting the plugin via the WordPress admin runs `uninstall.php`, which:

- Drops `bvip_visits`, `bvip_sessions`, and `bvip_clicks` tables.
- Deletes all plugin options from `wp_options`.
- Clears all plugin transients.

> **Warning:** Deletion is permanent. Export your data before uninstalling if you need it.
