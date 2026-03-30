# B2B Visitor Intelligence — Documentation

Identifies visiting companies, tracks their journey, and sends hot lead alerts.
All data is stored privately in your own WordPress database — no third-party SaaS required.

## Table of Contents

| Document | Description |
|----------|-------------|
| [Installation](installation.md) | Requirements, install steps, first-run checklist |
| [Configuration](configuration.md) | Settings reference — API key, alerts, data retention |
| [Hooks Reference](hooks.md) | WordPress actions and filters exposed by the plugin |
| [Changelog](changelog.md) | Version history and release notes |

## How It Works

```
Visitor lands on page
        │
        ▼
tracker.js  ──POST──►  /b2b-analytics/v1/track
                               │
                        IP Lookup (IPinfo.io)
                               │
                        Insert into bvip_visits
                        Upsert into bvip_sessions
                               │
                        Return visit_id + session_hash
                               │
        ┌──────────────────────┴───────────────────────┐
        │                                               │
click-tracker.js                                  tracker.js
POST /click  (on user click)             sendBeacon /duration  (on page hide)
        │                                               │
  bvip_clicks                               bvip_visits.time_on_page
                                           bvip_sessions.total_duration
```

Every hour a WP cron job checks for "hot leads" (companies that visited N+ times in 7 days)
and sends an email alert to the configured address.

## Plugin Files

```
b2b-visitor-intelligence/
├── b2b-visitor-intelligence.php   — bootstrap: constants, includes, lifecycle hooks
├── uninstall.php                  — drops tables & options on deletion
├── admin/
│   ├── class-dashboard.php        — admin menu, settings, AJAX handlers
│   └── views/                     — PHP view templates
│       ├── dashboard.php
│       ├── settings.php
│       ├── clicks.php
│       ├── countries.php
│       └── duration.php
├── assets/
│   ├── css/dashboard.css          — admin styles
│   └── js/
│       ├── tracker.js             — page-visit tracking + duration reporting
│       ├── click-tracker.js       — click event tracking
│       └── dashboard.js           — Chart.js charts + realtime counter
├── docs/                          — this folder
├── includes/
│   ├── class-activator.php        — plugin activation logic
│   ├── class-deactivator.php      — plugin deactivation logic
│   ├── class-database.php         — table creation via dbDelta
│   ├── class-ip-lookup.php        — IPinfo.io reverse IP lookup with caching
│   ├── class-tracker.php          — REST endpoint: /track
│   ├── class-click-tracker.php    — REST endpoint: /click
│   ├── class-session-tracker.php  — REST endpoint: /duration
│   ├── class-analytics.php        — query layer with transient caching
│   └── class-alerts.php           — hot lead detection + WP cron + email
└── languages/
    └── b2b-visitor-intelligence.pot
```
