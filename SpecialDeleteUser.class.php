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

/*
 * Special page to delete a user
 */
class SpecialDeleteUser extends SpecialUADMBase {

  function __construct() {
    parent::__construct('DeleteUser', 'userrights', array());
    wfLoadExtensionMessages('UserAdmin');
  }

  /*
   * Get the parameters and their default values for a GET
   * 
   * @return array key-value parameters with default value
   */
  function getParamsGET()
  {
    return array(
      'action' => '',
      'userid' => '',
      'username' => '',
      'userids' => array(),
      'usernames' => array(),
      'returnto' => $this->getDefaultReturnTo(),
    );
  }
  
  /*
   * Get the parameters and their default values for a POST
   * 
   * @return array key-value parameters with default value
   */
  function getParamsPOST()
  {
    // Have to list these here otherwise they will never get read
    // from wgRequest
    return array(
      'action' => '',
      'userid' => '',
      'username' => '',
      'userids' => array(),
      'usernames' => array(),
      'reason' => '',
      'edittoken' => '',
      'returnto' => $this->getDefaultReturnTo(),
    );
  }

  
  /*
   * Helper function to validate get parameters; throws on invalid
   * 
   * @return array of users requested for deletion
   */
  function validateGETParams()
  {
    if(!empty($this->returnto))
    {
      $title = Title::newFromText($this->returnto);
      if(!is_object($title) || !$title->isKnown())
        $this->returnto = $this->mDefaultParams['returnto'];
    }
    
    $users = array();
    
    if(!empty($this->userid))
    {
      $user = User::newFromId($this->userid);
      if(!$user->loadFromId())
        throw new InvalidGETParamException(wfMsg('uadm-invaliduseridmsg',$this->userid), $this->copyParamsAndRemoveBadParam('userid'));
      if($this->isAdminUser($user))
          throw new InvalidGETParamException(wfMsg('uadm-nodeleteadminmsg',$user->getName()), $this->copyParamsAndRemoveBadParam('userid'));
            
      $users[] = $user;
    }
    
    if(!empty($this->subpage))
    {
      $user = User::newFromName($this->subpage);
      if(!is_object($user) || $user->getId() == 0)
        throw new InvalidGETParamException(wfMsg('uadm-usernoexistmsg', $this->subpage), $this->copyParamsAndRemoveBadParam('subpage'));
      if($this->isAdminUser($user))
          throw new InvalidGETParamException(wfMsg('uadm-nodeleteadminmsg', $user->getName()), $this->copyParamsAndRemoveBadParam('subpage'));
      $users[] = $user;
    }
    
    if(!empty($this->username))
    {
      $user = User::newFromName($this->username);
      if(!is_object($user) || $user->getId() == 0)
        throw new InvalidGETParamException(wfMsg('uadm-usernoexistmsg', $this->username), $this->copyParamsAndRemoveBadParam('username'));
      
      if($this->isAdminUser($user))
        throw new InvalidGETParamException(wfMsg('uadm-nodeleteadminmsg', $this->username), $this->copyParamsAndRemoveBadParam('username'));
      $users[] = $user;
    }
    
    if(count($this->userids) > 0)
    {
      $adminUserIDs = array();
      foreach($this->userids as $userid)
      {
        $user = User::newFromId($userid);
        if(!$user->loadFromId())
          throw new InvalidGETParamException(wfMsg('uadm-invaliduseridmsg',$userid), $this->copyParamsAndRemoveBadArrayValue('userids', $userid));
        if($this->isAdminUser($user))
          throw new InvalidGETParamException(wfMsg('uadm-nodeleteadminmsg', $user->getName()), $this->copyParamsAndRemoveBadArrayValue('userids', $userid));
        
        $users[] = $user;
      }
    }
    
    if(!count($this->usernames))
    {
      $adminUserIDs = array();
      foreach($this->usernames as $username)
      {
        $user = User::newFromName($username);
        if(!is_object($user) || $user->getId() == 0)
          throw new InvalidGETParamException(wfMsg('uadm-usernoexistmsg', $username), $this->copyParamsAndRemoveBadArrayValue('usernames', $username));
        if($this->isAdminUser($user))
          throw new InvalidGETParamException(wfMsg('uadm-nodeleteadminmsg', $username), $this->copyParamsAndRemoveBadArrayValue('usernames', $username));
                
        $users[] = $user;
      }
    }
    
    return $users;
  }
  
  function doGET()
  {
    $users = $this->validateGETParams();

    $returnToHTML = '';
    if(!empty($this->returnto))
      $returnToHTML = self::parse(wfMsg('uadm-returntomsg', $this->returnto));
    
    $searchFormHTML = $this->getSearchFormHTML(wfMsg('uadm-deleteauserlabel'));
            
    if(count($users) == 0)
      return <<<EOT
$searchFormHTML
$returnToHTML
EOT;

    $userRowsHTML = '';
    foreach ($users as $user)
      $userRowsHTML .= $this->getUserRowHTML($user);
    
    $idfieldHTML = $this->useridfield;
    $userNamefieldHTML = $this->usernamefield;
    $realNamefieldHTML = $this->realnamefield;
    $emailfieldHTML = $this->emailfield;
    $createdDatefieldHTML = $this->createddatefield;
    $userTouchedDatefieldHTML = $this->usertoucheddatefield;
    $editcountfieldHTML = $this->editcountfield;
    $groupsfieldHTML = $this->groupsfield;
    $lastEditDatefieldHTML = $this->lasteditdatefield;

    
    return <<<EOT
<h2 class="visualClear">$this->confirmdeletewarningmsg</h2>
<form name="input" action="$this->mURL" method="post" class="visualClear">
<table>
    <tr>
        <th></th>
        <th>$idfieldHTML</th>
        <th>$userNamefieldHTML</th>
        <th>$realNamefieldHTML</th>
        <th>$emailfieldHTML</th>
        <th>$groupsfieldHTML</th>
        <th>$createdDatefieldHTML</th>
        <th>$userTouchedDatefieldHTML<a title="$this->usertoucheddatehelp">[?]</a></th>
        <th>$lastEditDatefieldHTML</th>
        <th>$editcountfieldHTML</th>
    </tr>
    $userRowsHTML
   </table>
   <button type="submit" name="action" value="delete">$this->confirmdeletelabel</button>
</form>
<br/>
$returnToHTML
EOT;
  }

  /*
   * Get the HTML for a user row in the table
   * 
   * @return String: HTML of user row
  */
  function getUserRowHTML($user)
  {
    global $wgLang;
    
    $user->loadGroups();

    $id = $user->getId();
    $userName = $user->getName();
    $realName = $user->getRealName();
    $email = $user->getEmail();
    $emailHTML = strlen($email) > 0 ? "<a href=\"mailto:$email\">$email</a>" : '';
    
    $groups = array_diff($user->getEffectiveGroups(), $user->getImplicitGroups());
    $groupsHTML = '';
    foreach ($groups as $group)
      $groupsHTML .= User::makeGroupLinkHtml($group, htmlspecialchars(User::getGroupMember($group))) . ', ';
    $groupsHTML = substr($groupsHTML, 0, strlen($groupsHTML) - 2);

    $unconfirmed = $user->isEmailConfirmationPending() ? wfMsg('') : '';
    $userPageURL = $user->getUserPage()->getLocalURL();
    $editCount = $user->getEditCount();
    $createDate = $user->getRegistration();
    $createDate = $wgLang->timeanddate($createDate, true);
    $userTouchedDate = $user->getTouched();
    $userTouchedDate = $wgLang->timeanddate($userTouchedDate, true);
    if ($editCount > 0) {
      $lastEditDate = self::getUserLastEditTimestamp($user);
      $lastEditDate = $wgLang->timeanddate($lastEditDate, true);
    }
    else
      $lastEditDate = '';

    $editHref = $this->getSpecialPageURL('EditUser',$userName, array('returnto' => $this->getTitle()->getPrefixedText()));
    $contribsHref = $this->getSpecialPageURL('Contributions',$userName);
    $logsHref = $this->getSpecialPageURL('Log',$userName);
    
    return <<<EOT
<tr>
    <td><input type="checkbox" name="userids[]" value="$id" checked/></td>
    <td>$id</td>
    <td><a href="$userPageURL">$userName</a> <a href="$editHref">($this->editactionlabel</a> | <a href="$contribsHref">$this->contributionsactionlabel</a> | <a href="$logsHref">$this->logsactionlabel</a>) </td>
    <td>$realName</td>
    <td>$emailHTML$unconfirmed</a></td>
    <td>$groupsHTML</td>
    <td>$createDate</td>
    <td>$userTouchedDate</td>
    <td>$lastEditDate</td>
    <td>$editCount</td>
</tr>
EOT;
  }
  
  /*
   * Helper function to validate get parameters; throws on invalid
   * 
   * @return array of users requested for deletion
   */
  function validatePOSTParams()
  {
    if(!empty($this->returnto))
    {
      $title = Title::newFromText($this->returnto);
      if(!is_object($title) || !$title->isKnown())
      {
        $title = Title::newFromText('Special:DeleteUser');
        $title->fixSpecialName();
        $this->returnto = $title->getPrefixedText();
      }
    }
    
    $users = array();
    
    if(!empty($this->userid))
    {
      $user = User::newFromId($this->userid);
      if(!$user->loadFromId())
        throw new InvalidPOSTParamException(wfMsg('uadm-invaliduseridmsg',$this->userid));
      if($this->isAdminUser($user))
          throw new InvalidPOSTParamException(wfMsg('uadm-nodeleteadminmsg',$user->getName()));
            
      $users[] = $user;
    }
    
    if(!empty($this->subpage))
    {
      $user = User::newFromName($this->subpage);
      if(!is_object($user) || $user->getId() == 0)
        throw new InvalidPOSTParamException(wfMsg('uadm-usernoexistmsg', $this->subpage));
      if($this->isAdminUser($user))
          throw new InvalidPOSTParamException(wfMsg('uadm-nodeleteadminmsg', $user->getName()));
      $users[] = $user;
    }
    
    if(!empty($this->username))
    {
      $user = User::newFromName($this->username);
      if(!is_object($user) || $user->getId() == 0)
        throw new InvalidPOSTParamException(wfMsg('uadm-usernoexistmsg', $this->username));
      
      if($this->isAdminUser($user))
        throw new InvalidPOSTParamException(wfMsg('uadm-nodeleteadminmsg', $this->username));
      $users[] = $user;
    }
    
    if(count($this->userids) > 0)
    {
      $adminUserIDs = array();
      foreach($this->userids as $userid)
      {
        $user = User::newFromId($userid);
        if(!$user->loadFromId())
          throw new InvalidPOSTParamException(wfMsg('uadm-invaliduseridmsg',$userid));
        if($this->isAdminUser($user))
          throw new InvalidPOSTParamException(wfMsg('uadm-nodeleteadminmsg', $user->getName()));
        
        $users[] = $user;
      }
    }
    
    if(!count($this->usernames))
    {
      $adminUserIDs = array();
      foreach($this->usernames as $username)
      {
        $user = User::newFromName($username);
        if(!is_object($user) || $user->getId() == 0)
          throw new InvalidPOSTParamException(wfMsg('uadm-usernoexistmsg', $username));
        if($this->isAdminUser($user))
          throw new InvalidPOSTParamException(wfMsg('uadm-nodeleteadminmsg', $username));
                
        $users[] = $user;
      }
    }
    
    return $users;
  }
  
  function doPOST()
  {
    return __FUNCTION__;
  }
}