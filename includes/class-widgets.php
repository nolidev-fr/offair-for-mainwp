<?php
/**
 * Widgets of the overview and of the page of a site.
 *
 * @package OffairForMainWP
 */

namespace OffairForMainWP;

defined( 'ABSPATH' ) || exit;

/**
 * On the overview: outages of the last 30 days and sites that need attention.
 * On the page of a site: state of the three pages and its recent outages.
 */
class Widgets {

	/**
	 * Period of the overview widget, in seconds (30 days).
	 */
	const PERIOD = 2592000;

	/**
	 * Largest number of outages listed in a widget.
	 */
	const MAX_ROWS = 10;

	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Site whose page is being shown, 0 on the overview.
	 *
	 * @var int
	 */
	private $site_id = 0;

	/**
	 * Registers the hooks.
	 *
	 * @param Plugin $plugin Plugin.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;

		add_filter( 'mainwp_getmetaboxes', array( $this, 'register' ), 10, 2 );
	}

	/**
	 * Adds the widget. MainWP passes the site ID on the page of a site only.
	 *
	 * @param array    $boxes   Widgets.
	 * @param int|null $site_id Site ID.
	 * @return array
	 */
	public function register( $boxes, $site_id = null ) {
		if ( ! is_array( $boxes ) ) {
			$boxes = array();
		}

		$this->site_id = (int) $site_id;

		$boxes[] = array(
			'id'            => 'offair',
			'plugin'        => OFFAIR_MAINWP_FILE,
			'key'           => $this->plugin->key(),
			'metabox_title' => __( 'Offair', 'offair-for-mainwp' ),
			'callback'      => array( $this, 'render' ),
		);

		return $boxes;
	}

	/**
	 * Shows the widget of the current page.
	 */
	public function render() {
		if ( $this->site_id > 0 ) {
			$this->render_site( $this->site_id );
			return;
		}

		$this->render_overview();
	}

	/**
	 * Widget of the overview.
	 */
	private function render_overview() {
		$since     = time() - self::PERIOD;
		$incidents = array();
		$attention = array();
		$missing   = 0;

		foreach ( $this->plugin->sites() as $site ) {
			$status = Sync::status( $site['stored'] );

			if ( 'warning' === $status ) {
				$attention[] = $site;
			} elseif ( 'ok' !== $status ) {
				++$missing;
			}

			if ( empty( $site['stored']['offair']['incidents'] ) ) {
				continue;
			}

			foreach ( $site['stored']['offair']['incidents'] as $incident ) {
				if ( $incident['end'] >= $since ) {
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

		$this->header( __( 'Offair', 'offair-for-mainwp' ), __( 'Outages of the last 30 days, all sites', 'offair-for-mainwp' ) );

		echo '<div class="mainwp-scrolly-overflow">';

		if ( $incidents ) {
			$this->incidents_table( array_slice( $incidents, 0, self::MAX_ROWS ), true );
		} else {
			echo '<p>' . esc_html__( 'No outage in the last 30 days.', 'offair-for-mainwp' ) . '</p>';
		}

		if ( $attention ) {
			echo '<h4 class="ui header">' . esc_html__( 'Sites that need attention', 'offair-for-mainwp' ) . '</h4><div class="ui list">';
			foreach ( $attention as $site ) {
				echo '<div class="item"><a href="' . esc_url( self::site_url( $site['id'] ) ) . '">' . esc_html( $site['name'] ) . '</a></div>';
			}
			echo '</div>';
		}

		if ( $missing ) {
			echo '<p><small>' . esc_html(
				sprintf(
					/* translators: %d: number of sites. */
					_n( '%d site does not report Offair data.', '%d sites do not report Offair data.', $missing, 'offair-for-mainwp' ),
					$missing
				)
			) . '</small></p>';
		}

		echo '</div>';

		$this->footer( $this->plugin->incidents_page->url(), __( 'All outages', 'offair-for-mainwp' ) );
	}

	/**
	 * Widget of the page of a site.
	 *
	 * @param int $site_id Site ID.
	 */
	private function render_site( $site_id ) {
		$stored = Sync::get( $site_id );
		$status = Sync::status( $stored );

		$this->header( __( 'Offair', 'offair-for-mainwp' ), __( 'Error pages and outages of this site', 'offair-for-mainwp' ) );

		echo '<div class="mainwp-scrolly-overflow">';
		echo '<p>' . Labels::badge( $status ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Labels::badge().

		if ( ! empty( $stored['offair'] ) ) {
			$offair = $stored['offair'];

			echo '<table class="ui single line table"><tbody>';
			foreach ( $offair['pages'] as $screen => $state ) {
				echo '<tr><td>' . esc_html( Labels::screen( $screen ) ) . '</td><td>' . esc_html( Labels::page_state( $state ) ) . '</td></tr>';
			}
			echo '<tr><td>' . esc_html__( 'Email alert', 'offair-for-mainwp' ) . '</td><td>' . esc_html( $offair['alert'] ? __( 'On', 'offair-for-mainwp' ) : __( 'Off', 'offair-for-mainwp' ) ) . '</td></tr>';
			echo '</tbody></table>';

			foreach ( $offair['problems'] as $code ) {
				echo '<div class="ui small yellow message">' . esc_html( Labels::problem( $code ) ) . '</div>';
			}

			if ( $offair['incidents'] ) {
				echo '<h4 class="ui header">' . esc_html__( 'Recent outages', 'offair-for-mainwp' ) . '</h4>';
				$this->incidents_table( array_slice( $offair['incidents'], 0, self::MAX_ROWS ), false );
			} else {
				echo '<p>' . esc_html__( 'No outage in the last 90 days.', 'offair-for-mainwp' ) . '</p>';
			}
		}

		if ( null !== $stored ) {
			echo '<p><small>' . esc_html(
				sprintf(
					/* translators: %s: date of the last synchronization. */
					__( 'Synchronized on %s.', 'offair-for-mainwp' ),
					Labels::date( $stored['synced'] )
				)
			) . '</small></p>';
		}

		echo '</div>';

		if ( in_array( $status, array( 'ok', 'warning' ), true ) ) {
			$this->footer( Labels::settings_url( $site_id ), __( 'Open the Offair settings', 'offair-for-mainwp' ), true );
		}
	}

	/**
	 * Table of outages.
	 *
	 * @param array[] $incidents Outages, with their site when $with_site is true.
	 * @param bool    $with_site Whether to show the site column.
	 */
	private function incidents_table( array $incidents, $with_site ) {
		echo '<table class="ui single line compact table"><thead><tr>';
		if ( $with_site ) {
			echo '<th>' . esc_html__( 'Site', 'offair-for-mainwp' ) . '</th>';
		}
		echo '<th>' . esc_html__( 'Page', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'Start', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'Duration', 'offair-for-mainwp' ) . '</th></tr></thead><tbody>';

		foreach ( $incidents as $incident ) {
			echo '<tr>';
			if ( $with_site ) {
				echo '<td><a href="' . esc_url( self::site_url( $incident['site']['id'] ) ) . '">' . esc_html( $incident['site']['name'] ) . '</a></td>';
			}
			echo '<td>' . esc_html( Labels::screen( $incident['screen'] ) ) . '</td>';
			echo '<td>' . esc_html( Labels::date( $incident['start'] ) ) . '</td>';
			echo '<td>' . esc_html( Labels::duration( $incident ) ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Header of a widget.
	 *
	 * @param string $title    Title.
	 * @param string $subtitle Subtitle.
	 */
	private function header( $title, $subtitle ) {
		echo '<div class="mainwp-widget-header"><h2 class="ui header handle-drag">' . esc_html( $title ) . '<div class="sub header">' . esc_html( $subtitle ) . '</div></h2></div>';
	}

	/**
	 * Footer of a widget, with one button.
	 *
	 * @param string $url        Address of the button.
	 * @param string $label      Label of the button.
	 * @param bool   $new_window Whether the link opens a new window.
	 */
	private function footer( $url, $label, $new_window = false ) {
		echo '<div class="ui two columns grid mainwp-widget-footer"><div class="column"><a class="ui mini button" href="' . esc_url( $url ) . '"' . ( $new_window ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( $label ) . '</a></div><div class="column"></div></div>';
	}

	/**
	 * Address of the overview of a site in MainWP.
	 *
	 * @param int $site_id Site ID.
	 * @return string
	 */
	public static function site_url( $site_id ) {
		return add_query_arg(
			array(
				'page'      => 'managesites',
				'dashboard' => (int) $site_id,
			),
			admin_url( 'admin.php' )
		);
	}
}
