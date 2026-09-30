<?php
/**
 * Widgets of the overview and of the page of a site.
 *
 * @package OffairForMainWP
 */

namespace OffairForMainWP;

defined( 'ABSPATH' ) || exit;

/**
 * On the overview: outages of the last 30 days on all the sites.
 * On the page of a site: its recent outages.
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

		if ( ! Plugin::current_user_can_view() ) {
			return $boxes;
		}

		$this->site_id = (int) $site_id;

		$box = array(
			'id'            => 'offair',
			'plugin'        => OFFAIR_MAINWP_FILE,
			'key'           => $this->plugin->key(),
			'metabox_title' => __( 'Offair', 'offair-for-mainwp' ),
			'callback'      => array( $this, 'render' ),
		);

		if ( 0 === $this->site_id ) {
			// Column, row, width and height on the MainWP grid: half the width, tall enough for the list of all the sites.
			$box['layout'] = array( -1, -1, 6, 45 );
		}

		$boxes[] = $box;

		return $boxes;
	}

	/**
	 * Shows the widget of the current page.
	 */
	public function render() {
		if ( ! Plugin::current_user_can_view() ) {
			return;
		}

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
		$since      = time() - self::PERIOD;
		$incidents  = array();
		$not_seeing = 0;

		foreach ( $this->plugin->sites() as $site ) {
			if ( 'reporting' !== Sync::status( $site['stored'] ) ) {
				++$not_seeing;
			}

			foreach ( Sync::incidents( $site['stored'] ) as $incident ) {
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

		$this->header( __( 'Offair', 'offair-for-mainwp' ), __( 'Outages seen by visitors in the last 30 days, all sites', 'offair-for-mainwp' ) );

		echo '<div class="mainwp-scrolly-overflow">';

		if ( $incidents ) {
			$this->incidents_table( array_slice( $incidents, 0, self::MAX_ROWS ), true );
		} else {
			echo '<p>' . esc_html__( 'No outage in the last 30 days.', 'offair-for-mainwp' ) . '</p>';
		}

		if ( $not_seeing ) {
			echo '<p><small>' . esc_html(
				sprintf(
					/* translators: %d: number of sites. */
					_n( '%d site does not report its outages.', '%d sites do not report their outages.', $not_seeing, 'offair-for-mainwp' ),
					$not_seeing
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
		$stored    = Sync::get( $site_id );
		$status    = Sync::status( $stored );
		$incidents = Sync::incidents( $stored );

		$this->header( __( 'Offair', 'offair-for-mainwp' ), __( 'Outages seen by visitors on this site', 'offair-for-mainwp' ) );

		echo '<div class="mainwp-scrolly-overflow">';

		if ( 'reporting' !== $status ) {
			echo '<p>' . Labels::badge( $status ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Labels::badge().
		} elseif ( $incidents ) {
			$this->incidents_table( array_slice( $incidents, 0, self::MAX_ROWS ), false );
		} else {
			echo '<p>' . esc_html__( 'No outage in the last 90 days.', 'offair-for-mainwp' ) . '</p>';
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

		if ( 'reporting' === $status ) {
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
		echo '<table class="ui compact unstackable table"><thead><tr>';
		if ( $with_site ) {
			echo '<th>' . esc_html__( 'Site', 'offair-for-mainwp' ) . '</th>';
		}
		echo '<th>' . esc_html__( 'Page', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'Start', 'offair-for-mainwp' ) . '</th><th>' . esc_html__( 'Duration', 'offair-for-mainwp' ) . '</th></tr></thead><tbody>';

		foreach ( $incidents as $incident ) {
			echo '<tr>';
			if ( $with_site ) {
				echo '<td><a href="' . esc_url( self::site_url( $incident['site']['id'] ) ) . '">' . esc_html( $incident['site']['name'] ) . '</a></td>';
			}
			echo '<td>' . Labels::screen_badge( $incident['screen'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Labels::screen_badge().
			echo '<td>' . esc_html( Labels::short_date( $incident['start'] ) ) . '</td>';
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
