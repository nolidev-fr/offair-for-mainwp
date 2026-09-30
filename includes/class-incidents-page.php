<?php
/**
 * Page of all the outages.
 *
 * @package OffairForMainWP
 */

namespace OffairForMainWP;

defined( 'ABSPATH' ) || exit;

/**
 * Extension page: every outage of every site, filtered by site and by page,
 * and whether each site reports its outages.
 */
class Incidents_Page {

	/**
	 * Page name, which MainWP makes from the folder of the extension.
	 */
	const PAGE = 'Extensions-Offair-For-Mainwp';

	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;

		add_filter( 'mainwp_header_title', array( $this, 'title' ) );
	}

	/**
	 * Title in the MainWP header, instead of the one made from the page name.
	 *
	 * @param string $title Title.
	 * @return string
	 */
	public function title( $title ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only compares the page name.
		if ( isset( $_GET['page'] ) && self::PAGE === $_GET['page'] ) {
			return __( 'Offair for MainWP', 'offair-for-mainwp' );
		}

		return $title;
	}

	/**
	 * Address of the page. MainWP names it after the folder of the extension.
	 *
	 * @param array $args Query arguments.
	 * @return string
	 */
	public function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Shows the page, inside the MainWP frame.
	 */
	public function render() {
		if ( ! Plugin::current_user_can_view() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'offair-for-mainwp' ), 403 );
		}

		do_action( 'mainwp_pageheader_extensions', OFFAIR_MAINWP_FILE ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook of MainWP.

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters.
		$site_filter   = isset( $_GET['offair_site'] ) ? absint( $_GET['offair_site'] ) : 0;
		$screen_filter = isset( $_GET['offair_screen'] ) ? sanitize_key( wp_unslash( $_GET['offair_screen'] ) ) : '';
		// phpcs:enable

		if ( ! array_key_exists( $screen_filter, Labels::screens() ) ) {
			$screen_filter = '';
		}

		$sites     = $this->plugin->sites();
		$incidents = array();

		foreach ( $sites as $site ) {
			if ( $site_filter && $site_filter !== $site['id'] ) {
				continue;
			}

			foreach ( Sync::incidents( $site['stored'] ) as $incident ) {
				if ( '' === $screen_filter || $screen_filter === $incident['screen'] ) {
					$incidents[] = $incident + array( 'site' => $site );
				}
			}
		}

		usort(
			$incidents,
			static function ( $a, $b ) {
				return $b['start'] - $a['start'];
			}
		);

		echo '<div class="ui segment">';

		$this->render_filters( $sites, $site_filter, $screen_filter );

		echo '<h3 class="ui header">' . esc_html__( 'Outages of the last 90 days', 'offair-for-mainwp' ) . '</h3>';

		if ( $incidents ) {
			echo '<table class="ui single line table"><thead><tr>';
			echo '<th>' . esc_html__( 'Site', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'Page', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'Start', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'Duration', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'HTTP status', 'offair-for-mainwp' ) . '</th>';
			echo '</tr></thead><tbody>';

			foreach ( $incidents as $incident ) {
				echo '<tr>';
				echo '<td><a href="' . esc_url( Widgets::site_url( $incident['site']['id'] ) ) . '">' . esc_html( $incident['site']['name'] ) . '</a></td>';
				echo '<td>' . esc_html( Labels::screen( $incident['screen'] ) ) . '</td>';
				echo '<td>' . esc_html( Labels::date( $incident['start'] ) ) . '</td>';
				echo '<td>' . esc_html( Labels::duration( $incident ) ) . '</td>';
				echo '<td>' . esc_html( $incident['status'] ? (string) $incident['status'] : '' ) . '</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		} else {
			echo '<p>' . esc_html__( 'No outage recorded.', 'offair-for-mainwp' ) . '</p>';
		}

		echo '<p><small>' . esc_html__( 'Offair records an outage when a visitor sees one of its pages. An outage while nobody visits a site cannot be seen. The data is updated at each MainWP synchronization.', 'offair-for-mainwp' ) . '</small></p>';

		$this->render_sites( $sites );

		echo '</div>';

		do_action( 'mainwp_pagefooter_extensions', OFFAIR_MAINWP_FILE ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook of MainWP.
	}

	/**
	 * Filters by site and by page.
	 *
	 * @param array[] $sites         Sites.
	 * @param int     $site_filter   Selected site.
	 * @param string  $screen_filter Selected page.
	 */
	private function render_filters( array $sites, $site_filter, $screen_filter ) {
		echo '<form method="get" class="ui form"><input type="hidden" name="page" value="' . esc_attr( self::PAGE ) . '"><div class="three fields">';

		echo '<div class="field"><label for="offair-site">' . esc_html__( 'Site', 'offair-for-mainwp' ) . '</label><select id="offair-site" name="offair_site" class="ui dropdown"><option value="0">' . esc_html__( 'All sites', 'offair-for-mainwp' ) . '</option>';
		foreach ( $sites as $site ) {
			echo '<option value="' . esc_attr( (string) $site['id'] ) . '"' . selected( $site_filter, $site['id'], false ) . '>' . esc_html( $site['name'] ) . '</option>';
		}
		echo '</select></div>';

		echo '<div class="field"><label for="offair-screen">' . esc_html__( 'Page', 'offair-for-mainwp' ) . '</label><select id="offair-screen" name="offair_screen" class="ui dropdown"><option value="">' . esc_html__( 'All pages', 'offair-for-mainwp' ) . '</option>';
		foreach ( Labels::screens() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $screen_filter, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></div>';

		echo '<div class="field"><label>&nbsp;</label><button type="submit" class="ui green button">' . esc_html__( 'Filter', 'offair-for-mainwp' ) . '</button></div>';

		echo '</div></form>';
	}

	/**
	 * State of Offair on each site.
	 *
	 * @param array[] $sites Sites.
	 */
	private function render_sites( array $sites ) {
		echo '<h3 class="ui header">' . esc_html__( 'Sites', 'offair-for-mainwp' ) . '</h3>';
		echo '<table class="ui single line table"><thead><tr>';
		echo '<th>' . esc_html__( 'Site', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'State', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'Offair version', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'Last outage', 'offair-for-mainwp' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $sites as $site ) {
			$stored  = $site['stored'];
			$last    = current( Sync::incidents( $stored ) );
			$version = '';

			if ( ! empty( $stored['offair']['version'] ) ) {
				$version = $stored['offair']['version'];
			} elseif ( ! empty( $stored['plugin']['version'] ) ) {
				$version = $stored['plugin']['version'];
			}

			echo '<tr>';
			echo '<td><a href="' . esc_url( Widgets::site_url( $site['id'] ) ) . '">' . esc_html( $site['name'] ) . '</a></td>';
			echo '<td>' . Labels::badge( Sync::status( $stored ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Labels::badge().
			echo '<td>' . esc_html( $version ) . '</td>';
			echo '<td>' . esc_html( $last ? Labels::date( $last['start'] ) : '' ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}
}
