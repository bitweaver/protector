<?php
/**
 * @version $Header$
 */
global $gBitInstaller;

$infoHash = array(
	'package'      => PROTECTOR_PKG_NAME,
	'version'      => str_replace( '.php', '', basename( __FILE__ )),
	'description'  => "This upgrade renames the liberty_content_group_map to liberty_content_role_map in line with renaming groups to roles in users.",
	'post_upgrade' => NULL,
);
$gBitInstaller->registerPackageUpgrade( $infoHash, array(

array( 'DATADICT' => array(
	array( 'RENAMETABLE' => array(
		'liberty_content_group_map' => 'liberty_content_role_map',
	)),
))

/**
 * This needs to be expanded to take care of name changes in fields and constraints
 * Probably best to create new table with correct structure,copy content, and then delete original
 * Need to complete change of users_groups tables
 */

));
?>
