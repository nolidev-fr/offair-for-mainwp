# Offair for MainWP

Shows in your MainWP Dashboard the outages your visitors ran into on all your sites, recorded by [Offair](https://wordpress.org/plugins/offair/).

MainWP checks your sites from the outside, every few minutes at best, and only the home page. Offair records what visitors actually saw: a database that drops for two minutes between two checks, a fatal error on one page, an update stuck in maintenance. This extension gathers these outages from all your sites.

## Features

- A widget on the MainWP overview: the outages of the last 30 days on all your sites.
- A widget on the page of each site: its recent outages, with a link to its Offair settings.
- An Offair page under Add-ons: every outage of the last 90 days, filtered by site and by page, and the sites that do not report their outages yet.

The state of the Offair pages of each site is not repeated here: Offair reports it in Site Health, which MainWP already monitors.

## Requirements

- MainWP Dashboard on the dashboard site.
- MainWP Child and Offair 1.3.0 or later on each site.

## How it works

The extension adds `'offair_sync' => 'yes'` to the extra data of each MainWP synchronization (filter `mainwp_sync_others_data`). Offair 1.3.0 and later answers under the `offair` key with its version and the outages of the last 90 days, 50 at most:

| Key | Content |
| --- | --- |
| `format` | Version of the format, `1` |
| `version` | Version of Offair |
| `incidents` | `screen` (`db`, `maintenance` or `php`), `start` and `end` (Unix times), `count` (minutes with a page shown), `status` (HTTP status) |

The answer is received in `mainwp_site_synced`, cleaned and stored per site in the `offair_mainwp` site option (`mainwp_updatewebsiteoptions`). When a site sends nothing, the plugin list of the same synchronization tells whether Offair is missing, inactive or older than 1.3.0.

No new connection and no extra request: the data travels in the synchronization MainWP already runs and authenticates. Uninstalling the extension removes the stored data.

## Development

```
composer install
vendor/bin/phpcs
```

Translations are delivered by translate.wordpress.org. The package ships only `languages/offair-for-mainwp.pot` (`wp i18n make-pot . languages/offair-for-mainwp.pot`). The French `.po` in the repository is kept to be imported there, and is left out of the package by `.distignore`.

A tag matching the version (`0.2.0`) deploys to WordPress.org through GitHub Actions, once the `SVN_USERNAME` and `SVN_PASSWORD` secrets are set. The tag, the plugin header, the `OFFAIR_MAINWP_VERSION` constant and the `Stable tag` of the readme must match.

## License

GPLv2 or later.
