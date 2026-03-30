# Hooks Reference

All actions and filters exposed by B2B Visitor Intelligence.

---

## Actions

### `bvip_check_alerts`

Fired by the hourly WP cron job. Triggers hot lead detection and email alerts.

```php
do_action( 'bvip_check_alerts' );
```

You can trigger it manually for testing:

```php
do_action( 'bvip_check_alerts' );
```

Or run it via WP-CLI:

```bash
wp cron event run bvip_check_alerts
```

---

## REST API Endpoints

These are registered at `plugins_loaded` and are always public (`permission_callback: __return_true`).
All input is sanitized server-side. Nonces are sent by the frontend via `X-WP-Nonce` header.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/wp-json/b2b-analytics/v1/track`    | Register a page view |
| `POST` | `/wp-json/b2b-analytics/v1/click`    | Record a click event |
| `POST` | `/wp-json/b2b-analytics/v1/duration` | Update time on page  |

### `/track` Request Body

```json
{
  "post_id":      123,
  "referrer":     "https://google.com/",
  "screen_width": 1440,
  "page_url":     "https://example.com/pricing/",
  "page_title":   "Pricing"
}
```

### `/track` Response

```json
{
  "status":       "ok",
  "visit_id":     456,
  "session_hash": "abc123..."
}
```

`visit_id` and `session_hash` are required by the `/duration` and `/click` endpoints.

---

## Admin AJAX Actions

Both require a logged-in user with `manage_options` capability and a valid `bvip_nonce` nonce.

| Action | Description |
|--------|-------------|
| `bvip_realtime`    | Returns current active visitor count (last 5 minutes) |
| `bvip_flush_cache` | Clears all plugin transients                         |

### Example

```javascript
jQuery.post(ajaxurl, {
    action: 'bvip_realtime',
    nonce:  bvipChartData.nonce
}, function( response ) {
    console.log( response.data.count ); // number of active visitors
});
```

---

## Extending the Plugin

The plugin does not yet expose custom filters. Future versions will add:

- `bvip_should_track` — filter to override the tracking decision per request.
- `bvip_ip_lookup_result` — filter the raw IPinfo response before it is stored.
- `bvip_alert_email_subject` / `bvip_alert_email_body` — customise alert emails.
- `bvip_hot_lead_threshold` — programmatically override the alert threshold.

To request a specific hook, open an issue at
[github.com/Anupkankale/b2b](https://github.com/Anupkankale/b2b/issues).
