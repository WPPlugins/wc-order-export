/**
 * @author  	: Saravana Kumar K, sarkparanjothi
 * @author url 	: http://iamsark.com
 * @url			: http://sarkware.com/
 * @copyrights	: SARKWARE
 * @purpose 	: WC ORDER EXPORT
 */
var $ = jQuery;
var return_page = "";
$( document ).ready( function(){	
	var wcexord_order_export = new wcexord_order_export_obj();
	$( ".export_start_date" ).datepicker({ dateFormat: 'dd-mm-yy' });
	$( ".export_end_date" ).datepicker({ dateFormat: 'dd-mm-yy' });
	$( document ).on( "click", "#export-order-button", function(){
		var status_check = $( '.wc_order_status' );
		var status = "wc-all";
		if( !$( ".all_oder_status_selected" ).is( ":checked" ) && $( '.wc_order_status' ).is( ":checked" ) ){
			status = [];
			for ( var i = 0; i < status_check.length; i++ ){
				select_single = $(  status_check[i] );
				if( select_single.is( ":checked" ) &&  select_single.val() != "wc-all" ){
					status.push( select_single.val() );
				}
			}
			status = JSON.stringify( status );
		}
		var data = { "start_date" : $( "input[name=export_start_date]" ).val(), "end_date" : $( "input[name=export_end_date]" ).val(), "order_status" : status, "file_type" : $( "input[name=export_file_type]:checked" ).val(), "file_name" : $( "input[name=download_file_name]" ).val() };
		wcexord_order_export.conn(  "export_order", data );
	});
	
	$( document ).on( "change", ".all_oder_status_selected", function(){
		if( $( this ).is( ":checked" ) ){
			$( '.wc_order_status' ).parent().hide();
			$( this ).parent().show();
		} else {
			$( '.wc_order_status' ).parent().show();
		}
	});
	
	if( $( ".order-export-option-select" ).length != 0 ){
		wcexord_order_export.conn(  "get_order_status", { "data" : "get_order_data" } );
	}
});


var wcexord_order_export_obj = function(){
	this.ajaxFlaQ = true;
	this.conn = function( _action, _payload ) {	
		_payload[ "action" ] = _action;
		var me = this;
		/* see the ajax handler is free */
		if( !this.ajaxFlaQ ) {
			return;
		}	
		
		$.ajax({  
			type       : "POST",  
			data       : { action : "wcexord_exporter", payload : _payload },  
			dataType   : "json",  
			url        : wcexord_ajax_url,  
			responseType : 'blob',
			beforeSend : function(){  				
				/* enable the ajax lock - actually it disable the dock */
				me.ajaxFlaQ = false;				
			},  
			success    : function(data) {				
				/* disable the ajax lock */
				me.ajaxFlaQ = true;				
				/* handle the response and route to appropriate target */
				/*me.responseHandler( _action, data );*/
				me.responseHandler( _action, data );
										
			},  
			error      : function(jqXHR, textStatus, errorThrown) {                    
				/* disable the ajax lock */
				me.ajaxFlaQ = true;
				
			},
			complete   : function(dsad) {
				
			}   
		});
	};
	
	this.responseHandler = function( _action, data ){
		if( !data.status && typeof data.data != "undefined" ){
			if( $( ".wc-order-export-error" ).length == 0 ) {
				$( "#wpbody #wpbody-content" ).prepend( '<div class="error wc-order-export-error"><p>'+ data.data +'</p></div>' );
			}
		} else {
			$( ".wc-order-export-error" ).remove();
			if( _action == "export_order" ){
				if( data.type == "xlsx" || data.type == "csv" ){
				 var $a = $( "<a>" );
				    $a.attr( "href", data.data );
				    $( "body" ).append( $a );
				    $a.attr( "download", $( ".download_file_name" ).val()+"."+data.type );
				    $a[0].click();
				    $a.remove();
				} else if ( data.type == "json" ) {
					var textFile = null;
					 var json_data = new Blob( [ data.data ] , { type: 'text/plain'} );
					    if ( textFile !== null ) {
					      window.URL.revokeObjectURL( textFile );
					    }
					    textFile = window.URL.createObjectURL( json_data );
					    var $a = $( "<a>" );    
					    $a.attr( "href", textFile );
					    $( "body" ).append( $a );
					    $a.attr( "download", $( ".download_file_name" ).val()+".json" );
					    $a[0].click();
					    $a.remove();
				}
			} else if( _action == "get_order_status" ){
				$( ".order-export-option-select" ).append( data.data );
			}
		}
	};
	
	
		
};
