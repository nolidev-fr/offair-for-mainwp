=== Offair for MainWP ===
Contributors: nolidev
Tags: mainwp, downtime, error page, database, monitoring
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See in your MainWP Dashboard the outages your visitors ran into on all your sites, recorded by Offair from inside each site.

== Description ==

[Offair](https://wordpress.org/plugins/offair/) replaces the screens WordPress shows when it breaks (database connection error, fatal PHP error, update notice) with branded pages, and records each time a visitor sees one. Offair for MainWP brings this into your MainWP Dashboard.

MainWP checks your sites from the outside, every few minutes at best, and only the home page. Offair records what visitors actually saw: a database that drops for two minutes between two checks, a fatal error on the product page while the home page works, an update stuck in maintenance. This extension gathers these outages from all your sites.

= What you get =

* **A widget on the overview**: the outages of the last 30 days on all your sites.
* **A widget on the page of each site**: its recent outages, with a link to its Offair settings.
* **An Offair page** under Add-ons: every outage of the last 90 days on every site, filtered by site and by page, the downtime of each site over 30 days, and the sites that do not report their outages yet.

Each outage shows its page in color: database error, PHP error or maintenance. The maintenance pages shown less than a minute, as during most updates, are counted on one line instead of filling the lists. The Offair page can list them too.

The state of the Offair pages of each site (in place, missing, needing regeneration) is not repeated here: Offair reports it in Site Health, which MainWP already monitors.

= How it works =

The outages travel in the synchronization MainWP already runs, automatically or with the Sync button. There is no new connection and no extra request, and the authentication is the one of MainWP. The list is as fresh as your last synchronization. For an alert as soon as a database goes down, turn on the email alert of Offair on the site.

Each site sends only the version of Offair and its outages: the page, the start, the end and the HTTP status. Offair records nothing about visitors.

= Requirements =

* MainWP Dashboard on the dashboard site.
* On each site: MainWP Child, and Offair 1.3.0 or later. Nothing else to install on the sites.

== Installation ==

1. Install and activate Offair for MainWP on your MainWP Dashboard site.
2. Install Offair 1.3.0 or later on your sites. MainWP can do it for all of them from Plugins, Install.
3. Synchronize your sites.

== Frequently Asked Questions ==

= A site does not report its outages. Why? =

The Offair page tells why: Offair is not installed, inactive, or older than 1.3.0, or the site has not been synchronized since the extension was installed. Install or update Offair on the site, then synchronize.

= Does it replace an uptime monitor? =

No. Offair only sees an outage when a visitor, or a bot, runs into one of its pages. It cannot see a site that is down while nobody visits it, or a server that does not answer at all. Keep the uptime monitoring of MainWP on: the two complement each other.

= Does it send data anywhere? =

No. The extension only reads what your sites send to your own MainWP Dashboard during the synchronization. It stores it with the other data of each site, and removes it when uninstalled.

= Is it made by MainWP? =

No. Offair for MainWP is made by Nolidev, the author of Offair. MainWP is a trademark of its owners.

== Screenshots ==

1. The Offair page under Add-ons: every outage of the last 90 days on every site, the downtime of each site over 30 days, and whether each site reports its outages.
2. The widget on the MainWP overview: the outages of the last 30 days on all your sites.
3. The widget on the page of a site: its recent outages and a link to its Offair settings.

== Changelog ==

= 0.2.0 =
* New: each outage shows its page in color, database error, PHP error or maintenance.
* New: the maintenance pages shown less than a minute, as during most updates, are counted on one line in the widgets. The Offair page hides them unless you ask for them.
* New: downtime of each site over the last 30 days, on the Offair page.
* The overview widget lists the 8 most recent outages and starts taller, so its notes stay visible.

= 0.1.0 =
* First release: outages of all the sites on the overview, on the page of each site and on a page of their own.

== Upgrade Notice ==

= 0.2.0 =
Outages in color by page, short maintenance pages during updates grouped on one line, and the downtime of each site over 30 days.
