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
 * Special page to add a user
 */
class SpecialAddUser extends SpecialUADMBase {

  function __construct() 
  {
    parent::__construct('AddUser', 'createaccount');
  }
  
  /*
   * Get the parameters and their default values for a GET
   * 
   * @return array key-value parameters with default value
   */
  function getParamsGET()
  {
    return array(
      'username' => '',
      'realname' => '',
      'email' => '',
      'returnto' => $this->getDefaultReturnTo(),
      'preview' => '',
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
      'username' => '',
      'realname' => '',
      'email' => '',
      'groups' => array(),
      'pwdaction' => '',
      'password1' => '',
      'password2' => '',
      'edittoken' => '',
      'returnto' => $this->getDefaultReturnTo(),
    );
  }

  /*
   * Helper function to validate passed GET parameters
   */
  function validateGETParams()
  {
    if(!empty($this->returnto))
    {
      $title = Title::newFromText($this->returnto);
      if(!is_object($title) || !$title->isKnown())
        $this->returnto = $this->mDefaultParams['returnto'];
    }
  }
  
  /*
   * Display the add user form
   * 
   * @return string HTML
   */
  function doGET() 
  {
    global $wgLang, $wgOut, $wgUser;
    
    $this->validateGETParams();
    
    $groupsHTML = '';
    foreach(User::getAllGroups() as $groupName)
    {
      $localName = User::getGroupMember($groupName);
      $groupsHTML.= <<<EOT
<input id="grp$groupName" type="checkbox" name="groups[]" value="$groupName"/> <label for="grp$groupName">$localName</label><br/>
EOT;
    }

    $pwdtitleHref = Title::newFromText('passwordremindertitle', NS_MEDIAWIKI)->getLocalURL();
    $pwdtextHref = Title::newFromText('passwordremindertext', NS_MEDIAWIKI)->getLocalURL();
    $welcomeTitleHref = Title::newFromText('createaccount-title', NS_MEDIAWIKI)->getLocalURL();
    $welcomeTextHref = Title::newFromText('createaccount-text', NS_MEDIAWIKI)->getLocalURL();
    
    $returnToHTML = '';
    if(!empty($this->returnto))
      $returnToHTML = self::parse(wfMsg('uadm-returntomsg', $this->returnto));
    
    $postURL = $this->getURL($this->mParams);
    
    $editToken = $wgUser->editToken('adduser' . $wgUser->getName());

    $previewPasswordEmailHref = $this->getURL(array('preview' => 'password') + $this->mParams);
    $previewWelcomeEmailHref = $this->getURL(array('preview' => 'welcome') + $this->mParams);
    
    $previewPasswordEmailHTML = '';
    $previewWelcomeEmailHTML = '';
    $setPasswordChecked = 'checked';
    $emailPasswordChecked = '';
    $emailWelcomeChecked = '';
    if(!empty($this->preview))
    {
      $tempUser = new User;
      $tempUser->setName($this->username);
      $tempUser->setRealName($this->realname);
      $tempUser->setEmail($this->email);
      switch($this->preview)
      {
        case 'welcome' :
          list($subject, $body) = $this->getWelcomeMailMessage($tempUser);
          break;
      }
    
      $previewHTML=<<<EOT
<table>
  <tr>
    <td>$this->subjectlabel</td>
    <td><input value="$subject" size="70" disabled="disabled"/></td>
  <tr>
    <td colspan="2"><textarea rows="10" cols="80" disabled="disabled">$body</textarea></td>
  </tr>
</table>
EOT;
      switch($this->preview)
      {
        case 'welcome' :
          $previewWelcomeEmailHTML = $previewHTML;
          $setPasswordChecked = '';
          $emailWelcomeChecked = 'checked';
          break;
      }
    }
    
    return <<<EOT
<form id="adduserform" name="input" action="$postURL" method="post" class="visualClear">
  <input type="hidden" name="edittoken" value="$editToken"/>
  <fieldset>
    <legend>$this->adduserlabel</legend>
    <table>
      <tr>
        <td><label for="username">$this->usernamefield</label></td>
        <td><input id="username" type="text" name="username" size="30" value="$this->username"/> $this->requiredlabel<br/></td>
      </tr>
      <tr>
        <td><label for="realname">$this->realnamefield</label></td>
        <td><input id="realname" type="text" name="realname" size="30" value="$this->realname"/><br/></td>
      </tr>
      <tr>
        <td><label for="email">$this->emailfield</label></td>
        <td><input id="email" type="text" name="email" size="30" value="$this->email"/> $this->requiredlabel<br/></td>
      </tr>
    </table>
    <fieldset>
      <legend>$this->editgroupslabel</legend>
      $groupsHTML
    </fieldset>
    <fieldset>
      <legend>$this->editpasswordlabel</legend>
      <input id="pwdmanual" type="radio" name="pwdaction" value="manual" $setPasswordChecked/> <label for="pwdmanual">$this->setpasswordforuserlabel</label><br/>
        <table>
          <tr>
            <td><label for="password1">$this->passwordlabel</label></td>
            <td><input id="password1" type="password" name="password1" size="30"/></td>
          </tr>
          <tr>
            <td><label for="password2">$this->verifypasswordlabel</label></td>
            <td><input id="password2" type="password" name="password2" size="30"/></td>
          </tr>
        </table>
      <input id="pwdemailwelcome" type="radio" name="pwdaction" value="emailwelcome" $emailWelcomeChecked/> <label for="pwdemailwelcome">$this->emailwelcomelabel</label> <button type="submit" name="action" value="emailwelcomepreview">$this->previewactionlabel</button> (<a href="$welcomeTitleHref">$this->subjectlabel</a> | <a href="$welcomeTextHref">$this->bodylabel</a>)<br/>
      $previewWelcomeEmailHTML
    </fieldset>
    <input type="submit" name="action" value="$this->adduserlabel"/>
  </fieldset>
</form>
$returnToHTML
EOT;
  }

  /*
   * Helper function to validate POST paramters
   */
  function validatePOSTParams()
  {
    global $wgUser;
    
    // Validate FORM 
    if(empty($this->username))
      throw new InvalidPOSTParamException(wfMsg('uadm-fieldisrequiredmsg',$this->usernamefield));
    
    // check if its already being used
    if(User::idFromName($this->username) !== null)
      throw new InvalidPOSTParamException(wfMsg('uadm-usernameinusemsg', $this->username));

    if(!User::isCreatableName($this->username))
      throw new InvalidPOSTParamException(wfMsg('uadm-invalidusernamemsg',$this->usernamefield));
    
//    if(!$wgUser->matchEditToken(stripslashes($this->edittoken), $this->userid))
    if(!$wgUser->matchEditToken($this->edittoken, 'adduser' . $wgUser->getName()))
      throw new InvalidPOSTParamException(wfMsg('uadm-formsubmissionerrormsg'));
    
    if(empty($this->email))
      throw new InvalidPOSTParamException(wfMsg('uadm-fieldisrequiredmsg',$this->emailfield));

    if(!User::isValidEmailAddr($this->email))
      throw new InvalidPOSTParamException(wfMsg('uadm-invalidemailmsg',$this->emailfield));

    if(empty($this->pwdaction))
      throw new InvalidPOSTParamException(wfMsg('uadm-formsubmissionerrormsg'));
    
    if($this->pwdaction == 'manual')
    {
      if(empty($this->password1) || empty($this->password2))
        throw new InvalidPOSTParamException(wfMsg('uadm-fieldisrequiredmsg',$this->passwordfield));
      
      if($this->password1 != $this->password2)
        throw new InvalidPOSTParamException(wfMsg('uadm-passwordsmustmatchmsg'));

    }
    elseif($this->pwdaction != 'email' && $this->pwdaction != 'emailwelcome')
      throw new InvalidPOSTParamException(wfMsg('uadm-formsubmissionerrormsg'));
  }
  
  /*
   * Add a new user according to the POST parameters OR redirect for preview
   * 
   * @return string URL to redirect to
   */
  function doPOST()
  {
    global $wgUser;

    switch($this->action)
    {
      default :
        throw new InvalidPOSTParamException(wfMsg('uadm-formsubmissionerrormsg'));
      case 'emailwelcomepreview' :
        $this->pwdaction = 'emailwelcome';
        $this->validatePOSTParams();
        return $this->getURL(array('preview' => 'welcome') + $this->mParams);
      case 'adduser' :
        break;
    }
    
    $this->validatePOSTParams();

    $logRights = new LogPage( 'rights' );
    
    $user = new User;
        
    $user->setName($this->username);
    $user->setRealName($this->realname);      
    $user->setEmail($this->email);
    
    $successWikiText = array();
    $successWikiText[] = wfMsg('uadm-newusersuccessmsg', $this->username);
    
    switch($this->pwdaction)
    {
      case 'manual' :
        try {
        
//          $result = $user->checkPassword($this->password1);
//          if($result !== true)
//            throw new InvalidPOSTParamException(wfMsg('uadm-invalidpasswordmsg'));
          
          $user->setPassword($this->password1);
          
        }
        catch(PasswordError $pe)
        {
          return $this->getPOSTRedirectURL(false, wfMsg('uadm-passworderrormsg') . $pe->getText());
        }
        $logRights->addEntry( 
          'changeduserpasswordlog',
          $user->getUserPage(),
          $this->newuserreasonmsg,
          array(
          )
        );
        $successWikiText[] = wfMsg('uadm-passwordchangesuccessmg',$this->username);
        break;
      
      case 'email' :
        $result = self::mailPassword($user);

        if( WikiError::isError( $result ) )
          return $this->getPOSTRedirectURL(false, wfMsg( 'uadm-mailerror', $result->getMessage() ) );

        $logRights->addEntry( 
          'emailpasswordlog',
          $user->getUserPage(),
          $this->newuserreasonmsg,
          array(
          )
        );
        $successWikiText[] = wfMsg('uadm-passwordemailsuccessmsg', $this->username, $this->email);
        break;
        
      case 'emailwelcome' :
        $result = self::mailWelcomeAndPassword($user);

        if( WikiError::isError( $result ) )
          return $this->getPOSTRedirectURL( false, wfMsg( 'uadm-mailerror', $result->getMessage() ) );

        $logRights->addEntry( 
          'emailwelcomelog',
          $user->getUserPage(),
          $this->newuserreasonmsg,
          array(
          )
        );
        $successWikiText[] = wfMsg('uadm-welcomeemailsuccessmsg', $this->username, $this->email);
        break;
    }
    
    $user->addToDatabase();
    $user->addNewUserLogEntry();
    
    if(count($this->groups) > 0)
    {
      $userrightsPage = new UserrightsPage;    
      $userrightsPage->doSaveUserGroups($user, $this->groups, array(), $this->newuserreasonmsg);
      $successWikiText[] = wfMsg('uadm-changestogroupsuccessmsg', $this->username);
    }
    
    $successWikiText = implode('<br/>', $successWikiText);
    
    // Redirect to EditUser special page instead of AddUser to allow editing of 
    // user just added
    return $this->getSpecialPageURL('EditUser',$this->username, array('returnto' => $this->returnto));
  }
}