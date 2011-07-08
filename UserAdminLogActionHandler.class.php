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
static function UlogActionHandler($type, $action, $title, $skin, $params)
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

}

?>