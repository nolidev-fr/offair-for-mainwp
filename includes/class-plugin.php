<?php
/**
 * Plugin bootstrap.
 *
 * @package OffairForMainWP
 */

namespace OffairForMainWP;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the extension with MainWP and wires the components together.
 */
final class Plugin {

	/**
	 * Slug of the extension, also its page name for MainWP.
	 */
	const SLUG = 'offair-for-mainwp';

	/**
	 * Shared instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Key MainWP gives the extension to call its hooks.
	 *
	 * @var string
	 */
	private $key = '';

	/**
	 * Page of all the outages.
	 *
	 * @var Incidents_Page
	 */
	public $incidents_page;

	/**
	 * Returns the shared instance, creating it on first use.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Builds the components and registers the hooks. They stay silent while
	 * MainWP Dashboard is not active, since MainWP fires them.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_filter( 'mainwp_getextensions', array( $this, 'register' ) );

		$this->incidents_page = new Incidents_Page( $this );

		new Sync();
		new Widgets( $this );
	}

	/**
	 * Declares the extension to MainWP.
	 *
	 * @param array $extensions Registered extensions.
	 * @return array
	 */
	public function register( $extensions ) {
		$extensions[] = array(
			'plugin'   => OFFAIR_MAINWP_FILE,
			'api'      => self::SLUG,
			'mainwp'   => false,
			'callback' => array( $this->incidents_page, 'render' ),
			'icon'     => '',
		);

		return $extensions;
	}

	/**
	 * Key given by MainWP, needed to list the sites.
	 *
	 * @return string
	 */
	public function key() {
		if ( '' === $this->key ) {
			$enabled   = apply_filters( 'mainwp_extension_enabled_check', OFFAIR_MAINWP_FILE ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook of MainWP.
			$this->key = is_array( $enabled ) && isset( $enabled['key'] ) ? (string) $enabled['key'] : '';
		}

		return $this->key;
	}

	/**
	 * Sites the current user can see, with their stored Offair data.
	 *
	 * @return array[] Each site has an id, a name, a url and its data.
	 */
	public function sites() {
		$sites = apply_filters( 'mainwp_getsites', OFFAIR_MAINWP_FILE, $this->key(), null ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook of MainWP.
		$out   = array();

		foreach ( is_array( $sites ) ? $sites : array() as $site ) {
			$id    = (int) $site['id'];
			$out[] = array(
				'id'     => $id,
				'name'   => (string) $site['name'],
				'url'    => (string) $site['url'],
				'stored' => Sync::get( $id ),
			);
		}

		return $out;
	}

	/**
	 * Loads the translations shipped in the languages directory. Language
	 * packs from translate.wordpress.org take precedence when present.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'offair-for-mainwp', false, dirname( OFFAIR_MAINWP_BASENAME ) . '/languages' );
	}
}
