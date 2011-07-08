<?php

/**
 * UserAdmin is a MediaWiki extension which allows administrators to add and 
 * delete users (e.g. spam or unused accounts), change user passwords, edit user 
 * details (e.g. username, real name or email), edit user groups, resend emails 
 * (e.g. reset password email or welcome message email). This extension is 
 * primarily for administrators of private wikis that require tighter control of 
 * user accounts.
 *
 * Usage:
 * 	require_once("extensions/UserAdmin/UserAdmin.php"); in LocalSettings.php
 *
 * @file
 * @ingroup Extensions
 * @link http://www.mediawiki.org/wiki/Extension:UserAdmin   Documentation
 * @author Lance Gatlin <lance.gatlin@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 0.1.0
*/

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install the UserAdmin extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/UserAdmin/UserAdmin.php" );
EOT;
        exit( 1 );
}
 
$wgExtensionCredits['specialpage'][] = array(
        'name' => 'User Administration',
        'author' => 'Lance Gatlin',
        'url' => 'http://www.mediawiki.org/w/index.php?title=Extension:UserAdmin',
        'descriptionmsg' => 'useradmin-desc',
        'version' => '0.5.0',
);
 
$dir = dirname(__FILE__) . '/';
 
$wgExtensionMessagesFiles['UserAdmin'] = $dir . 'UserAdmin.i18n.php'; # Location of a messages file (Tell MediaWiki to load this file)

$wgAutoloadClasses['SpecialUserAdminPanel'] = $dir . 'SpecialUserAdminPanel.class.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgAutoloadClasses['SpecialAddUser'] = $dir . 'SpecialAddUser.class.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgAutoloadClasses['SpecialDeleteUser'] = $dir . 'SpecialDeleteUser.class.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgAutoloadClasses['SpecialEditUser'] = $dir . 'SpecialEditUser.class.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgAutoloadClasses['SpecialUADMBase'] = $dir . 'SpecialUADMBase.class.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)

$wgSpecialPages['UserAdmin'] = 'SpecialUserAdminPanel'; # Tell MediaWiki about the new special page and its class name
$wgSpecialPages['AddUser'] = 'SpecialAddUser'; # Tell MediaWiki about the new special page and its class name
$wgSpecialPages['DeleteUser'] = 'SpecialDeleteUser'; # Tell MediaWiki about the new special page and its class name
$wgSpecialPages['EditUser'] = 'SpecialEditUser'; # Tell MediaWiki about the new special page and its class name

$wgSpecialPageGroups['UserAdmin'] = 'users'; # Place this under the users grouping in special pages
$wgSpecialPageGroups['AddUser'] = 'users'; # Place this under the users grouping in special pages
$wgSpecialPageGroups['DeleteUser'] = 'users'; # Place this under the users grouping in special pages
$wgSpecialPageGroups['EditUser'] = 'users'; # Place this under the users grouping in special pages

$wgLogActionsHandlers['rights/uadm-changeduserpasswordlog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/uadm-changeduseremaillog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/uadm-changedusernamelog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/uadm-changeduserrealnamelog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/uadm-emailpasswordlog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/uadm-emailwelcomelog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/uadm-usersdeletedlog'] = 'UADM_logActionHandler';

/*
 * Formats log messages when invoked by MW
 * 
 * @param $type unused
 * @param $action string the log message type
 * @param $title Title object
 * @param $skin Skin objec
 * @param $params array of string parameters for message
 * @return string formatted log message
 */
function UADM_logActionHandler($type, $action, $title, $skin, $params)
{
  foreach($params as &$param)
    if($param == '')
      $param = '(empty)';
      
  $options =  array('parseinline', 'replaceafter');
  switch($action)
  {
    case 'uadm-usersdeletedlog' :
      return wfMsgExt($action, $options, $params[0]);
      
    case 'uadm-emailpasswordlog' :
    case 'uadm-emailwelcomelog' :
    case 'uadm-changeduserpasswordlog' :
      return wfMsgExt($action, $options, $title->getPrefixedText());
      
    case 'uadm-changedusernamelog' :
      return wfMsgExt($action, $options, $params[0], $params[1], $params[2]);
      
    case 'uadm-changeduseremaillog' :
    case 'uadm-changeduserrealnamelog' :
      return wfMsgExt($action, $options, $title->getPrefixedText(), $params[0], $params[1]);
  }
}