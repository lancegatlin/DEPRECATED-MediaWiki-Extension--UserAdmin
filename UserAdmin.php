<?php

/**
 * UserAdmin is a MediaWiki extension which allows administrators to add users, 
 * permanently remove spam or unused accounts, change user passwords, edit user 
 * details, send reset password or welcome emails and list users with pagination 
 * and filter controls. This extension is primarily for administrators of 
 * private wikis that require tighter control of user accounts.
 *
 * Usage:
 * 	require_once("$IP/extensions/UserAdmin/UserAdmin.php"); in LocalSettings.php
 *
 * @file
 * @ingroup Extensions
 * @link http://www.mediawiki.org/wiki/Extension:UserAdmin   Documentation
 * @author Lance Gatlin <lance.gatlin@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License 3.0
 * @version 0.9.0
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
$wgAutoloadClasses['SpecialPurgeUser'] = $dir . 'SpecialPurgeUser.class.php'; 
$wgAutoloadClasses['MWPurge_1_16'] = $dir . 'MWPurge_1_16.class.php'; 
$wgAutoloadClasses['MWPurge'] = $dir . 'MWPurge.class.php'; 
$wgAutoloadClasses['SpecialMassBlock'] = $dir . 'SpecialMassBlock.class.php'; 
$wgAutoloadClasses['SpecialEditUser'] = $dir . 'SpecialEditUser.class.php'; 
$wgAutoloadClasses['SpecialUADMBase'] = $dir . 'SpecialUADMBase.class.php'; 
$wgAutoloadClasses['UADMLogActionHandler'] = $dir . 'UserAdminLogActionHandler.class.php'; 

$wgSpecialPages['UserAdmin'] = 'SpecialUserAdminPanel'; 
$wgSpecialPages['AddUser'] = 'SpecialAddUser'; 
$wgSpecialPages['PurgeUser'] = 'SpecialPurgeUser'; 
$wgSpecialPages['EditUser'] = 'SpecialEditUser'; 
//$wgSpecialPages['MassBlock'] = 'SpecialMassBlock'; 

$wgSpecialPageGroups['UserAdmin'] = 'users'; 
$wgSpecialPageGroups['AddUser'] = 'users'; 
$wgSpecialPageGroups['PurgeUser'] = 'users'; 
$wgSpecialPageGroups['EditUser'] = 'users'; 
//$wgSpecialPageGroups['MassBlock'] = 'users'; 

$wgLogActionsHandlers['rights/uadm-changeduserpasswordlog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-changeduseremaillog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-changedusernamelog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-changeduserrealnamelog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-emailpasswordlog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-emailwelcomelog'] = 'UADMLogActionHandler::logActionHandler';
$wgLogActionsHandlers['rights/uadm-userspurgedlog'] = 'UADMLogActionHandler::logActionHandler';

