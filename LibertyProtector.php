<?php
/**
 * @version $Header: /cvsroot/bitweaver/_bit_protector/LibertyProtector.php,v 1.2 2006/02/10 10:34:26 lsces Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: LibertyProtector.php,v 1.2 2006/02/10 10:34:26 lsces Exp $
 * @package protector
 */

/**
 * required setup
 */
require_once( LIBERTY_PKG_PATH.'LibertyBase.php' );

/**
 * Protector class to illustrate best practices when creating a new bitweaver package that
 * builds on core bitweaver functionality, such as the Liberty CMS engine
 *
 * @package protector
 * @subpackage LibertyProtector
 *
 * created 2004/8/15
 *
 * @author spider <spider@steelsun.com>
 *
 * @version $Revision: 1.2 $ $Date: 2006/02/10 10:34:26 $ $Author: lsces $
 */
class LibertyProtector extends LibertyBase {
    /**
    * During initialisation, be sure to call our base constructors
	**/
	function LibertyProtector( $pContentId=NULL ) {
		$this->mContentId = $pContentId;
		LibertyBase::LibertyBase();
	}

	function storeProtection( &$pParamHash ) {
		if( isset( $pParamHash['protector_group'] ) ) {
			$this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_group_map` WHERE `content_id`=?", array( $pParamHash['content_id'] ) );
			foreach( $pParamHash['protector_group'] AS $groupId ) {
				$this->mDb->query( "INSERT INTO `".BIT_DB_PREFIX."liberty_content_group_map` ( `group_id`, `content_id` ) VALUES ( ?, ? )", array( $groupId, $pParamHash['content_id'] ) );
			}
		}
		return( count( $this->mErrors ) == 0 );
	}

}

function protector_content_list() {
	global $gBitUser;
	$ret = array(
		'join_sql' => " LEFT JOIN `".BIT_DB_PREFIX."liberty_content_group_map` lcgm ON ( lc.`content_id`=lcgm.`content_id` ) LEFT OUTER JOIN `".BIT_DB_PREFIX."users_groups_map` ugm ON ( ugm.`group_id`=lcgm.`group_id` ) ",
		'where_sql' => " AND (lcgm.`content_id` IS NULL OR lcgm.`group_id` = ".ANONYMOUS_GROUP_ID." OR ugm.`user_id`=?) ",
		'bind_vars' => array( $gBitUser->mUserId ),
	);
	return $ret;
}

function protector_content_load() {
	global $gBitUser;
	$ret = array(
		'join_sql' => " LEFT JOIN `".BIT_DB_PREFIX."liberty_content_group_map` lcgm ON ( lc.`content_id`=lcgm.`content_id` ) LEFT OUTER JOIN `".BIT_DB_PREFIX."users_groups_map` ugm ON ( ugm.`group_id`=lcgm.`group_id` ) ",
		'where_sql' => " AND (lcgm.`content_id` IS NULL OR lcgm.`group_id` = ".ANONYMOUS_GROUP_ID." OR ugm.`user_id`=?) ",
		'bind_vars' => array( $gBitUser->mUserId ),
	);
	return $ret;
}

function protector_content_store( &$pObject, &$pParamHash ) {
	global $gBitSystem, $gProtector;
	$errors = NULL;
	// If a content access system is active, let's call it
	if( $gBitSystem->isPackageActive( 'protector' ) ) {
		if( !$gProtector->storeProtection( $pParamHash ) ) {
			$errors['protector'] = $gProtector->mErrors['security'];
		}
	}
	return( $errors );
}

function protector_content_display( &$pContent, &$pParamHash ) {
	global $gBitSystem, $gBitSmarty;
	$pContent->hasUserPermission( $pParamHash['perm_name'] );
}

function protector_content_verify_access( &$pContent, &$pHash ) {
	global $gBitUser, $gBitSystem;
	$error = NULL;
	return $error;
}

function protector_content_edit( &$pContent ) {
	global $gProtector, $gBitUser, $gBitSmarty;
	$groups = $gBitUser->getGroups();
	$gBitSmarty->assign( 'protectorGroupsId', array_keys( $groups ) );
	$gBitSmarty->assign( 'protectorGroupsDesc', array_values( $groups ) );
	$gBitSmarty->assign( 'protectorGroups', $gBitUser->getGroups() );
}

global $gProtector;
$gProtector = new LibertyProtector();

?>
