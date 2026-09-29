<?php
/**
 * Synchronization with the child sites.
 *
 * @package OffairForMainWP
 */

namespace OffairForMainWP;

defined( 'ABSPATH' ) || exit;

/**
 * Asks each site for its Offair data during the MainWP synchronization,
 * stores the answer per site and tells what state a site is in.
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
	 * States of a page.
	 */
	const PAGE_STATES = array( 'current', 'stale', 'missing', 'foreign', 'off' );

	/**
	 * Problem codes.
	 */
	const PROBLEMS = array( 'php_blocked', 'uploads_unreachable', 'not_writable' );

	/**
	 * Registers the synchronization hooks.
	 */
	public function __construct() {
		add_filter( 'mainwp_sync_others_data', array( $this, 'request' ), 10, 2 );
		add_action( 'mainwp_site_synced', array( $this, 'receive' ), 10, 2 );
	}

	/**
	 * Asks the site for its Offair data.
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

		$pages = array();
		foreach ( self::SCREENS as $screen ) {
			$state            = isset( $data['pages'][ $screen ] ) ? (string) $data['pages'][ $screen ] : '';
			$pages[ $screen ] = in_array( $state, self::PAGE_STATES, true ) ? $state : 'missing';
		}

		$problems = isset( $data['problems'] ) && is_array( $data['problems'] ) ? $data['problems'] : array();

		$incidents = array();
		if ( isset( $data['incidents'] ) && is_array( $data['incidents'] ) ) {
			foreach ( array_slice( $data['incidents'], 0, 50 ) as $incident ) {
				$incident = self::sanitize_incident( $incident );
				if ( null !== $incident ) {
					$incidents[] = $incident;
				}
			}
		}

		return array(
			'format'        => isset( $data['format'] ) ? (int) $data['format'] : 1,
			'version'       => preg_replace( '/[^0-9A-Za-z.\-]/', '', (string) $data['version'] ),
			'pages'         => $pages,
			'problems'      => array_values( array_intersect( self::PROBLEMS, $problems ) ),
			'alert'         => ! empty( $data['alert'] ),
			'last_incident' => isset( $data['last_incident'] ) ? self::sanitize_incident( $data['last_incident'] ) : null,
			'incidents'     => $incidents,
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
					'version' => isset( $plugin['version'] ) ? preg_replace( '/[^0-9A-Za-z.\-]/', '', (string) $plugin['version'] ) : '',
					'active'  => ! empty( $plugin['active'] ),
				);
			}
		}

		return null;
	}

	/**
	 * Stored data of a site.
	 *
	 * @param int|object|array $website Site, its ID or its row with the option.
	 * @return array|null Null when the site has not been synchronized since the extension was installed.
	 */
	public static function get( $website ) {
		if ( is_array( $website ) && array_key_exists( self::OPTION, $website ) ) {
			$json = $website[ self::OPTION ];
		} else {
			if ( is_array( $website ) ) {
				$website = isset( $website['id'] ) ? (int) $website['id'] : 0;
			}
			$json = apply_filters( 'mainwp_getwebsiteoptions', false, $website, self::OPTION ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook of MainWP.
		}

		$stored = is_string( $json ) && '' !== $json ? json_decode( $json, true ) : null;

		return is_array( $stored ) && isset( $stored['synced'] ) ? $stored : null;
	}

	/**
	 * State of a site.
	 *
	 * @param array|null $stored Stored data of the site.
	 * @return string ok, warning, unknown, absent, inactive or outdated.
	 */
	public static function status( $stored ) {
		if ( null === $stored ) {
			return 'unknown';
		}

		if ( ! empty( $stored['offair'] ) ) {
			return self::needs_attention( $stored['offair'] ) ? 'warning' : 'ok';
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
	 * Whether a page or a problem needs attention.
	 *
	 * @param array $offair Offair data of a site.
	 * @return bool
	 */
	public static function needs_attention( array $offair ) {
		if ( ! empty( $offair['problems'] ) ) {
			return true;
		}

		foreach ( (array) $offair['pages'] as $state ) {
			if ( in_array( $state, array( 'stale', 'missing', 'foreign' ), true ) ) {
				return true;
			}
		}

		return false;
	}
}
