<?php 
/**
 * @author 		: Saravana Kumar K, sarkparanjothi
 * @author url  : iamsark.com
 * @copyright	: sarkware.com
 * One of the core module, of genarate order file
 *
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if( !class_exists( 'PHPExcel' ) ){
	require_once( 'PHPExcel.php' );
}

class wcexord_genarate_export_order {
	
	var $row_count;
	
	function __construct(){
		$this->row_count = 2;
		add_action( 'wp_ajax_wcexord_exporter', array( $this, 'wcexord_trigger_call' ) );
		add_action( 'wp_ajax_nopriv_wcexord_exporter', array( $this, 'wcexord_trigger_call' ));
		add_filter( 'export/genarate_excel', array( $this, 'wcexord_genarate_file_data' ) );
		add_filter( 'export/get_order_status', array( $this, 'wcexord_get_order_status' ) );
	}
	
	/*
	 * Commaon ajax request farmetter
	 */
	function wcexord_trigger_call(){
		if( isset( $_POST["action"] ) ){
			if( isset( $_POST[ "payload" ] ) && filter_var( $_POST['action'], FILTER_SANITIZE_STRING ) ){					
				$payload = $_POST[ "payload" ];
				$request[ "action" ]  = $payload[ "action" ];
				$request[ "payload" ] = $payload;
				if( filter_var( $request[ "payload" ][ "action" ], FILTER_SANITIZE_STRING ) && filter_var( json_encode( $request ), FILTER_UNSAFE_RAW ) ){
					$this->wcexord_call_devider( $request );
				}
			}
		}
	}
	
	
	/*
	 * Commaon ajax controller
	 */
	function wcexord_call_devider( $request ){
		if( isset( $request ) ){
			if( $request[ "action" ] == "export_order" ) {				
				if( $request["payload"][ "file_type" ] != "" && strlen( $request["payload"][ "end_date" ] ) == 10 && strlen( $request["payload"][ "start_date" ] ) && $request["payload"][ "file_name" ] != "" ){
					$res = apply_filters( 'export/genarate_excel', $request[ "payload" ] );	
				} else {
					$res = array( "status" => false, "data" => "something wrong please try again" );
				}
			} else if( $request[ "action" ] == "get_order_status" ){
				$res = apply_filters( 'export/get_order_status', $request[ "payload" ] );
			}
			echo json_encode( $res );
			die();
		}
	}
	
	function wcexord_get_order_status(){
		
		$status = wc_get_order_statuses();		
		ob_start();
		$option = "";
		foreach ( $status as $key => $value ){
			$option .= '<label><input class="wc_order_status" type="checkbox" value="'.$key.'"> '.$value.'</label>';
		}		
		ob_end_clean();
		return array( "status" => true, "data" => $option );
	}
	
	/*
	 * Write excel data
	 */
	function wcexord_header_writer(  $header ){		
		Global $objPHPExcel;			
		foreach( $header as $key => $value ){
			$header_exist = false;
			$header_title = trim( ucwords( str_replace( "_", " ", $key ) ), " " );
			$heter_to_contend_map = "";
			$get_col_index = PHPExcel_Cell::columnIndexFromString( $objPHPExcel->setActiveSheetIndex(0)->getHighestDataColumn() );
			// check If header exist
			for( $i = 0; $i < $get_col_index; $i++ ){		
				if( !empty( PHPExcel_Cell::stringFromColumnIndex( $i ) ) ){					
					if( $objPHPExcel->getActiveSheet()->getCell( PHPExcel_Cell::stringFromColumnIndex( $i ).'1' )->getValue() == $header_title  && !$header_not_exist ){
						$header_exist = true;						
						$heter_to_contend_map = PHPExcel_Cell::stringFromColumnIndex( $i );						
					}			
				}	
			} 
			
			// If exist header add content only
			$reender_val = false;
			if( $header_exist ) {		
				$objPHPExcel->setActiveSheetIndex(0)->setCellValue( $heter_to_contend_map.$this->row_count,  $value );
			} else {
				$reender_val = true;
			}
			
			// If not exist header add header and content
			if( $reender_val ){
				$get_col_index_last = PHPExcel_Cell::stringFromColumnIndex( $get_col_index );
				$objPHPExcel->setActiveSheetIndex(0)->setCellValue( $get_col_index_last."1",  $header_title  );
				$objPHPExcel->setActiveSheetIndex(0)->setCellValue( $get_col_index_last.$this->row_count,  $value );
			}
		}
		$this->row_count++;
	}
	
	/*	
	 * Write file data
	 */
	function wcexord_genarate_file_data( $payload ){
		Global $objPHPExcel;		
		$data  = apply_filters( "wc_order_export/exporter_data", $payload );		
		$filterd_data = array();		
		if( sizeof( $data ) != 0 ){
			for( $i = 0; $i < sizeof( $data ); $i++ ){
				$filterd_data[$i] = array();
				foreach ( $data[$i] as $key => $value ) {	
					$filterd_data[$i]["post_status"] = $data[$i]->post_status;
					$filterd_data[$i]["post_date"]	= $data[$i]->post_date;
					foreach( $data[$i]->order_metas as $key => $value) {
						$filterd_data[$i][$key] = $value;
					}
					foreach( $data[$i]->order_custom_metas as $key => $value ) {
						$filterd_data[$i][$key] = $value;
					}							
				}
			}
		} else {
			return array( 'status' => false, 'data' => "No order found you selected period." );
		}
		if( $payload[ "file_type" ] != "json" ){
			$objPHPExcel = new PHPExcel();
			// Set document properties
			$objPHPExcel->getProperties()->setCreator("wc order export")->setLastModifiedBy("wc order export")->setTitle("wc order export")->setSubject("wc order export")->setDescription("wc order export.")->setKeywords("wc order export")->setCategory("wc order export");
			
			// write excel file from $filterd_data
			$objPHPExcel->getActiveSheet()->getStyle('A1:XFD1')->getFont()->setBold( true );
			for( $j = 0; $j < sizeof( $filterd_data ); $j++ ){
				$this->wcexord_header_writer( $filterd_data[ $j ] );			
			}			
			
			// Rename worksheet
			$objPHPExcel->getActiveSheet()->setTitle( 'wc_order_export' );			
			
			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel->setActiveSheetIndex(0);
			if( $payload[ "file_type" ] == "xlsx" ){
				// Set excel headers
				header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );				
				$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
			} else if( $payload[ "file_type" ] == "csv" ){
				header('Content-type: text/csv');
				$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'CSV' );
			}
			header( 'Content-Disposition: attachment;filename="wc_order_export.'.$payload[ "file_type" ].'"' );
			header( 'Cache-Control: max-age=0' );
			header( 'Cache-Control: max-age=1' );
			header( 'Last-Modified: '.gmdate( 'D, d M Y H:i:s' ).' GMT' );
			header( 'Cache-Control: cache, must-revalidate' );
			header( 'Pragma: public' );				
			
	 		ob_start();
	 		$objWriter->save('php://output');
	 		$xlsData = ob_get_contents();
	 		ob_end_clean();
	 		$response =  array( 'status' => true, 'data' => "data:application/vnd.ms-excel;base64,".base64_encode( $xlsData ), "type" => $payload[ "file_type" ] );
	 		return $response;
		} else if( $payload[ "file_type" ] == "json" ){			
			return array( 'status' => true, 'data' => json_encode( $filterd_data ), "type" => "json" );
		}
 	}
 	

}
new wcexord_genarate_export_order();
?>