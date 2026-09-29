<?php
/**
 * Texts and small pieces of markup shared by the screens.
 *
 * @package OffairForMainWP
 */

namespace OffairForMainWP;

defined( 'ABSPATH' ) || exit;

/**
 * Turns the codes sent by the sites into texts in the language of the dashboard.
 */
class Labels {

	/**
	 * Short label of the state of a site.
	 *
	 * @param string $status State, from Sync::status().
	 * @return string
	 */
	public static function status( $status ) {
		$labels = array(
			'reporting' => __( 'Reports its outages', 'offair-for-mainwp' ),
			'unknown'   => __( 'Sync to see', 'offair-for-mainwp' ),
			'absent'    => __( 'Offair not installed', 'offair-for-mainwp' ),
			'inactive'  => __( 'Offair inactive', 'offair-for-mainwp' ),
			/* translators: %s: version of Offair, for example 1.3.0. */
			'outdated'  => sprintf( __( 'Update Offair to %s or later', 'offair-for-mainwp' ), Sync::MIN_OFFAIR ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}

	/**
	 * Color of the dot of a state, from the MainWP palette.
	 *
	 * @param string $status State, from Sync::status().
	 * @return string
	 */
	public static function color( $status ) {
		return 'reporting' === $status ? 'green' : 'grey';
	}

	/**
	 * Colored dot followed by the label of a state.
	 *
	 * @param string $status State, from Sync::status().
	 * @return string Escaped markup.
	 */
	public static function badge( $status ) {
		return '<span class="ui ' . esc_attr( self::color( $status ) ) . ' empty circular mini label"></span> ' . esc_html( self::status( $status ) );
	}

	/**
	 * Name of a page.
	 *
	 * @param string $screen db, maintenance or php.
	 * @return string
	 */
	public static function screen( $screen ) {
		$labels = self::screens();

		return isset( $labels[ $screen ] ) ? $labels[ $screen ] : $screen;
	}

	/**
	 * Names of the pages.
	 *
	 * @return array<string, string>
	 */
	public static function screens() {
		return array(
			'db'          => __( 'Database error', 'offair-for-mainwp' ),
			'maintenance' => __( 'Maintenance', 'offair-for-mainwp' ),
			'php'         => __( 'PHP error', 'offair-for-mainwp' ),
		);
	}

	/**
	 * Date and time in the format of the dashboard.
	 *
	 * @param int $time Unix time.
	 * @return string
	 */
	public static function date( $time ) {
		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $time );
	}

	/**
	 * Duration of an outage, in words.
	 *
	 * @param array $incident Outage.
	 * @return string
	 */
	public static function duration( array $incident ) {
		$seconds = (int) $incident['end'] - (int) $incident['start'];

		if ( $seconds < MINUTE_IN_SECONDS ) {
			return __( 'less than a minute', 'offair-for-mainwp' );
		}

		return human_time_diff( (int) $incident['start'], (int) $incident['end'] );
	}

	/**
	 * Link that opens the Offair settings of a site, logged in through MainWP.
	 *
	 * @param int $site_id Site ID.
	 * @return string
	 */
	public static function settings_url( $site_id ) {
		return add_query_arg(
			array(
				'page'      => 'SiteOpen',
				'newWindow' => 'yes',
				'websiteid' => (int) $site_id,
				'location'  => base64_encode( 'options-general.php?page=offair' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- MainWP expects the location in base64.
			),
			admin_url( 'admin.php' )
		);
	}
}
