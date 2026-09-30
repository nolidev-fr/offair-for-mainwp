<?php
/**
 * Synchronization with the child sites.
 *
 * @package OffairForMainWP
 */

namespace OffairForMainWP;

defined( 'ABSPATH' ) || exit;

/**
 * Asks each site for its outages during the MainWP synchronization, stores
 * the answer per site and tells whether a site reports its outages.
 *
 * Offair 1.3.0 and later answers under the offair key. When a site sends
 * nothing, the list of plugins MainWP receives in the same synchronization
 * tells whether Offair is missing, inactive or too old to answer.
 */
class Sync {

	/**
	 * Site option holding the stored data, as JSON.
	 */
	const OPTION = 'offair_mainwp';

	/**
	 * First version of Offair that answers the synchronization.
	 */
	const MIN_OFFAIR = '1.3.0';

	/**
	 * Main file of Offair on the child sites.
	 */
	const OFFAIR_PLUGIN = 'offair/offair.php';

	/**
	 * Pages of Offair.
	 */
	const SCREENS = array( 'db', 'maintenance', 'php' );

	/**
	 * Largest number of outages kept per site.
	 */
	const MAX_INCIDENTS = 50;

	/**
	 * Registers the synchronization hooks.
	 */
	public function __construct() {
		add_filter( 'mainwp_sync_others_data', array( $this, 'request' ), 10, 2 );
		add_action( 'mainwp_site_synced', array( $this, 'receive' ), 10, 2 );
	}

	/**
	 * Asks the site for its outages.
	 *
	 * @param array  $others_data Extra requests sent with the synchronization.
	 * @param object $website     Site.
	 * @return array
	 */
	public function request( $others_data, $website = null ) {
		unset( $website );

		if ( ! is_array( $others_data ) ) {
			$others_data = array();
		}

		$others_data['offair_sync'] = 'yes';

		return $others_data;
	}

	/**
	 * Stores what the site sent back.
	 *
	 * @param object $website     Site.
	 * @param array  $information Answer of the site.
	 */
	public function receive( $website, $information ) {
		if ( ! is_object( $website ) || empty( $website->id ) || ! is_array( $information ) ) {
			return;
		}

		$stored = array(
			'synced' => time(),
			'offair' => isset( $information['offair'] ) ? self::sanitize( $information['offair'] ) : null,
			'plugin' => isset( $information['plugins'] ) ? self::find_plugin( $information['plugins'] ) : null,
		);

		apply_filters( 'mainwp_updatewebsiteoptions', false, $website, self::OPTION, wp_json_encode( $stored ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook of MainWP.
	}

	/**
	 * Keeps only the known keys and values of the answer.
	 *
	 * @param mixed $data Answer of Offair.
	 * @return array|null
	 */
	public static function sanitize( $data ) {
		if ( ! is_array( $data ) || empty( $data['version'] ) ) {
			return null;
		}

		$incidents = array();
		if ( isset( $data['incidents'] ) && is_array( $data['incidents'] ) ) {
			foreach ( array_slice( $data['incidents'], 0, self::MAX_INCIDENTS ) as $incident ) {
				$incident = self::sanitize_incident( $incident );
				if ( null !== $incident ) {
					$incidents[] = $incident;
				}
			}
		}

		return array(
			'version'   => self::sanitize_version( $data['version'] ),
			'incidents' => $incidents,
		);
	}

	/**
	 * Keeps only the known keys of an outage.
	 *
	 * @param mixed $incident Outage.
	 * @return array|null
	 */
	private static function sanitize_incident( $incident ) {
		if ( ! is_array( $incident ) || empty( $incident['start'] ) || ! isset( $incident['screen'] ) || ! in_array( $incident['screen'], self::SCREENS, true ) ) {
			return null;
		}

		$start = (int) $incident['start'];

		return array(
			'screen' => $incident['screen'],
			'start'  => $start,
			'end'    => isset( $incident['end'] ) ? max( $start, (int) $incident['end'] ) : $start,
			'count'  => isset( $incident['count'] ) ? (int) $incident['count'] : 0,
			'status' => isset( $incident['status'] ) ? (int) $incident['status'] : 0,
		);
	}

	/**
	 * Keeps only the characters of a version number.
	 *
	 * @param mixed $version Version.
	 * @return string
	 */
	private static function sanitize_version( $version ) {
		return (string) preg_replace( '/[^0-9A-Za-z.\-]/', '', (string) $version );
	}

	/**
	 * Finds Offair in the plugins of the site.
	 *
	 * @param mixed $plugins Plugins, as sent by MainWP Child or as JSON.
	 * @return array|null Version and whether it is active, or null when absent.
	 */
	public static function find_plugin( $plugins ) {
		if ( is_string( $plugins ) ) {
			$plugins = json_decode( $plugins, true );
		}

		foreach ( is_array( $plugins ) ? $plugins : array() as $plugin ) {
			if ( is_array( $plugin ) && isset( $plugin['slug'] ) && self::OFFAIR_PLUGIN === $plugin['slug'] ) {
				return array(
					'version' => isset( $plugin['version'] ) ? self::sanitize_version( $plugin['version'] ) : '',
					'active'  => ! empty( $plugin['active'] ),
				);
			}
		}

		return null;
	}

	/**
	 * Stored data of a site.
	 *
	 * @param int $site_id Site ID.
	 * @return array|null Null when the site has not been synchronized since the extension was installed.
	 */
	public static function get( $site_id ) {
		$json   = apply_filters( 'mainwp_getwebsiteoptions', false, (int) $site_id, self::OPTION ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook of MainWP.
		$stored = is_string( $json ) && '' !== $json ? json_decode( $json, true ) : null;

		return is_array( $stored ) && isset( $stored['synced'] ) ? $stored : null;
	}

	/**
	 * Whether a site reports its outages, or why not.
	 *
	 * @param array|null $stored Stored data of the site.
	 * @return string reporting, unknown, absent, inactive or outdated.
	 */
	public static function status( $stored ) {
		if ( null === $stored ) {
			return 'unknown';
		}

		if ( ! empty( $stored['offair'] ) ) {
			return 'reporting';
		}

		$plugin = isset( $stored['plugin'] ) ? $stored['plugin'] : null;

		if ( empty( $plugin ) ) {
			return 'absent';
		}
		if ( empty( $plugin['active'] ) ) {
			return 'inactive';
		}
		if ( version_compare( (string) $plugin['version'], self::MIN_OFFAIR, '<' ) ) {
			return 'outdated';
		}

		// Recent enough, but the answer was missing: synchronized before the update.
		return 'unknown';
	}

	/**
	 * Whether an outage is a maintenance page shown less than a minute, as
	 * during most updates.
	 *
	 * @param array $incident Outage.
	 * @return bool
	 */
	public static function is_short_update( array $incident ) {
		return 'maintenance' === $incident['screen'] && (int) $incident['end'] - (int) $incident['start'] < MINUTE_IN_SECONDS;
	}

	/**
	 * Minutes with an error page shown since a given time, all pages together,
	 * without the maintenance pages shown less than a minute.
	 *
	 * @param array|null $stored Stored data of the site.
	 * @param int        $since  Unix time.
	 * @return int
	 */
	public static function downtime_minutes( $stored, $since ) {
		$minutes = 0;

		foreach ( self::incidents( $stored ) as $incident ) {
			if ( (int) $incident['end'] >= $since && ! self::is_short_update( $incident ) ) {
				$minutes += max( 1, (int) $incident['count'] );
			}
		}

		return $minutes;
	}

	/**
	 * Most recent outage of a site, without the maintenance pages shown less
	 * than a minute.
	 *
	 * @param array|null $stored Stored data of the site.
	 * @return array|null
	 */
	public static function last_outage( $stored ) {
		$last = null;

		foreach ( self::incidents( $stored ) as $incident ) {
			if ( ! self::is_short_update( $incident ) && ( null === $last || $incident['start'] > $last['start'] ) ) {
				$last = $incident;
			}
		}

		return $last;
	}

	/**
	 * Outages of a site, most recent first.
	 *
	 * @param array|null $stored Stored data of the site.
	 * @return array[]
	 */
	public static function incidents( $stored ) {
		return empty( $stored['offair']['incidents'] ) ? array() : $stored['offair']['incidents'];
	}
}
