<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package MTLS_Centerwise_for_Quiz_Maker
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom tables securely for Quiz Maker
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mtls_qm_centers" );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mtls_qm_students" );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mtls_qm_student_categories" );

// Delete plugin specific options (PRO settings)
delete_option( 'mtls_qm_pro_center_msg' );
delete_option( 'mtls_qm_pro_center_bg' );
delete_option( 'mtls_qm_pro_stud_msg' );
delete_option( 'mtls_qm_pro_stud_bg' );

// Remove custom roles mapped
remove_role( 'center_owner' );
remove_role( 'center_student' );