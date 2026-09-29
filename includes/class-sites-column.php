<?php
/**
 * Column in the list of the sites.
 *
 * @package OffairForMainWP
 */

namespace OffairForMainWP;

defined( 'ABSPATH' ) || exit;

/**
 * Adds an Offair column to Sites, Manage Sites: a colored dot, the state and
 * the date of the last outage.
 */
class Sites_Column {

	/**
	 * Column key.
	 */
	const COLUMN = 'offair';

	/**
	 * Registers the hooks.
	 */
	public function __construct() {
		add_filter( 'mainwp_sitestable_getcolumns', array( $this, 'columns' ) );
		add_filter( 'mainwp_sitestable_prepare_extra_view', array( $this, 'extra_view' ) );
		add_filter( 'mainwp_sitestable_item', array( $this, 'item' ), 10, 2 );
	}

	/**
	 * Adds the column.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function columns( $columns ) {
		$columns[ self::COLUMN ] = __( 'Offair', 'offair-for-mainwp' );

		return $columns;
	}

	/**
	 * Loads the stored data with the list, in the same query.
	 *
	 * @param array $extra_view Site options loaded with the list.
	 * @return array
	 */
	public function extra_view( $extra_view ) {
		if ( is_array( $extra_view ) ) {
			$extra_view[] = Sync::OPTION;
		}

		return $extra_view;
	}

	/**
	 * Fills the column of a site.
	 *
	 * @param array  $item        Site row.
	 * @param string $column_name Column being shown.
	 * @return array
	 */
	public function item( $item, $column_name = '' ) {
		if ( self::COLUMN !== $column_name || ! is_array( $item ) ) {
			return $item;
		}

		$stored = Sync::get( $item );
		$html   = Labels::badge( Sync::status( $stored ) );

		$last = self::last_incident( $stored );
		if ( null !== $last ) {
			$html .= '<br><small>' . esc_html(
				sprintf(
					/* translators: %s: date of the last outage. */
					__( 'Last outage: %s', 'offair-for-mainwp' ),
					Labels::date( $last['start'] )
				)
			) . '</small>';
		}

		$item[ self::COLUMN ] = $html;

		return $item;
	}

	/**
	 * Most recent outage of a site, whatever the page.
	 *
	 * @param array|null $stored Stored data.
	 * @return array|null
	 */
	public static function last_incident( $stored ) {
		if ( empty( $stored['offair'] ) ) {
			return null;
		}

		$offair = $stored['offair'];
		$last   = isset( $offair['last_incident'] ) ? $offair['last_incident'] : null;

		if ( ! empty( $offair['incidents'][0] ) && ( null === $last || $offair['incidents'][0]['start'] > $last['start'] ) ) {
			$last = $offair['incidents'][0];
		}

		return $last;
	}
}
