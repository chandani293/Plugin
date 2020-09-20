<?php
/*
Plugin Name: WP CF7DB
Description: Wordpress Contact Form 7 Form DataList, Bulk Delete Option
Author: Chandani Vadaria
Author URI: https://cmsminds.com
Text Domain: wp-cf7db
Version: 1.0.0
*/

defined( 'ABSPATH' ) or die();
define('ROOTDIR', plugin_dir_path(__FILE__));
// function to create the DB / Options / Defaults					
function wp_cf7db_table_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . "cf7db_forms";
	
	if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
				form_id bigint(20) NOT NULL AUTO_INCREMENT,
				form_post_id bigint(20) NOT NULL,
				form_value longtext NOT NULL,
				form_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (form_id)
			) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}	
}

// Install Table When plugin activation
register_activation_hook(__FILE__, 'wp_cf7db_table_install');

function wp_cf7db_table_deactivate() {
	// Remove/Delete Table 	
}

// Delete Table When plugin deactivation
register_deactivation_hook( __FILE__, 'wp_cf7db_table_deactivate' );

//Register Wordpress Menu Item Show in Backend
add_action('admin_menu','wp_cf7db_admin_menu');
function wp_cf7db_admin_menu() {
	//this is the main item for the menu
	add_menu_page('Contact Form Entry', //page title
	'WP CForm DB', //menu title
	'manage_options', //capabilities
	'wp_cf7db_list', //menu slug
	'wp_cf7db_data_list' //function
	);
}

//add_action( 'wp_enqueue_scripts', 'cf7cb_style_script_file' );
function cf7cb_style_script_file(){ 
	wp_register_script( 'cf7db-custom-js',  plugin_dir_url( __FILE__ ) . 'js/custom.js' );
	wp_enqueue_style( 'cf7db-custom-js' );
	
	wp_register_style( 'cf7db-admin-style',  plugin_dir_url( __FILE__ ) . 'css/admin-style.css' );
    wp_enqueue_style( 'cf7db-admin-style' );
}

require_once ROOTDIR . '/wp-cf7db-data-list.php';

function cf7db_before_send_mail( $form_tag ) {

    global $wpdb;
    $table_name    = $wpdb->prefix . "cf7db_forms";
    $upload_dir    = wp_upload_dir();
    $cfdb7_dirname = $upload_dir['basedir'].'/cfdb7_uploads';
    $time_now      = time();

    $form = WPCF7_Submission::get_instance();

    if ( $form ) {

        $black_list   = array('_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag',
        '_wpcf7_is_ajax_call','cfdb7_name', '_wpcf7_container_post','_wpcf7cf_hidden_group_fields',
        '_wpcf7cf_hidden_groups', '_wpcf7cf_visible_groups', '_wpcf7cf_options','g-recaptcha-response');

        $data           = $form->get_posted_data();
        $files          = $form->uploaded_files();
        $uploaded_files = array();

        $rm_underscore  = apply_filters('cfdb7_remove_underscore_data', true); 

        foreach ($files as $file_key => $file) {
            array_push($uploaded_files, $file_key);
            copy($file, $cfdb7_dirname.'/'.$time_now.'-'.basename($file));
        }

        $form_data   = array();

        $form_data['cfdb7_status'] = 'unread';
        foreach ($data as $key => $d) {
            
            $matches = array();
            if( $rm_underscore ) preg_match('/^_.*$/m', $key, $matches);

            if ( !in_array($key, $black_list ) && !in_array($key, $uploaded_files ) && empty( $matches[0] ) ) {

                $tmpD = $d;

                if ( ! is_array($d) ){

                    $bl   = array('\"',"\'",'/','\\','"',"'");
                    $wl   = array('&quot;','&#039;','&#047;', '&#092;','&quot;','&#039;');

                    $tmpD = str_replace($bl, $wl, $tmpD );
                }

                $form_data[$key] = $tmpD;
            }
            if ( in_array($key, $uploaded_files ) ) {
                $form_data[$key.'cfdb7_file'] = $time_now.'-'.$d;
            }
        }

        /* cf7db before save data */
        $form_data = apply_filters('cf7db_before_save_data', $form_data);

        do_action( 'cf7db_before_save', $form_data );

        $form_post_id = $form_tag->id();
        $form_value   = serialize( $form_data );
        $form_date    = current_time('Y-m-d H:i:s');

        $wpdb->insert( $table_name, array(
            'form_post_id' => $form_post_id,
            'form_value'   => $form_value,
            'form_date'    => $form_date
        ) );

        
        $insert_id = $wpdb->insert_id; /* cf7db after insert data insertid */
        do_action( 'cf7db_after_save_data', $insert_id );
    }

}
add_action( 'wpcf7_before_send_mail', 'cf7db_before_send_mail' );
