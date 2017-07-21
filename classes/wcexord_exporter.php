<?php 
/**
 * @author 		: Saravana Kumar K, sarkparanjothi
 * @author url  : iamsark.com
 * @copyright	: sarkware.com
 * One of the core module, get allorder data from woocomerce
 *
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class wcexord_exporter {
 	function __construct(){ 		
 		add_filter( "wc_order_export/exporter_data", array( $this, "wcexord_exporter_data" ), 10, 1 );
 	}	
 	
 	function wcexord_exporter_data( $payload ){
 		$start_date = $payload[ "start_date" ];
 		$end_date 	= $payload[ "end_date" ];
 		$order_state_front = $payload[ "order_status" ];
 		$date_start_exp = explode( "-", $start_date );
 		$date_end_exp 	= explode( "-", $end_date );
 		$order_state = $order_state_front == "wc-all" ? "any" : json_decode( preg_replace('/\\\"/',"\"", $order_state_front ) );
 		
 		if( is_array( $order_state ) ){
	 		if( sizeof( $order_state ) == 1 ){
	 			$order_state = $order_state[0];
	 		}
 		}
 		
 		$args = array(
 				'date_query' => array(
 						array(
 							'after' =>  array(
 									'year'  => $date_start_exp[2],
 									'month' => $date_start_exp[1],
 									'day'   => $date_start_exp[0],
 							),
 							'before' => array(
 									'year'  => $date_end_exp[2],
 									'month' => $date_end_exp[1],
 									'day'   => $date_end_exp[0],
 							),
 							'inclusive' => true,
 						),
 				),
 				'post_type' => 'shop_order',
 				'post_status' => $order_state,
 				'posts_per_page' => -1,
 		);
 		$query = new WP_Query( $args );
 		$orders_data = $query->posts;
 		// TO get order data ( billing address shipping address etc.. )
 		for ( $i = 0; $i < sizeof( $orders_data ); $i++ ){
 			$get_meta = $this->wcexord_export_order_data( $orders_data[$i]->ID );
 			$orders_data[$i]->order_metas =  $get_meta;
 		} 
 		// To get order custom metas
 		for ( $i = 0; $i < sizeof( $orders_data ); $i++ ){
 			$get_meta = $this->wcexord_export_order_meta_custom( $orders_data[$i]->ID );
 			$orders_data[$i]->order_custom_metas =  $get_meta;
 		}  		
 		return $orders_data;
 	}
 	
 	/*
 	 * get order meta for perticular orders
 	 */ 	
 	function wcexord_export_order_data( $order_id ){
 		$order_meta = array();
 		global $wpdb;
 		$meta_array =  array( "_payment_method_title", "_billing_first_name","_billing_last_name","_billing_company","_billing_address_1","_billing_address_2","_billing_city","_billing_state","_billing_postcode","_billing_country","_billing_email","_billing_phone","_shipping_first_name","_shipping_last_name","_shipping_company","_shipping_address_1","_shipping_address_2","_shipping_city","_shipping_state","_shipping_postcode","_shipping_country", "_cart_discount","_cart_discount_tax","_order_shipping","_order_shipping_tax","_order_tax","_order_total","_prices_include_tax","_billing_address_index","_shipping_method" );
 		$meta_array = apply_filters( "wc_order_export_add_extra_meta", $meta_array, $order_id );
 		for( $i = 0; $i < sizeof( $meta_array ); $i++ ){
 			$meta = get_post_meta( $order_id, $meta_array[$i], true );
 			if( !empty( $meta ) ){
 				$order_meta[ $meta_array[$i] ] = $meta;
 			}
 		} 		
 		return apply_filters( "wc_order_export_add_extra_meta_key_val", $order_meta, $order_id ); 		
 	} 	
 	
 	/*
 	 * get order meta for perticular orders
 	 */
 	function wcexord_export_order_meta_custom( $order_id ){
 		global $wpdb;
 		$order_item_data = $wpdb->get_results( "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '".$order_id."' AND order_item_type = 'line_item' " )[0]; 
 		$order_item_id = $order_item_data->order_item_id;
 		$custom_meta_data_fields = array();
 		$custom_meta_data_fields[ "_product_name" ] = $order_item_data->order_item_name;
 		$order_item_meta_data = $wpdb->get_results( "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '".$order_item_id."'" );
 		
 		for( $i = 0; $i < sizeof( $order_item_meta_data ); $i++ ){
 			$meta_key = $order_item_meta_data[$i]->meta_key;
 			if(  $meta_key != "_variation_id" && $meta_key != "_product_id" && $meta_key != "_tax_class" && $meta_key != "_line_tax_data" ){
 				$custom_meta_data_fields[ $meta_key ] = $order_item_meta_data[$i]->meta_value;
 			}
 		} 		
 		return apply_filters( "wc_order_export_add_extra_custon_meta_key_val", $custom_meta_data_fields, $order_item_id, $order_id );
 	}
 	
 	
 	
 }
 
 new wcexord_exporter();


?>