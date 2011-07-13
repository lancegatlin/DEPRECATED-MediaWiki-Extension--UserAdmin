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
 * @version 1.0.0
*/

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install the UserAdmin extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/UserAdmin/UserAdmin.php" );
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
 
$wgExtensionMessagesFiles['UserAdmin'] = $dir . 'UserAdmin.i18n.php'; 

$wgAutoloadClasses['SpecialUserAdminPanel'] = $dir . 'SpecialUserAdminPanel.class.php'; 
$wgAutoloadClasses['SpecialAddUser'] = $dir . 'SpecialAddUser.class.php'; 
$wgAutoloadClasses['SpecialDeleteUser'] = $dir . 'SpecialDeleteUser.class.php'; 
$wgAutoloadClasses['SpecialMassBlock'] = $dir . 'SpecialMassBlock.class.php'; 
$wgAutoloadClasses['SpecialEditUser'] = $dir . 'SpecialEditUser.class.php'; 
$wgAutoloadClasses['SpecialUADMBase'] = $dir . 'SpecialUADMBase.class.php'; 
$wgAutoloadClasses['UADMLogActionHandler'] = $dir . 'UserAdminLogActionHandler.class.php'; 

$wgSpecialPages['UserAdmin'] = 'SpecialUserAdminPanel'; 
$wgSpecialPages['AddUser'] = 'SpecialAddUser'; 
$wgSpecialPages['DeleteUser'] = 'SpecialDeleteUser'; 
$wgSpecialPages['EditUser'] = 'SpecialEditUser'; 
//$wgSpecialPages['MassBlock'] = 'SpecialMassBlock'; 

$wgSpecialPageGroups['UserAdmin'] = 'users'; 
$wgSpecialPageGroups['AddUser'] = 'users'; 
$wgSpecialPageGroups['DeleteUser'] = 'users'; 
$wgSpecialPageGroups['EditUser'] = 'users'; 
//$wgSpecialPageGroups['MassBlock'] = 'users'; 

$wgLogActionsHandlers['rights/uadm-changeduserpasswordlog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-changeduseremaillog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-changedusernamelog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-changeduserrealnamelog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-emailpasswordlog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-emailwelcomelog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-usersdeletedlog'] = 'UADMLogActionHandler::logActionHandler';

