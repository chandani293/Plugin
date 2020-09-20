<?php
if (!defined( 'ABcf7dbATH')) exit;
function wp_cf7db_data_list() {
	if ( ! class_exists('WPCF7_ContactForm') ) {
           wp_die( 'Please Install/Activate <a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7</a> Plugin.' );
    }
	else{ 
		echo 'hii';
		
		/* WP List DataTable Initialize */
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABcf7dbATH . 'wp-admin/includes/class-wp-list-table.php' );
		}
		
		
	}	
}