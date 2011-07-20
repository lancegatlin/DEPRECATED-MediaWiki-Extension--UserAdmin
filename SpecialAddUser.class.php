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
      'domain' => 'local',
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
      'domain' => 'local',
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
    global $wgLang, $wgOut, $wgUser, $wgAuth;
    
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
    
    # Hack to detect if domain is needed
    $domainHTML = '';
    $template = new UsercreateTemplate;
    $temp = 'signup';
    // Bug fix. This does nothing.
    $wgAuth->autoCreate(); 
    // The first time wgAuth is called, some PHP auto-magic involving StubObject
    // occurs to "unstub" wgAuth AND call the function invoked. If the below
    // call is made as written, the call is actually made by calling 
    // call_user_func_array and the arguments are passed by value even though
    // the modifyUITemplate expects them to be by reference.
    // This use to be a non issue since call-time pass-by-reference was allowed
    // $wgAuth->modifyUITemplate(&$template, &$temp); 
    // This generates warnings now. Solution is to perform a no-op call to
    // wgAuth to "unstub" it so that the below call will be made directly and
    // not by call_user_func_array
    $wgAuth->modifyUITemplate($template, $temp);
    if(isset($template->data['usedomain']) && $template->data['usedomain'] == true)
    {
      $domainHTML = <<<EOT
      <tr>
        <td><label for="domain">$this->domainfield</label></td>
        <td><input id="domain" type="text" name="domain" size="30" value="$this->domain"/><br/></td>
      </tr>
EOT;
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
$domainHTML
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
    <button type="submit" name="action" value="adduser">$this->adduserlabel</button>
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
    global $wgUser, $wgAuth;
    
    // Validate FORM 
    if(empty($this->username))
      throw new InvalidPOSTParamException(wfMsg('uadm-fieldisrequiredmsg',$this->usernamefield));
    
    // check if its already being used
    if(User::idFromName($this->username) !== null)
      throw new InvalidPOSTParamException(wfMsg('uadm-usernameinusemsg', $this->username));

    if(!User::isCreatableName($this->username))
      throw new InvalidPOSTParamException(wfMsg('uadm-invalidusernamemsg',$this->usernamefield));
    
    if($this->domain != 'local' && $this->domain != '')
    {
      if(!$wgAuth->validDomain($this->domain))
        throw new InvalidPOSTParamException(wfMsg('uadm-invaliddomainmsg'));
      
      $wgAuth->setDomain($this->domain);
      
      if($wgAuth->userExists($this->username))
        throw new InvalidPOSTParamException(wfMsg('uadm-usernameinusemsg', $this->username));
    }
      
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
    global $wgUser, $wgAuth;

    switch($this->action)
    {
      default :
        throw new InvalidPOSTParamException(wfMsg('uadm-formsubmissionerrormsg'));
      case 'emailwelcomepreview' :
        $this->pwdaction = 'emailwelcome';
        $newParams = array('preview' => 'welcome' ) + $this->mParams;
        $newParams = array_intersect_key($newParams, $this->getParamsGET());
        return $this->getURL($newParams);
      case 'adduser' :
        break;
    }
    
    $this->validatePOSTParams();
    
    if($this->domain != 'local' && $this->domain != '')
    {
      if(!$wgAuth->canCreateAccounts())
        return $this->getPOSTRedirectURL(false, wfMsg('uadm-createextacctfailmsg'));
    }
    
    $logRights = new LogPage( 'rights' );
    
    $user = new User;
        
    $user->setName($wgAuth->getCanonicalName($this->username));
    $user->setRealName($this->realname);      
    $user->setEmail($this->email);
    
    $successWikiText = array();
    $successWikiText[] = wfMsg('uadm-newusersuccessmsg', $this->username);
    
    $userPassword = '';
    switch($this->pwdaction)
    {
      case 'manual' :
        try {        
          $user->setPassword($this->password1);
          $userPassword = $this->password1;
        }
        catch(PasswordError $pe)
        {
          return $this->getPOSTRedirectURL(false, wfMsg('uadm-passworderrormsg') . $pe->getText());
        }
        $successWikiText[] = wfMsg('uadm-passwordchangesuccessmsg',$this->username);
        break;
      
      case 'emailwelcome' :
        $result = self::mailWelcomeAndPassword($user);

        if( WikiError::isError( $result ) )
          return $this->getPOSTRedirectURL( false, wfMsg( 'uadm-mailerror', $result->getMessage() ) );

        $successWikiText[] = wfMsg('uadm-welcomeemailsuccessmsg', $this->username, $this->email);
        break;
    }
    
    $user->setToken();
    $wgAuth->initUser( $user, false);
    
    $abortError = '';
		if( !wfRunHooks( 'AbortNewAccount', array( $user, &$abortError ) ) )
			return $this->getPOSTRedirectURL( false, wfMsg( 'uadm-hookblocknewusermsg', $abortError) );
		
    if(!$wgAuth->addUser( $user, $userPassword, $this->email, $this->realname ) )
			return $this->getPOSTRedirectURL( false, wfMsg( 'uadm-wgauthaddfailmsg', $abortError) );

    $user->addToDatabase();
    $user->addNewUserLogEntry();
    
    if(count($this->groups) > 0)
    {
      $userrightsPage = new UserrightsPage;    
      $userrightsPage->doSaveUserGroups($user, $this->groups, array(), $this->newuserreasonmsg);
      wfRunHooks( 'UserRights', array( $user, $add, $remove ) );
      $successWikiText[] = wfMsg('uadm-changestogroupsuccessmsg', $this->username);
    }
    
    $successWikiText = implode('<br/>', $successWikiText);
		
    wfRunHooks( 'AddNewAccount', array( $user, true ) );
    
    $ssUpdate = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
		$ssUpdate->doUpdate();
    
    # password log entry
    switch($this->pwdaction)
    {
      case 'manual' :
        $logRights->addEntry( 
          'uadm-changeduserpasswordlog',
          $user->getUserPage(),
          $this->newuserreasonmsg,
          array(
          )
        );
        break;
      case 'emailwelcome' :
        $logRights->addEntry( 
          'uadm-emailwelcomelog',
          $user->getUserPage(),
          $this->newuserreasonmsg,
          array(
          )
        );
        break;
    }
    
    // Redirect to EditUser special page instead of AddUser to allow editing of 
    // user just added
    return $this->getSpecialPageURL('EditUser',$this->username, array('statusmsg' => base64_encode($successWikiText), 'statusok' => true, 'returnto' => $this->returnto));
  }
}