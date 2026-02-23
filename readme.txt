=== B2B Visitor Intelligence ===
Contributors: yourname
Tags: analytics, b2b, visitor tracking, company identification, sales intelligence
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Identify which companies visit your WordPress website. Track their journey, get hot lead alerts, and view real-time stats — all stored privately in your own database.

== Description ==

B2B Visitor Intelligence shows you exactly which companies are browsing your website, which pages they read, how many times they returned, and where they are located — right inside your WordPress dashboard.

Unlike expensive SaaS tools like Leadfeeder ($139/month) or Albacross ($149/month), this plugin stores everything in your own WordPress database. Your data never leaves your server.

**Key Features:**

* Identify visiting companies from IP addresses using IPinfo.io
* Track which pages each company viewed and how many times
* See city and region level geographic data
* Real-time visitor counter in your admin dashboard
* Hot lead email alerts when a company crosses your visit threshold
* Traffic source breakdown — direct, search, social, referral
* Device breakdown — mobile, tablet, desktop
* Bot filtering so your stats stay accurate
* Zero cookies — privacy-first approach
* 100% data stored in your own WordPress database

**Perfect For:**

* SaaS companies wanting to see which businesses evaluate their product
* IT services companies identifying enterprise prospects
* Agencies tracking which companies research their services
* Any B2B business that wants sales intelligence from website traffic

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/
2. Activate the plugin through the Plugins menu in WordPress
3. Go to B2B Visitors > Settings in your admin menu
4. Enter your free IPinfo.io API key (get it at ipinfo.io — 50,000 requests/month free)
5. Enter the email address for hot lead alerts
6. Visit your website from a corporate internet connection to test

== Frequently Asked Questions ==

= Why do some visitors show Unknown company? =
Residential internet connections show the ISP name, not a company. Corporate office internet connections and business VPNs will correctly show the company name.

= Is this GDPR compliant? =
The plugin does not store full IP addresses or any personally identifiable information. It only stores anonymised session hashes and company-level data. Always consult a legal professional for your specific situation.

= How many API requests does it use? =
Each unique IP is cached for 24 hours, so repeat visitors from the same IP only use 1 API call per day. IPinfo.io free tier provides 50,000 requests/month.

== Changelog ==

= 1.0.0 =
* Initial release
