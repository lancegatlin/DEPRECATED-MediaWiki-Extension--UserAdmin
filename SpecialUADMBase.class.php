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
 * Exception class for throwing invalid GET parameters
 */
class InvalidGETParamException extends InvalidArgumentException {
  public $newParmas;
  /*
   * Construct a new exception with a new set of *valid* parameters to redirect
   * to
   */
  public function __construct($message, $newParams, $code = 0, Exception $previous = null)
  {
    parent::__construct($message, $code, $previous);
    $this->newParams = $newParams;
  }
}

/*
 * Exception class for throwing invalid PUT parameters
 */
class InvalidPOSTParamException extends InvalidArgumentException {
  public function __construct($message, $code = 0, Exception $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }
}

/*
 * Base class for UserAdmin special pages
 */
abstract class SpecialUADMBase extends SpecialPage {
  var $mURL; // URL of this special page
  var $mParams = array(); // current parameters to the special page (POST or GET)  
  var $mDefaultParams = array(); // parameters with a default value
  
  function __construct($name, $rights) 
  {
    parent::__construct($name, $rights);

    wfLoadExtensionMessages('UserAdmin');
    
    $this->mURL = $this->getTitle()->getLocalURL();
  }
  
  /*
   * Invoked by MW when this special page is requested. Writes to wgOut to 
   * display page
   * 
   * @param $subpage string subpage specified in the URL (content after first slash)
   */
  function execute($subpage)
  {
    global $wgRequest, $wgUser;

		$this->outputHeader();
    $this->setHeaders();
    
    if ( !$this->userCanExecute( $wgUser ) || !$this->isAdminUser($wgUser)) 
    {
        $this->displayRestrictionError();
        return;
    }
    
    $this->loadMessages();

    if($wgRequest->wasPosted())
      $this->executePOST($subpage);
    else
      $this->executeGET($subpage);
  }
  
  /*
   * Load parameters, build HTML POST status messages, call doGET, return output HTML
   * 
   * @param $subpage string subpage specified in the URL (content after first slash)
   */
  function executeGET($subpage)
  {
    global $wgOut;
    
    $outputHTML = '';

    $this->loadParams(array('statusok' => '','statusmsg' => '') + $this->getParamsGET(), array('subpage' => $subpage));

    if(!empty($this->statusmsg))
    {
      $statusmsg = stripslashes(base64_decode($this->statusmsg));
      // parsing handles sanitizing string
      $statusmsg = self::parse($statusmsg);
      $type = $this->statusok ? 'successbox' : 'errorbox';
      
      $outputHTML .= <<<EOT
<div class="$type">$statusmsg</div>
EOT;
      unset($this->statusok);
      unset($this->statusmsg);
      unset($this->mParams['statusok']);
      unset($this->mParams['statusmsg']);
    }
    
    $isValid = false;
    while(!$isValid)
    {
      try {
        $outputHTML .= $this->doGET();
        $isValid = true;
      }
      catch(InvalidGETParamException $e)
      {
        $outputHTML .= $this->getErrorBoxHTML($e->getMessage());
        $this->mParams = $e->newParams;
        $this->loadMembersFromParams();
      }
    }
    
    $wgOut->addHTML($outputHTML);
  }
  
  /*
   * Load parameters, call doGET, return redirect URL
   * 
   * @param $subpage string subpage specified in the URL (content after first slash)
   */
  function executePOST($subpage)
  {
    global $wgOut;
    
    $this->loadParams($this->getParamsPOST(), array('subpage' => $subpage));
    
    try {
      $redirectURL = $this->doPOST();
    }
    catch(InvalidPOSTParamException $e)
    {
      $redirectURL = $this->getPOSTRedirectURL(false, $e->getMessage());
    }
    
    $wgOut->redirect($redirectURL);
  }

  /*
   * Perform GET in a derived class
   * 
   * @return string URL to redirect to 
   */
  abstract function doPOST();
  
  /*
   * Get the POST parameters (and default values) to read from passed paramters
   */
  abstract function getParamsPOST();
  
  /*
   * Perform GET in a derived class
   * 
   * @return array key-value
   */
  abstract function doGET();
  
  /*
   * Get the GET parameters (and default values) to read from passed paramters
   * 
   * @return array key-value
   */
  abstract function getParamsGET();
    
/* *****************************************************************************
 *                             Parameters Functions
 * *****************************************************************************
*/

  /*
   * Get parameters that are not set to their default values. Used in building
   * query parameters for URL to prevent emitting query parameters that are 
   * set to default value anyways.
   * 
   * @param $params array key-value parameters 
   * @return array key-value parameters that are not set to a default value
   */
  function getNonDefaultParams($params) 
  {
    $nonDefaultParams = array();
    foreach ($params as $key => $value)
      if (!array_key_exists($key, $this->mDefaultParams) || $value != $this->mDefaultParams[$key])
        $nonDefaultParams[$key] = $value;
    ksort($nonDefaultParams);
    return $nonDefaultParams;
  }

  /*
   * Load parameters from wgRequest (_GET, _POST) and supplied defaultParams 
   * into this->mParams and this->mDefaultParams. Also sets shortcut property
   * values, e.g. this->username or this->subpage
   * 
   * @param $queryDefaultParams key-value parameters to read from wgRequest
   * @param $defaultParams key-value additional default parameters NOT read from wgRequest
   */
  function loadParams($queryDefaultParams, $defaultParams)
  {
    global $wgRequest;
    
     // read GET/POST values
    $this->mParams = $defaultParams;
    
    $this->mDefaultParams = $defaultParams + $queryDefaultParams; 

    // Load params from wgRequest
    foreach ($queryDefaultParams as $key => $defaultValue) 
    {
      if(is_array($defaultValue))
        $this->mParams[$key] = $wgRequest->getArray($key, $defaultValue);
      else
        $this->mParams[$key] = $wgRequest->getText($key, $defaultValue);
    }
    
    $this->loadMembersFromParams();
  }

  /*
   * Helper function for loadParams. Makes member shortcuts for params
   */
  function loadMembersFromParams()
  {
    // Make script property shortcuts
    foreach($this->mParams as $key => $value)
      $this->$key = $value;
  }
  
  /*
   * Helper function that loads all messages as members shortcuts. Removes
   * prefix starting with '-' character from name: uadm-passwordchangesuccessmsg
   * becomes this->passwordchangesuccessmsg
   */
  function loadMessages()
  {
    include 'UserAdmin.i18n.php';
    
    global $wgLang;
    
    foreach($messages['en'] as $key => $notused)
    {
      $a = explode('-', $key);
      if(count($a) == 2)
        $this->$a[1] = $wgLang->getMessage($key);
      else
        $this->$key = $wgLang->getMessage($key);
    }
  }
  
  /*
   * Copies current parameters and removes (sets to default) a bad parameter
   * 
   * @param $badParam name of the bad parameter
   */
  function copyParamsAndRemoveBadParam($badParam)
  {
    $params = $this->mParams;
    
    $params[$badParam] = $this->mDefaultParams[$badParam];
    
    return $params;
  }
  
  /*
   * Copies current parameters and removes a bad value from an array parameter
   * 
   * @param $key string name of the array parameter
   * @param $badValue string the value to remove
   */
  function copyParamsAndRemoveBadArrayValue($key, $badValue)
  {
    $params = $this->mParams;
    
    $params[$key] = array_diff($params[$key], array($badValue));
    
    return $params;
  }
  
/* *****************************************************************************
 *                                Mail Functions
 * *****************************************************************************
*/
  
  /*
   * Mail welcome message with random password to a user
   * 
   * @param $user User
   */
  static function mailWelcomeAndPassword($user)
  {
    return self::mailPasswordInternal($user, false, 'createaccount-title', 'createaccount-text');
  }
  
  /*
   * Mail password reset message to user
   * 
   * @param $user User
   */
  static function mailPassword($user)
  {
    return self::mailPasswordInternal($user, false, 'passwordremindertitle', 'passwordremindertext');
  }
  
	/** Modified from SpecialUserlogin.php; supports an extended set of parameters
   *  for the email message, see code below.
	 * @param $u User object
	 * @param $throttle Boolean
	 * @param $emailTitle String: message name of email title
	 * @param $emailText String: message name of email text
	 * @return Mixed: true on success, WikiError on failure
	 */
	static function mailPasswordInternal( $user, $throttle, $emailTitle, $emailText) 
  {
		global $wgUser;

		if ( $user->getEmail() == '' ) 
			return new WikiError( wfMsg( 'noemail', $user->getName() ) );
    
		wfRunHooks( 'User::mailPasswordInternal', array(&$wgUser, &$ip, &$user) );

    list($subject, $body, $np) = self::getMailMessage($user, $emailTitle, $emailText);
    
		$user->setNewpassword( $np, $throttle );
		$user->saveSettings();
    
		$result = $user->sendMail( $subject, $body );

		return $result;
	}

  /*
   * Get the text for the welcome message
   * 
   * @return array($subject as string, $body as string, $newPassword as string)
   */
  static function getWelcomeMailMessage($user)
  {
    return self::getMailMessage($user, 'createaccount-title', 'createaccount-text');
  }
  
  /*
   * Get the text of the reset password message
   * 
   * @return array($subject as string, $body as string, $newPassword as string)
   */
  static function getPasswordMailMessage($user)
  {
    return self::getMailMessage($user, 'passwordremindertitle', 'passwordremindertext');
  }
  
  /*
   * Get the subject and body of an system generated email. Used for both 
   * sending and previewing a message
   * 
   * @param $user User
   * @param $emailTitle string of title of message string for wfMsg (e.g. passwordremindertitle)
   * @param $emailText string of title of message string for wfMsg (e.g. passwordremindertext)
   * @return array($subject as string, $body as string, $newPassword as string)
   */
  static function getMailMessage($user, $emailTitle, $emailText)
  {
    global $wgNewPasswordExpiry, $wgServer, $wgScript;
    
		$ip = wfGetIP();
		if( !$ip ) 
			return new WikiError( wfMsg( 'badipaddress' ) );
		
		$newPassword = $user->randomPassword();
    
		$userLangUADMge = $user->getOption( 'langUADMge' );
    
    $subject = wfMsgExt( $emailTitle, array( 'parsemag', 'langUADMge' => $userLangUADMge ));
    
		$body = wfMsgExt( $emailText, array( 'parsemag', 'langUADMge' => $userLangUADMge )
            ,$ip // $1 
            ,$user->getName() // $2 [User's name]
            ,$newPassword // $3 [new password]
            ,$wgServer . $wgScript // $4 [URL of wiki]
            ,round( $wgNewPasswordExpiry / 86400 ) // $5 [days to password expires]
            ,$user->getRealName() // $6 [User's real name]
            ,$user->getEmail() // $7 [User's email]
            
    );
    
    return array($subject, $body, $newPassword);
  }

/* *****************************************************************************
 *                             URL Functions
 * *****************************************************************************
*/
  
  /*
   * Get the URL to a special page, localizing english names
   * 
   * @param $specialPage string special page name
   * @param $subpage string subpage (optional)
   * @param $queryParams array key-value of parameters
   */
  function getSpecialPageURL($specialPage, $subpage = '', $queryParams = '')
  {
    $title = Title::newFromText('Special:' . $specialPage . (strlen($subpage) > 0 ? "/$subpage" : ''));
    
    $title->fixSpecialName();
    return $title->getLocalURL($queryParams);
  }
  
  /*
   * Get the URL to this special page with the parameters passed. Default values 
   * are removed from the supplied parameters
   * 
   * @param $params key-value parameters
   * @return string URL of special page with http query parameters
   */
  function getURL($params) 
  {
    $retvURL = $this->mURL;

    $nonDefaultParams = $this->getNonDefaultParams($params);
    if (count($nonDefaultParams) > 0)
      $retvURL .= '?' . http_build_query($nonDefaultParams);
    return $retvURL;
  }
  
  /*
   * Get the URL to the current special page with current parameters and
   * add an additional status msg
   * 
   * @param $params key-value parameters
   * @param $statusok boolean true = success, false = error
   * @param $statusmsg string
   * @return string URL of special page with http query parameters
   */
  function getURLWithStatus($params, $statusok, $statusmsg) 
  {
    return $this->getURL(array('statusok' => $statusok, 'statusmsg' => base64_encode($statusmsg)) + $params);
  }

  /*
   * Get the URL to a special page with current parameters and
   * add an additional status msg
   * 
   * @param $params key-value parameters
   * @param $statusok boolean true = success, false = error
   * @param $statusmsg string
   * @return string URL of special page with http query parameters
   */
  function getPOSTRedirectURL($statusok, $statusmsg)
  {
    $params = array_intersect_key($this->mParams, $this->getParamsGET());
    return $this->getURLWithStatus($params, $statusok, $statusmsg);
  }
  
  function getDefaultReturnTo()
  {
    $title = Title::newFromText('Special:SpecialPages');   
    $title->fixSpecialName();
    return $title->getPrefixedText();
  }
  
/* *****************************************************************************
 *                             Misc Functions
 * *****************************************************************************
*/
  
  /*
   * Convenience function to parse some wiki text to HTML
   * 
   * @param $wikiText string wiki text to parse
   * @return string HTML of parsed text
   */
  static function parse($wikiText)
  {
    global $wgOut, $wgParser;
    
    $parserOutput = $wgParser->parse($wikiText, $wgOut->getTitle(), $wgOut->parserOptions());
    $output = $parserOutput->getText();
    
    $m = array();
    if( preg_match( '/^<p>(.*)\n?<\/p>\n?$/sU', $output, $m ) ) {
      $output = $m[1];
    }
    return $output;
  }
  
  /*
   * Get the time stamp of the last time a user editted a page
   * 
   * @param $user User
   * @return string timestamp of last edit
   */
  static function getUserLastEditTimestamp($user) 
  {
    if ($user->getId() == 0)
      return false; // anons

    $dbr = wfGetDB(DB_SLAVE);
    $time = $dbr->selectField('revision', 'rev_timestamp', array('rev_user' => $user->getId()), __METHOD__, array('ORDER BY' => 'rev_timestamp DESC'));
    if (!$time)
      return false; // no edits
    return wfTimestamp(TS_MW, $time);
  }
  
  /*
   * Test if the user passed is an administrator 
   * 
   * @return boolean true if user belongs to sysop group, false otherwise
   */
  function isAdminUser($user)
  {
    foreach($user->getGroups() as $groupName)
      if($groupName == 'sysop')
        return true;
      
    return false;
  }
  
/* *****************************************************************************
 *                             HTML Functions
 * *****************************************************************************
*/
  
 
  /*
   * Get HTML for an error box with HTML content
   * 
   * @param $errorHTML string HTML content of error box
   * @return string HTML
   */
  function getErrorBoxHTML($errorHTML)
  {
    return<<<EOT
<div class="errorbox visualClear">$errorHTML</div>
EOT;
  }

  /*
   * Get HTML for a success box with HTML content
   * 
   * @param $errorHTML string HTML content of success box
   * @return string HTML
   */
  function getSuccessBoxHTML($successHTML)
  {
    return<<<EOT
<div class="successbox">$successHTML</div>
EOT;
  }
  
  /*
   * Get HTML for an error page with HTML content and a return to URL
   * 
   * @param $errorHTML string HTML content of error page
   * @return string HTML
   */
  function getErrorPageHTML($errorHTML)
  {
    $specialPageName = $this->getName();
    return<<<EOT
$errorHTML
<br/>
<br/>
Return to <a href="$this->mURL">$specialPageName</a>
EOT;
  }

  /*
   * Get HTML for a search form
   * 
   * @param $legend title of the search form
   * @return string HTML
   */
  function getSearchFormHTML($legend)
  {
    return<<<EOT
<form name="search" action="$this->mURL" method="get" class="visualClear">
  <fieldset>
    <legend>$legend</legend>
    <label for="username">$this->enterusernamelabel:</label>
    <input id="username" type="text" name="username" size="30">
    <input type="submit" value="Search"/>
  </fieldset>
</form>
EOT;
  }

}