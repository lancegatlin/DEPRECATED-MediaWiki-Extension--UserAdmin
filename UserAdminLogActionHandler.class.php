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

class UADMLogActionHandler {
  
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
static function logActionHandler($type, $action, $title, $skin, $params)
{
  foreach($params as &$param)
    if($param == '')
      $param = '(empty)';
      
  $options =  array('parseinline', 'replaceafter');
  switch($action)
  {
    case 'uadm-userspurgedlog' :
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

}

?>