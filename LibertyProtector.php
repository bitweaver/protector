<?php
/**
 * @version $Header: /cvsroot/bitweaver/_bit_protector/LibertyProtector.php,v 1.6 2006/11/01 12:59:23 lsces Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: LibertyProtector.php,v 1.6 2006/11/01 12:59:23 lsces Exp $
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
 * @version $Revision: 1.6 $ $Date: 2006/11/01 12:59:23 $ $Author: lsces $
 */
class LibertyProtector extends LibertyBase {
    /**
    * During initialisation, be sure to call our base constructors
	**/
	function LibertyProtector( $pContentId=NULL ) {
		$this->mContentId = $pContentId;
		LibertyBase::LibertyBase();
	}

    /**
    * Update the liberty_content_group_map table with corrected group_id(s)
    * In -1 for anonymouse is not stored, switching content to anonymouse will clear array 
	**/
	function storeProtection( &$pParamHash ) {
		global $gBitSystem;
		if( isset( $pParamHash['protector_group'] ) ) {
			$this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_group_map` WHERE `content_id`=?", array( $pParamHash['content_id'] ) );
			if( $gBitSystem->isFeatureActive( 'protector_single_group' ) ) {
				if ( $pParamHash['protector_group'] != -1 )
					$this->mDb->query( "INSERT INTO `".BIT_DB_PREFIX."liberty_content_group_map` ( `group_id`, `content_id` ) VALUES ( ?, ? )", array( $pParamHash['protector_group'], $pParamHash['content_id'] ) );
			} else {
				foreach( $pParamHash['protector_group'] AS $groupId ) {
					if ( $groupId != -1 )
					$this->mDb->query( "INSERT INTO `".BIT_DB_PREFIX."liberty_content_group_map` ( `group_id`, `content_id` ) VALUES ( ?, ? )", array( $groupId, $pParamHash['content_id'] ) );
				}
			}
		}
		return( count( $this->mErrors ) == 0 );
	}

    /**
    * Return liberty_content_group_map for selected content_id
    * Ret -1 for anonymouse if alternatives are not stored
	**/
	function getProtectionList( $ContentId=NULL ) {
		global $gBitSystem;
		$ret = array( '-1' <= $ContentId );
		if( isset( $ContentId ) ) {
			$ret = $this->mDb->GetAssoc( "SELECT `group_id`, `content_id` FROM `".BIT_DB_PREFIX."liberty_content_group_map` WHERE `content_id`=?", array( $ContentId ) );
		}
		return $ret;
	}
}

function protector_content_list() {
	global $gBitUser;
	$groups = array_keys($gBitUser->mGroups);
	$ret = array(
		'join_sql' => " LEFT JOIN `".BIT_DB_PREFIX."liberty_content_group_map` lcgm ON ( lc.`content_id`=lcgm.`content_id` ) LEFT OUTER JOIN `".BIT_DB_PREFIX."users_groups_map` ugm ON ( ugm.`user_id`=".$gBitUser->mUserId." ) AND ( ugm.`group_id`=lcgm.`group_id` ) ",
		'where_sql' => " AND (lcgm.`content_id` IS NULL OR lcgm.`group_id` IN(". implode(',', array_fill(0, count($groups), '?')) ." ) OR ugm.`user_id`=?) ",
		'bind_vars' => array_merge( $groups, array( $gBitUser->mUserId ) ),
	);
//	$ret['bind_vars'] = array_merge( $groups, array( $gBitUser->mUserId ) );
	return $ret;
}

function protector_content_load() {
	global $gBitUser;
	$groups = array_keys($gBitUser->mGroups);
	$ret = array(
		'join_sql' => " LEFT JOIN `".BIT_DB_PREFIX."liberty_content_group_map` lcgm ON ( lc.`content_id`=lcgm.`content_id` ) LEFT OUTER JOIN `".BIT_DB_PREFIX."users_groups_map` ugm ON ( ugm.`group_id`=lcgm.`group_id` ) ",
		'where_sql' => " AND (lcgm.`content_id` IS NULL OR lcgm.`group_id` IN(". implode(',', array_fill(0, count($groups), '?')) ." ) OR ugm.`user_id`=?) ",
		'bind_vars' => array( $gBitUser->mUserId ),
	);
	$ret['bind_vars'] = array_merge( $groups, $ret['bind_vars'] );
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
	foreach( array_keys( $groups ) as $groupId ) {
		$protectorGroupsId[$groupId] = $groups[$groupId]['group_name'];
	}
	$serviceHash['protector_group'] = $gProtector->getProtectionList( $pContent->mContentId );
	$prot = array_keys($serviceHash['protector_group']);
	$serviceHash['protector_group_id'] = (empty($prot[0])? -1 : $prot[0]);
	$gBitSmarty->assign_by_ref( 'serviceHash', $serviceHash );
	$gBitSmarty->assign_by_ref( 'protectorGroupsId', $protectorGroupsId );
	$gBitSmarty->assign_by_ref( 'protectorGroups', $groups );
}

global $gProtector;
$gProtector = new LibertyProtector();

?>
