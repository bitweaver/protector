<?php
global $gBitSystem, $gBitSmarty;
$gBitSystem->registerPackage( 'protector', dirname( __FILE__).'/', TRUE, LIBERTY_SERVICE_ACCESS_CONTROL );

require_once( PROTECTOR_PKG_PATH.'LibertyProtector.php' );

if( $gBitSystem->isPackageActive( 'protector' ) ) {
	$gLibertySystem->registerService( LIBERTY_SERVICE_ACCESS_CONTROL, PROTECTOR_PKG_NAME, array(
		'content_display_function' => 'protector_content_display',
		'content_edit_function' => 'protector_content_edit',
		'content_store_function' => 'protector_content_store',
		'content_list_function' => 'protector_content_list',
		'content_load_function' => 'protector_content_load',
		'content_edit_mini_tpl' => 'bitpackage:protector/choose_protection.tpl',
		'content_icon_tpl' => 'bitpackage:protector/protector_service_icon.tpl',
	) );
}
?>
