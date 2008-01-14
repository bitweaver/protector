<?php

$tables = array(

'liberty_content_group_map' => "
	content_id I4 PRIMARY,
	group_id I4 PRIMARY
	CONSTRAINT	', CONSTRAINT `protector_group_ref` FOREIGN KEY (`group_id`) REFERENCES `".BIT_DB_PREFIX."users_groups` (`group_id`)
				 , CONSTRAINT `protector_content_ref` FOREIGN KEY (`content_id`) REFERENCES `".BIT_DB_PREFIX."liberty_content` (`content_id`)'
",

);

global $gBitInstaller;

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( PROTECTOR_PKG_NAME, $tableName, $tables[$tableName] );
}

$gBitInstaller->registerPackageInfo( PROTECTOR_PKG_NAME, array(
	'description' => "Protector restricts access to content based on groups.",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
) );

// ### Indexes
//$indices = array (
//);
//$gBitInstaller->registerSchemaIndexes( PROTECTOR_PKG_NAME, $indices );

// ### Sequences
//$sequences = array (
//	'protector_security_id_seq' => array( 'start' => 1 ) 
//);
//$gBitInstaller->registerSchemaSequences( PROTECTOR_PKG_NAME, $sequences );

// ### Default UserPermissions
$gBitInstaller->registerUserPermissions( PROTECTOR_PKG_NAME, array(
	array('bit_p_create_protector', 'Can create a protector', 'registered', PROTECTOR_PKG_NAME),
	array('bit_p_protector_edit', 'Can edit any protector', 'editors', PROTECTOR_PKG_NAME),
	array('bit_p_protector_admin', 'Can admin protector', 'editors', PROTECTOR_PKG_NAME),
	array('bit_p_read_protector', 'Can read protector', 'basic', PROTECTOR_PKG_NAME),
) );

// ### Default Preferences
$gBitInstaller->registerPreferences( PROTECTOR_PKG_NAME, array(
	array(PROTECTOR_PKG_NAME, 'protector_default_ordering','title_desc'),
	array(PROTECTOR_PKG_NAME, 'protector_list_content_id','y'),
	array(PROTECTOR_PKG_NAME, 'protector_list_title','y'),
	array(PROTECTOR_PKG_NAME, 'protector_list_description','y'),
	array(PROTECTOR_PKG_NAME, 'protector_single_group','y'),
) );
?>
