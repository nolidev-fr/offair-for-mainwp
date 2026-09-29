=== Offair for MainWP ===
Contributors: nolidev
Tags: mainwp, downtime, error page, database, monitoring
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See the error pages and the outages of all your sites in your MainWP Dashboard, reported by Offair from inside each site.

== Description ==

[Offair](https://wordpress.org/plugins/offair/) replaces the screens WordPress shows when it breaks (database connection error, fatal PHP error, update notice) with branded pages, and records each time a visitor sees one. Offair for MainWP brings this into your MainWP Dashboard.

An uptime monitor checks your sites from the outside, every few minutes. Offair sees what happens inside: a database that drops for two minutes, a fatal error on one page, an update that hangs. This extension gathers what Offair saw on every site.

= What you get =

* **An Offair column** in Sites, Manage Sites: a green dot when the three pages are in place, orange when a page needs attention, grey when Offair is missing or too old, with the date of the last outage.
* **A widget on the overview**: the outages of the last 30 days on all your sites, and the sites that need attention.
* **A widget on the page of each site**: the state of the three pages, the email alert, the problems found by Site Health and the recent outages, with a link to the Offair settings of the site.
* **An Offair page** under Add-ons: every outage of the last 90 days on every site, filtered by site and by page.

= How it works =

The data travels in the synchronization MainWP already runs, automatically or with the Sync button. There is no new connection and no extra request, and the authentication is the one of MainWP. The data is as fresh as your last synchronization. For an alert as soon as a database goes down, turn on the email alert of Offair on the site.

The sites send codes, not sentences, so everything is shown in the language of your dashboard. They never send the alert address, the texts of the pages or the logo, and Offair records nothing about visitors.

= Requirements =

* MainWP Dashboard on the dashboard site.
* On each site: MainWP Child, and Offair 1.3.0 or later. Nothing else to install on the sites.

A site without Offair, or with an older version, is shown in grey with what to do.

== Installation ==

1. Install and activate Offair for MainWP on your MainWP Dashboard site.
2. Install Offair 1.3.0 or later on your sites. MainWP can do it for all of them from Plugins, Install.
3. Synchronize your sites.
4. To see the Offair column, open Sites, Manage Sites, click the Page Settings button (the gear) and tick Offair in the columns.

== Frequently Asked Questions ==

= Why is a site grey? =

The dot tells why: Offair is not installed, inactive, or older than 1.3.0, or the site has not been synchronized since the extension was installed. Update or install Offair on the site, then synchronize.

= Why is a site orange? =

A page needs to be written again, is missing, or a file with the same name was not added by Offair, or Site Health found a problem, such as a PHP configuration that prevents the PHP error page. The widget on the page of the site lists what is wrong. Open the Offair settings of the site to fix it.

= Does it replace an uptime monitor? =

No. Offair only sees an outage when a visitor, or a bot, runs into one of its pages. It cannot see a site that is down while nobody visits it, or a server that does not answer at all. The two complement each other.

= Does it send data anywhere? =

No. The extension only reads what your sites send to your own MainWP Dashboard during the synchronization. It stores it with the other data of each site, and removes it when uninstalled.

= Is it made by MainWP? =

No. Offair for MainWP is made by Nolidev, the author of Offair. MainWP is a trademark of its owners.

== Changelog ==

= 0.1.0 =
* First release: Offair column in the list of the sites, widgets on the overview and on the page of each site, page of all the outages.
