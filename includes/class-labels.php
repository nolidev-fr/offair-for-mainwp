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
		// The grey of the MainWP theme is too pale for an empty label: the color is set here.
		$style = 'grey' === self::color( $status ) ? ' style="background-color:#8c8f94;border-color:#8c8f94"' : '';

		return '<span class="ui ' . esc_attr( self::color( $status ) ) . ' empty circular mini label"' . $style . '></span> ' . esc_html( self::status( $status ) );
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
	 * Name of a page after a dot in its color, the colors of the Offair test card.
	 *
	 * @param string $screen db, maintenance or php.
	 * @return string Escaped markup.
	 */
	public static function screen_badge( $screen ) {
		$colors = array(
			'db'          => '#d3766a',
			'maintenance' => '#e6c76a',
			'php'         => '#6d7fb3',
		);
		$color  = isset( $colors[ $screen ] ) ? $colors[ $screen ] : '#8c8f94';

		return '<span class="ui empty circular mini label" style="background-color:' . esc_attr( $color ) . ';border-color:' . esc_attr( $color ) . '"></span> ' . esc_html( self::screen( $screen ) );
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
	 * Short date and time, for the narrow widgets: day and month, then the time.
	 *
	 * @param int $time Unix time.
	 * @return string
	 */
	public static function short_date( $time ) {
		/* translators: Date format of the widgets, see https://www.php.net/manual/datetime.format.php */
		return wp_date( __( 'M j', 'offair-for-mainwp' ) . ' ' . get_option( 'time_format' ), (int) $time );
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
	 * A number of minutes, in hours and minutes past an hour.
	 *
	 * @param int $minutes Minutes.
	 * @return string
	 */
	public static function minutes( $minutes ) {
		$minutes = (int) $minutes;

		if ( $minutes < 60 ) {
			/* translators: %d: number of minutes. */
			return sprintf( __( '%d min', 'offair-for-mainwp' ), $minutes );
		}

		/* translators: 1: number of hours, 2: number of minutes. */
		return sprintf( __( '%1$d h %2$02d min', 'offair-for-mainwp' ), intdiv( $minutes, 60 ), $minutes % 60 );
	}

	/**
	 * Line counting the short maintenance pages left out of a list.
	 *
	 * @param int $count Number of short maintenance pages.
	 * @return string
	 */
	public static function short_updates( $count ) {
		return sprintf(
			/* translators: %d: number of maintenance pages. */
			_n( 'Plus %d maintenance page shown less than a minute, during an update.', 'Plus %d maintenance pages shown less than a minute, during updates.', $count, 'offair-for-mainwp' ),
			$count
		);
	}

	/**
	 * Link that opens the Offair settings of a site, logged in through MainWP.
	 *
	 * MainWP refuses to open a site without the _opennonce it checks against
	 * mainwp-admin-nonce, as in the links it builds itself.
	 *
	 * @param int $site_id Site ID.
	 * @return string
	 */
	public static function settings_url( $site_id ) {
		return add_query_arg(
			array(
				'page'       => 'SiteOpen',
				'newWindow'  => 'yes',
				'websiteid'  => (int) $site_id,
				'location'   => rawurlencode( base64_encode( 'options-general.php?page=offair' ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- MainWP expects the location in base64.
				'_opennonce' => wp_create_nonce( 'mainwp-admin-nonce' ),
			),
			admin_url( 'admin.php' )
		);
	}
}
