<?php
/**
 * @version $Header: /cvsroot/bitweaver/_bit_protector/LibertyProtector.php,v 1.12 2009/10/01 13:45:46 wjames5 Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * $Id: LibertyProtector.php,v 1.12 2009/10/01 13:45:46 wjames5 Exp $
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
 * @version $Revision: 1.12 $ $Date: 2009/10/01 13:45:46 $ $Author: wjames5 $
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
		if( @BitBase::verifyId( $pParamHash['protector']['group_id'] ) ) {
			$this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_group_map` WHERE `content_id`=?", array( $pParamHash['content_id'] ) );
			if( $gBitSystem->isFeatureActive( 'protector_single_group' ) ) {
				if( $pParamHash['protector']['group_id'] != -1 )
					$this->mDb->query( "INSERT INTO `".BIT_DB_PREFIX."liberty_content_group_map` ( `group_id`, `content_id` ) VALUES ( ?, ? )", array( $pParamHash['protector']['group_id'], $pParamHash['content_id'] ) );
			} else {
				foreach( $pParamHash['protector']['group_id'] AS $groupId ) {
					if( $groupId != -1 )
					$this->mDb->query( "INSERT INTO `".BIT_DB_PREFIX."liberty_content_group_map` ( `group_id`, `content_id` ) VALUES ( ?, ? )", array( $groupId, $pParamHash['content_id'] ) );
				}
			}
		}
		return( count( $this->mErrors ) == 0 );
	}

    /**
    * Delete entry(ies) from liberty_content_group_map table with content_id
	**/
	function expunge( $ContentId=NULL ) {
		$ret = FALSE;
		if( @BitBase::verifyId( $ContentId ) ) {
			$this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_group_map` WHERE `content_id`=?", array( $ContentId ) );
		}
		return $ret;
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

function protector_content_load( &$pContent = NULL ) {
	global $gBitUser;

	$groups = array_keys($gBitUser->mGroups);
	protector_content_verify_access( $pContent, $groups );
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

function protector_content_expunge( &$pContent = NULL ) {
		if( @BitBase::verifyId( $pContent->mContentId ) ) {
			$pContent->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_group_map` WHERE `content_id`=?", array( $pContent->mContentId ) );
		}
}

function protector_content_display( &$pContent, &$pParamHash ) {
	global $gBitSystem, $gBitSmarty;
	$pContent->hasUserPermission( $pParamHash['perm_name'] );
}

function protector_content_verify_access( &$pContent, &$pHash ) {
	global $gBitUser, $gBitSystem;

	$error = NULL;
	if( !$pContent->verifyId( $pContent->mContentId ) ) {
// vd($pContent);
// need to get ContentId if not set
	}
	if( $pContent->verifyId( $pContent->mContentId ) ) {
		$query = "SELECT lc.`content_id`, lcgm.`group_id` as `is_protected`
			FROM `".BIT_DB_PREFIX."liberty_content` lc 
			LEFT JOIN `".BIT_DB_PREFIX."liberty_content_group_map` lcgm ON ( lc.`content_id`=lcgm.`content_id` ) LEFT OUTER JOIN `".BIT_DB_PREFIX."users_groups_map` ugm ON ( ugm.`user_id`=".$gBitUser->mUserId." ) AND ( ugm.`group_id`=lcgm.`group_id` ) 
			WHERE lc.`content_id` = ?";
		$ret = $pContent->mDb->getRow( $query, array( $pContent->mContentId ) );
		if( $ret and is_numeric($ret['is_protected']) and !in_array( $ret['is_protected'], $pHash ) ) {
			$gBitSystem->fatalError( tra( 'You do not have permission to access this '.$pContent->mType['content_description'] ), 'error.tpl', tra( 'Permission denied.' ) );
		}
	}
	return $error;
}

function protector_content_edit( &$pContent ) {
	global $gProtector, $gBitUser, $gBitSmarty;
	$groups = $gBitUser->getGroups();
	$groups[-1]['group_name'] = "~~ System Default ~~";
	ksort( $groups );
	foreach( array_keys( $groups ) as $groupId ) {
		if( $groupId != -1 ) {
			$protectorGroupsId[$groupId] = $groups[$groupId]['group_name'];
		} else {
			$protectorGroupsId[$groupId] = "~~ System Default ~~";
		}
	}
	$serviceHash['protector']['group'] = $gProtector->getProtectionList( $pContent->mContentId );
	$prot = array_keys( $serviceHash['protector']['group'] );
	$serviceHash['protector']['group_id'] = ( empty( $prot[0] ) ? -1 : $prot[0] );
	$gBitSmarty->assign_by_ref( 'serviceHash', $serviceHash );
	$gBitSmarty->assign_by_ref( 'protectorGroupsId', $protectorGroupsId );
	$gBitSmarty->assign_by_ref( 'protectorGroups', $groups );
}

global $gProtector;
$gProtector = new LibertyProtector();

?>
