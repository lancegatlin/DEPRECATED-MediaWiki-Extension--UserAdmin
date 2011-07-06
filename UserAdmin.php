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
        'url' => 'http://www.mediawiki.org/wiki/Extension:MyExtension',
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

$wgLogActionsHandlers['rights/changeduserpasswordlog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/changeduseremaillog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/changedusernamelog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/changeduserrealnamelog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/emailpasswordlog'] = 'UADM_logActionHandler';
$wgLogActionsHandlers['rights/emailwelcomelog'] = 'UADM_logActionHandler';

/*
 * Formats log messages when invoked by MW
 * 
 * @param type unused
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
    case 'emailpasswordlog' :
    case 'emailwelcomelog' :
    case 'changeduserpasswordlog' :
      return wfMsgExt('uadm-' . $action, $options, $title->getPrefixedText());
      
    case 'changedusernamelog' :
      return wfMsgExt('uadm-' . $action, $options, $params[0], $params[1], $params[2]);
      
    case 'changeduseremaillog' :
    case 'changeduserrealnamelog' :
      return wfMsgExt('uadm-' . $action, $options, $title->getPrefixedText(), $params[0], $params[1]);
  }
}