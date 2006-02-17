<?php
global $gBitSystem, $gBitSmarty;

$registerHash = array(
	'package_name' => 'protector',
	'package_path' => dirname( __FILE__ ).'/',
	'service' => LIBERTY_SERVICE_ACCESS_CONTROL,
);
$gBitSystem->registerPackage( $registerHash );

if( $gBitSystem->isPackageActive( 'protector' ) ) {
	require_once( PROTECTOR_PKG_PATH.'LibertyProtector.php' );

	$gLibertySystem->registerService( LIBERTY_SERVICE_ACCESS_CONTROL, PROTECTOR_PKG_NAME, array(
		'content_display_function' => 'protector_content_display',
		'content_preview_function' => 'protector_content_edit',
		'content_edit_function' => 'protector_content_edit',
		'content_store_function' => 'protector_content_store',
		'content_list_sql_function' => 'protector_content_list',
		'content_load_sql_function' => 'protector_content_load',
		'content_edit_mini_tpl' => 'bitpackage:protector/choose_protection.tpl',
		'content_icon_tpl' => 'bitpackage:protector/protector_service_icon.tpl',
	) );
}
?>
