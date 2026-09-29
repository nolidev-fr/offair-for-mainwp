<?php
/**
 * Removes the data stored for each site.
 *
 * @package OffairForMainWP
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// MainWP keeps site options in its own table. MainWP may already be gone.
$offair_mainwp_table = $wpdb->prefix . 'mainwp_wp_options';

if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $offair_mainwp_table ) ) === $offair_mainwp_table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->delete( $offair_mainwp_table, array( 'name' => 'offair_mainwp' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}
