<?php
/**
 * protector package limits content based on user role
 *
 * @copyright (c) 2004-15 bitweaver.org
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
 */
class LibertyProtector extends LibertyBase {
    /**
    * During initialisation, be sure to call our base constructors
	**/
	function __construct( $pContentId=NULL ) {
		$this->mContentId = $pContentId;
		parent::__construct();
	}

    /**
    * Update the liberty_content_role_map table with corrected role_id(s).
    * 
    * In -1 for anonymouse is not stored, switching content to anonymouse will clear array
    *  
    * @param object $pParamHash
	*/
	function storeProtection( &$pParamHash ) {
		global $gBitSystem;
		if( @BitBase::verifyId( $pParamHash['protector']['role_id'] ) ) {
			$this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", array( $pParamHash['content_id'] ) );
			if( $gBitSystem->isFeatureActive( 'protector_single_role' ) ) {
				if( $pParamHash['protector']['role_id'] != -1 )
					$this->mDb->query( "INSERT INTO `".BIT_DB_PREFIX."liberty_content_role_map` ( `role_id`, `content_id` ) VALUES ( ?, ? )", array( $pParamHash['protector']['role_id'], $pParamHash['content_id'] ) );
			} else {
				foreach( $pParamHash['protector']['role_id'] AS $roleId ) {
					if( $roleId != -1 )
					$this->mDb->query( "INSERT INTO `".BIT_DB_PREFIX."liberty_content_role_map` ( `role_id`, `content_id` ) VALUES ( ?, ? )", array( $roleId, $pParamHash['content_id'] ) );
				}
			}
		}
		return( count( $this->mErrors ) == 0 );
	}

    /**
    * Delete entry(ies) from liberty_content_role_map table with content_id.
    * 
    * @param object $pContent 
	*/
	function expunge( $ContentId=NULL ) {
		$ret = FALSE;
		if( @BitBase::verifyId( $ContentId ) ) {
			$this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", array( $ContentId ) );
		}
		return $ret;
	}

    /**
    * @return liberty_content_role_map for selected content_id
    * Ret -1 for anonymouse if alternatives are not stored
	**/
	function getProtectionList( $ContentId=NULL ) {
		global $gBitSystem;
		$ret = array( '-1' <= $ContentId );
		if( isset( $ContentId ) ) {
			$ret = $this->mDb->GetAssoc( "SELECT `role_id`, `content_id` FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", array( $ContentId ) );
		}
		return $ret;
	}
}

/**
* function to provide list of filtered content
**/
function protector_content_list() {
	global $gBitUser;
	$roles = array_keys($gBitUser->mRoles);
	$ret = array(
		'join_sql' => " LEFT JOIN `".BIT_DB_PREFIX."liberty_content_role_map` lcrm ON ( lc.`content_id`=lcrm.`content_id` ) LEFT OUTER JOIN `".BIT_DB_PREFIX."users_roles_map` purm ON ( purm.`user_id`=".$gBitUser->mUserId." ) AND ( purm.`role_id`=lcrm.`role_id` ) ",
		'where_sql' => " AND (lcrm.`content_id` IS NULL OR lcrm.`role_id` IN(". implode(',', array_fill(0, count($roles), '?')) ." ) OR purm.`user_id`=?) ",
		'bind_vars' => array_merge( $roles, array( $gBitUser->mUserId ) ),
	);
	return $ret;
}

/**
 * function to load a filtered content element
 * 
 * @param object $pContent 
 */
function protector_content_load( &$pContent = NULL ) {
	global $gBitUser;

	$roles = array_keys($gBitUser->mRoles);
	protector_content_verify_access( $pContent, $roles );
	$ret = array(
		'join_sql' => " LEFT JOIN `".BIT_DB_PREFIX."liberty_content_role_map` lcrm ON ( lc.`content_id`=lcrm.`content_id` ) LEFT OUTER JOIN `".BIT_DB_PREFIX."users_roles_map` purm ON ( purm.`role_id`=lcrm.`role_id` ) ",
		'where_sql' => " AND (lcrm.`content_id` IS NULL OR lcrm.`role_id` IN(". implode(',', array_fill(0, count($roles), '?')) ." ) OR purm.`user_id`=?) ",
		'bind_vars' => array( $gBitUser->mUserId ),
	);
	$ret['bind_vars'] = array_merge( $roles, $ret['bind_vars'] );
	return $ret;
}

/**
* function to store a filtered content element
* 
* @param object $pObject
* @param array $pParamHash 
**/
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

/**
* function to store a filtered comment element
* 
* @param object $pContent
* @param array $pParamHash 
**/
function protector_comment_store( &$pContent, &$pParamHash ) {
	global $gBitSystem, $gProtector;
	$errors = NULL;
	// If a content access system is active, let's call it
	if( $gBitSystem->isPackageActive( 'protector' ) ) {
		if( isset( $pParamHash['comments_parent_id'] ) ) {
			$pParamHash['protector']['role_id'] = $pContent->mDb->GetOne( "SELECT `role_id` FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", array( $pParamHash['comments_parent_id'] ) );
		}
		if( !$gProtector->storeProtection( $pParamHash ) ) {
			$errors['protector'] = $gProtector->mErrors['security'];
		}
	}
	return( $errors );
}

/**
* function to delete a filtered content element
* 
* @param object $pContent
* @param array $pParamHash 
**/
function protector_content_expunge( &$pContent = NULL ) {
		if( @BitBase::verifyId( $pContent->mContentId ) ) {
			$pContent->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", array( $pContent->mContentId ) );
		}
}

/**
* function to display a filtered content element
* 
* @param object $pContent
* @param array $pParamHash 
**/
function protector_content_display( &$pContent, &$pParamHash ) {
	global $gBitSystem, $gBitSmarty;
	$pContent->hasUserPermission( $pParamHash['perm_name'] );
}

/**
* function to verify access to a filtered content element
* 
* @param object $pContent
* @param array $pHash 
**/
function protector_content_verify_access( &$pContent, &$pHash ) {
	global $gBitUser, $gBitSystem;

	$error = NULL;
	if( !$pContent->verifyId( $pContent->mContentId ) ) {
// vd($pContent);
// need to get ContentId if not set
	}
	if( $pContent->verifyId( $pContent->mContentId ) ) {
		$query = "SELECT lc.`content_id`, lcrm.`role_id` as `is_protected`
			FROM `".BIT_DB_PREFIX."liberty_content` lc 
			LEFT JOIN `".BIT_DB_PREFIX."liberty_content_role_map` lcrm ON ( lc.`content_id`=lcrm.`content_id` ) LEFT OUTER JOIN `".BIT_DB_PREFIX."users_roles_map` urm ON ( urm.`user_id`=".$gBitUser->mUserId." ) AND ( urm.`role_id`=lcrm.`role_id` ) 
			WHERE lc.`content_id` = ?";
		$ret = $pContent->mDb->getRow( $query, array( $pContent->mContentId ) );
		if( $ret and is_numeric($ret['is_protected']) and !in_array( $ret['is_protected'], $pHash ) ) {
			$gBitSystem->fatalError( tra( 'You do not have permission to access this '.$pContent->getContentTypeName() ), 'error.tpl', tra( 'Permission denied.' ) );
		}
	}
	return $error;
}

/**
* function to edit a filtered content element
* 
* @param object $pContent
**/
function protector_content_edit( &$pContent ) {
	global $gProtector, $gBitUser, $gBitSmarty;
	$roles = $gBitUser->getRoles();
	$roles[-1]['role_name'] = "~~ System Default ~~";
	ksort( $roles );
	foreach( array_keys( $roles ) as $roleId ) {
		if( $roleId != -1 ) {
			$protectorRolesId[$roleId] = $roles[$roleId]['role_name'];
		} else {
			$protectorRolesId[$roleId] = "~~ System Default ~~";
		}
	}
	if ( $pContent->mContentId ) {
		$serviceHash['protector']['role'] = $gProtector->getProtectionList( $pContent->mContentId );
	} else {
		if ( isset( $pContent->mInfo['parent_id'] ) ) {
			$serviceHash['protector']['role'] = $gProtector->getProtectionList( $pContent->mInfo['parent_id'] );
		}
	}	
	if ( isset( $serviceHash['protector']['role'] ) ) { $prot = array_keys( $serviceHash['protector']['role'] ); }
	$serviceHash['protector']['role_id'] = ( empty( $prot[0] ) ? -1 : $prot[0] );
	$gBitSmarty->assignByRef( 'serviceHash', $serviceHash );
	$gBitSmarty->assignByRef( 'protectorRolesId', $protectorRolesId );
	$gBitSmarty->assignByRef( 'protectorRoles', $roles );
}

global $gProtector;
$gProtector = new LibertyProtector();

?>
