<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
// Delete a custom database table

global $wpdb;
$table_name = $wpdb->prefix . "cf7db_forms";
$wpdb->query("DROP TABLE IF EXISTS $table_name");
?>

