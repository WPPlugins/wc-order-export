<?php
/*
Plugin Name: WC Order Export
Plugin URI: http://sarkware.com/wc-fields-factory-a-wordpress-plugin-to-add-custom-fields-to-woocommerce-product-page/
Description: It allows you to add custom fields to your woocommerce product page. You can add custom fields and validations without tweaking any of your theme's code & templates, It also allows you to group the fields and add them to particular products or for particular product categories. Supported field types are text, numbers, email, textarea, checkbox, radio and select.
Version: 1.1.0
Author: Saravana Kumar K, sarkparanjothi
Author URI: http://www.iamsark.com/
License: GPL
Copyright: sarkware.com
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

if( !class_exists( 'export_orders' ) ):

class wcexord_export_orders {
	
	var $info;
	
	public function __construct() {
		$this->info = array(
			'path'				=> plugin_dir_path( __FILE__ ),
			'dir'				=> plugin_dir_url( __FILE__ ),
			'version'			=> '1.0.0'
		);
			
		add_action( 'init', array( $this, 'wcexord_init' ), 1 );
		$this->wcexord_includes();		
		add_action( 'admin_menu', array( $this, 'wcexord_admin_menu' ) );
		add_action( 'admin_head', array( $this, 'wcexord_exporter_ajax_url' ) );
	}
	
	function wcexord_admin_menu() {		
		add_submenu_page( 'woocommerce', 'Order Export', 'Order Export', 'manage_options', 'wcexord-order-export-page', array( $this, 'wcexord_order_page' ) );			
	}
	
	function wcexord_exporter_ajax_url(){
		echo '<script type="text/javascript">var wcexord_ajax_url = "'.admin_url( 'admin-ajax.php' ).'";</script>';
	}
	
	function wcexord_init() {
		if( is_admin() ) {	
			add_action( 'admin_enqueue_scripts',  array( $this, 'wcexord_order_export_script' ) );
		}		
	}
	
	function wcexord_order_export_script(){		
		wp_enqueue_style( 'wcexord-order-export-style', wcexord_order_export()->info['dir']. '/assets/css/exporter-style.css' );		
		wp_enqueue_style( 'wcexord-jquery-date-picker-style', wcexord_order_export()->info['dir']. '/assets/css/jquery-ui.css' );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'wcexord-order-export-script', wcexord_order_export()->info['dir']. "assets/js/wc-order-export.js" );			
	}

	function wcexord_order_page() {
		echo '<h3>WC Order Export</h3>';
		$html = '<div class="wc-order-export-container">';
		$html .= '<table><tbody><tr><td>Start export date</td>';
		$html .= '<td><input type="text" placeholder="dd-mm-yyyy" name="export_start_date" class="export_start_date"></td></tr><tr><td>Start export date</td>';
		$html .= '<td><input type="text" placeholder="dd-mm-yyyy" name="export_end_date" class="export_end_date"></td></tr><tr><td>Order Status</td>';		
		$html .= '<td class="order-export-option-select"><label><input class="wc_order_status all_oder_status_selected" type="checkbox" value="wc-all"> All</label> </td></tr>';
		$html .= '<tr><td>File name  </td><td><input type="text" name="download_file_name" value="wc-order-export" class="download_file_name"></td></tr>';
		$html .= '<tr><td>File type </td><td><label><input type="radio" name="export_file_type" value="xlsx" class="export_file_type" checked> XLSX </label><label><input type="radio" name="export_file_type" value="csv" class="export_file_type"> CSV </label><label><input type="radio" name="export_file_type" value="json" class="export_file_type"> JSON </label></td></tr>';
		$html .= '<tr><td></td><td class="wp-core-u"><button type="button" id="export-order-button" class="button button-primary button-large">Export</button>';
		$html .= '</td></tr><tbody></table>';
		do_action( "wc_order_export_render_page", "wcexord_order_export" );
		echo $html;
	}	
	
	/*
	 * To include class files foe retrive data and form file
	 */
	function wcexord_includes() {	
		include_once( 'classes/wcexord_exporter.php' );
		include_once( 'classes/wcexord_order-genarate.php' );
	}
	
}

function wcexord_order_export() {
	
	global $wcexord_order_export;
	
	if ( !function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'wc_order_export_woocommerce_not_found_notice' );
		return;
	}	
	if( !isset( $wcexord_order_export ) ) {
		$wcexord_order_export = new wcexord_export_orders();
	}	
	
	return $wcexord_order_export;
	
}

add_action( 'plugins_loaded', 'wcexord_order_export', 11 );

if( !function_exists( 'wcexord_order_export_woocommerce_not_found_notice' ) ) {
	function wcexord_order_export_woocommerce_not_found_notice() {
		?>
        <div class="error">
            <p><?php _e( 'WC order export requires WooCommerce, Please make sure it is installed and activated.', 'wc-order-export' ); ?></p>
        </div>
    <?php
    }
}

endif;

?>