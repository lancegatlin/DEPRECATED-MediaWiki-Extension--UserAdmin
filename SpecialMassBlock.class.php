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
 * Special page to delete a user
 */
class SpecialMassBlock extends SpecialUADMBase {

  function __construct() {
    parent::__construct('MassBlock', 'userrights', array());
  }

  /*
   * Get the parameters and their default values for a GET
   *
   * @return array key-value parameters with default value
   */
  function getParamsGET()
  {
    return array(
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
    global $wgUser;

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

    $editToken = $wgUser->editToken('deleteuser' . $wgUser->getName());

    # Get expiry options list (copied from SpecialBlockip.php
    $scBlockExpiryOptions = wfMsgForContent( 'ipboptions' );

		$showblockoptions = $scBlockExpiryOptions != '-';
		if( !$showblockoptions ) $mIpbother = $mIpbexpiry;

		$blockExpiryFormOptions = Xml::option( wfMsg( 'ipbotheroption' ), 'other' );
		foreach( explode( ',', $scBlockExpiryOptions ) as $option ) {
			if( strpos( $option, ':' ) === false ) $option = "$option:$option";
			list( $show, $value ) = explode( ':', $option );
			$show = htmlspecialchars( $show );
			$value = htmlspecialchars( $value );
			$blockExpiryFormOptions .= Xml::option( $show, $value, $this->BlockExpiry === $value ? true : false ) . "\n";
		}
    $expiryHTML = <<<EOT
<select id="expiry" name="expiry">$blockExpiryFormOptions</select>
EOT;
    return <<<EOT
<form name="input" action="$this->mURL" method="post" class="visualClear">
  <input type="hidden" name="edittoken" value="$editToken"/>
  <input type="hidden" name="returnto" value="$this->returnto"/>
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
    $expiryHTML
    <label for="reason">$this->reasonlabel:</label> <input id="reason" type="text" name="reason" size="60" maxlength="255" /> $this->requiredlabel<br/>
   <button type="submit" name="action" value="confirmdelete">$this->confirmblocklabel</button>
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
    global $wgUser;

    if(!$wgUser->matchEditToken($this->edittoken, 'deleteuser' . $wgUser->getName()))
      throw new InvalidPOSTParamException(wfMsg('uadm-formsubmissionerrormsg'));

    if(empty($this->reason))
      throw new InvalidPOSTParamException(wfMsg('uadm-fieldisrequiredmsg',$this->reasonfield));

    if(!empty($this->returnto))
    {
      $title = Title::newFromText($this->returnto);
      if(!is_object($title) || !$title->isKnown())
      {
        $title = Title::newFromText('Special:MassBlockUser');
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
    global $wgVersion, $wgUser;

    switch($this->action)
    {
      default :
        throw new InvalidPOSTParamException(wfMsg('uadm-formsubmissionerrormsg'));
      case 'confirmdelete' :
        break;
    }

    $users = $this->validatePOSTParams();

    list($versionMajor, $versionMinor, $versionRev) = explode('.', $wgVersion);

    switch($versionMajor)
    {
      case 1:
        break;
      default:
  			return $this->getPOSTRedirectURL( false, wfMsg( 'uadm-unsupportedversionmsg', $abortError) );
	  }

    switch($versionMinor)
    {
      case 16 :
        $deleteUser = 'deleteUserVersion1_16';
        break;
      default:
  			return $this->getPOSTRedirectURL( false, wfMsg( 'uadm-unsupportedversionmsg', $abortError) );
    }

    foreach($users as $user)
    {
      $usersMassBlockd[] = $user->getName();
      $this->$deleteUser($user);
    }
    $usersMassBlockd = implode(',', $usersMassBlockd);


    $log = new LogPage( 'rights' );
    $log->addEntry(
      'uadm-usersdeletedlog',
      $wgUser->getUserPage(),
      $this->reason,
      $usersMassBlockd
    );

    return $this->getURLWithStatus (array('returnto' => $this->returnto), true, wfMsg('uadm-deletesuccessmsg'));
  }

  function deleteUserVersion1_16($user)
  {
    $id = $user->getId();

    $dbr = wfGetDB(DB_SLAVE);

    # Purge user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->delete('user',array('user_id' => $id));

    # Purge properties for this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->delete('user_properties',array('up_user' => $id));

    # Purge any text belonging to deleted pages created by this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->deleteJoin('text', 'archive','old_id', 'ar_text_id', array('ar_user'=> $id));
    # Purge any deleted pages created by this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->delete('archive',array('ar_user' => $id));

    # Purge any deleted images created by user
    # lance.gatlin@gmail.com: tested good 9Jul11
    // TODO: delete images from file system
    $dbr->delete('filearchive',array('fa_user' => $id));
    # Zero id for any images deleted by user
    # NOT TESTED
    $dbr->update('filearchive', array( 'fa_deleted_user' => 0),array('fa_deleted_user' => $id));


    # Purge *all* old version of any images created by this user
    // TODO: delete images from file system
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->deleteJoin('oldimage','image','oi_name','img_name',array('img_user'=>$id));
    # Purge old revisions uploaded by this user (user uploaded a new revision to someone else's file)
    // TODO: delete images from file system
    # NOT TESTED
    $dbr->delete('oldimage',array('oi_user'=>$id));

    # Purge images created by user
    // TODO: delete images from file system
    # lance.gatlin@gmail.com: tested good 9Jul11
//    $usersImageCount = $dbr->estimateRowCount('image',array('img_user' => $id));
    $dbr->delete('image',array('img_user' => $id));

    # Zero id of any blocks on this user (preserve any specific IP blocks)
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->update('ipblocks', array( 'ipb_user' => 0),array('ipb_user' => $id));

    # Zero id for any log entries for this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->update('logging', array( 'log_user' => 0),array('log_user' => $id));

    # Purge any recent change entries for this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->delete('recentchanges',array('rc_user' => $id));

    # Purge any text belonging to revisions created by this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->deleteJoin('text', 'revision','old_id', 'rev_text_id', array('rev_user' => $id));


    # Purge any revisions created by this user
    # Get all revision by the user
    $pagesEditedByUser = array();
    $revsByUser = $dbr->select('revision', array('rev_id','rev_parent_id','rev_page'), array('rev_user' => $id));
    foreach($revsByUser as $r)
    {
      # Accumulate list of distinct page_ids of pages edited by the user
      $pagesEditedByUser[] = $r->rev_page;
      # Rethread rev_parent_ids that point at this revision
      # lance.gatlin@gmail.com: tested good 9Jul11
      $dbr->update('revision',array('rev_parent_id' => $r->rev_parent_id),array('rev_parent_id' => $r->rev_id));
      # Rethread page_latest revision
      # lance.gatlin@gmail.com: tested good 9Jul11
      $dbr->update('page',array('page_latest' => $r->rev_parent_id, 'page_touched' => $dbr->timestamp()),array('page_latest' => $r->rev_id));
    }
    $pagesEditedByUser = array_unique($pagesEditedByUser);
    $pagesEditedByUser = implode(',',$pagesEditedByUser);
//    $usersPageCount = 0;

    # MassBlock all revisions by this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->delete('revision',array('rev_user' => $id));

    # Purge any pages the user has revisions for that now have no revisions
    # lance.gatlin@gmail.com: tested good 9Jul11
    if(strlen($pagesEditedByUser)> 0)
      $dbr->query("DELETE page.* FROM page LEFT JOIN revision ON page.page_id = revision.rev_page WHERE page.page_id IN ($pagesEditedByUser) AND revision.rev_id IS NULL");

    # Purge user page, dump cache and all revisions to it
    # lance.gatlin@gmail.com: tested good 9Jul11
    $userName = $user->getName();
    $ns0 = NS_USER;
    $ns1 = NS_USER_TALK;
    $dbr->query("DELETE revision.* FROM revision LEFT JOIN page ON revision.rev_page = page.page_id WHERE page.page_title = '$userName' AND (page.page_namespace=$ns0 OR page.page_namespace=$ns1)" );
    $dbr->query("DELETE FROM page WHERE page_title='$userName' AND (page_namespace=$ns0 OR page_namespace=$ns1)" );

    # Purge any newtalk entries for this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->delete('user_newtalk',array('user_id'=>$id));

    # Purge any watchlist entries for this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $dbr->delete('watchlist',array('wl_user' => $id));
  }
}
