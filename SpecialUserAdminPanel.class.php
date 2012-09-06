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
 * Special page for user admin panel
 */
class SpecialUserAdminPanel extends SpecialUADMBase {

    // look up to convert displayed field name to database field name
    // keys are localized in constructor
    var $mLookupUserField = array(
      'uadm-useridfield' => 'user_id',
      'uadm-usernamefield' => 'user_name',
      'uadm-realnamefield' => 'user_real_name',
      'uadm-emailfield' => 'user_email',
      'uadm-createddatefield' => 'user_registration',
      'uadm-usertoucheddatefield' => 'user_touched',
      'uadm-editcountfield' => 'user_editcount',
    );

  var $mFilterOps = array ('=','<','>','<=','>=','LIKE'); // filter operations
   // lookup for logical opposite of a filter operation
  var $mNegFilterOps = array ( '!=', '>=', '<=', '>', '<', 'NOT LIKE' );

  var $mPageSizes = array('25','50','100','all'); // pagination sizes
  var $mFields = array (); // array of displayed field names

  function __construct() {
    parent::__construct('UserAdmin', 'createaccount');

    // Localize look up field values
    foreach($this->mLookupUserField as $key => $value)
      $tmp[wfMsg($key)] = $value;
    $this->mLookupUserField = $tmp;

    // Set field names to keys of lookup array
    $this->mFields = array_keys($this->mLookupUserField);
  }

  /*
   * Get the parameters and their default values for a GET
   *
   * @return array key-value parameters with default value
   */
  function getParamsGET()
  {
    $retv = array(
        'pagenum' => '1',
        'pagesize' => '25',
        'filterop' => '=',
        'filterneg' => '0',
        'filtertext' => '',
        'sortasc' => '1',
        // Parameters for mass blocks
        'userid' => '',
        'username' => '',
        'userids' => array(),
        'usernames' => array(),
        'returnto' => $this->getDefaultReturnTo(),

    );
    $retv['filterby'] = wfMsg('uadm-usernamefield');
    $retv['sortby'] = wfMsg('uadm-useridfield');

    return $retv;
  }

  /*
   * Get the parameters and their default values for a POST
   *
   * @return array key-value parameters with default value
   */
  function getParamsPOST()
  {
    return array(
        'action' => '',
        'userids' => array(),
    );
  }

  /*
   * Helper function to validate POST parameters
  */
  function validatePOSTParams()
  {

  }

  /*
   * Add a new user according to the POST parameters OR redirect for preview
   *
   * @return string URL to redirect to
   */
  function doPOST() {
    switch($this->action)
    {
      case 'newuser' :
        return $this->getSpecialPageURL('AddUser', '', array('returnto' => $this->getTitle()->getPrefixedText()));
      case 'purge' :
        return $this->getSpecialPageURL('PurgeUser', '', array('userids' => $this->userids, 'returnto' => $this->getTitle()->getPrefixedText()));
    }
    return __FUNCTION__;
  }

  /*
   * Helper function to validate GET parameters
  */
  function validateGETParams()
  {
    if(filter_var($this->pagenum, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false)
      $this->pagenum = $this->mParamsGET['pagenum'];

    if(!in_array($this->pagesize, $this->mPageSizes))
      $this->pagesize = $this->mParamsGET['pagesize'];

    if(!in_array($this->filterby, $this->mFields))
      $this->filterby = $this->mParamsGET['filterby'];

    if(!in_array($this->sortby, $this->mFields))
      $this->sortby = $this->mParamsGET['sortby'];

    if($this->sortasc != '0' && $this->sortasc != '1')
      $this->sortasc = $this->mParamsGET['sortasc'];

    if(!is_numeric($this->filterop) || $this->filterop < 0 || $this->filterop >= count($this->mFilterOps))
      $this->filterop = 0;
//    if(!in_array($this->filterop, $this->mFilterOps))
//      $this->filterop = $this->mParamsGET['filterop'];

    if($this->filterneg != '0' && $this->filterneg != '1')
      $this->filterneg = $this->mParamsGET['filterneg'];

    if($this->filterby == $this->createddatefield || $this->filterby==$this->usertoucheddatefield)
    {
      $result = strtotime($this->filtertext);
      if($result === false)
      {
        $this->filtertext = '';
      }
      else
        $this->filtertext = wfTimestamp(TS_MW, $result);
    }
  }

  /*
   * Display the add user form
   *
   * @return string HTML
   */
  function doGET()
  {
    global $wgOut;

    $this->validateGETParams();

    // Load the users from database according to pagination parameters
    list($MW_users, $estRowCount) = $this->getUsersFromDb();

    $pageNavHTML = '';
    $pageSizerHTML = '';
    $pageSizerSpacerHTML = '';

    // Only show pageNav if there are at least 25 entries and more than 1 page
    // Only show pageSizer if there at least 2 entries
    if ($estRowCount > 25) {
      if ($this->pagemax > 1) {
        $pageNavHTML = $this->getPageNavHTML();
      }
      $pageSizerHTML = $this->getPageSizerHTML();
      $pageSizerSpacerHTML .= "<br/><br/>";
    }

    // Get the filter controls
    $filterControlsHTML = $this->getFilterControlsHTML();

    // Get a link to to adding a new user
    $newuserHref = $this->getSpecialPageURL('AddUser', '', array('returnto' => $this->getTitle()->getPrefixedText()));

    // Build the HTML for rows of users
    $userRowsHTML = '';
    foreach ($MW_users as $user)
      $userRowsHTML .= $this->getUserRowHTML($user);

    // Build links for fields to allow sorting by that column, if clicked
    $idfieldHTML = $this->getSortfieldHTML($this->useridfield);
    $userNamefieldHTML = $this->getSortfieldHTML($this->usernamefield);
    $realNamefieldHTML = $this->getSortfieldHTML($this->realnamefield);
    $emailfieldHTML = $this->getSortfieldHTML($this->emailfield);
    $createdDatefieldHTML = $this->getSortfieldHTML($this->createddatefield);
    $userTouchedDatefieldHTML = $this->getSortfieldHTML($this->usertoucheddatefield);
    $editcountfieldHTML = $this->getSortfieldHTML($this->editcountfield);
    $groupsfieldHTML = $this->groupsfield;
    $lastEditDatefieldHTML = $this->lasteditdatefield;

    $wgOut->includeJQuery();

    $allOrNoneScript = <<<EOT
<script language="javascript" type="text/javascript">
var allOrNoneToggle = true;
jQuery( document ).ready( function( $ ) {
  $("#allornone").click(
    function() {
      $(".selusr").attr('checked', allOrNoneToggle);
      allOrNoneToggle = !allOrNoneToggle;
    }
  );
});
</script>
EOT;
    $wgOut->addScript($allOrNoneScript);

    return <<<EOT
$pageNavHTML
$pageSizerHTML
$pageSizerSpacerHTML
$filterControlsHTML
<form name="input" action="$this->mURL" method="post" class="visualClear">
<button type="submit" name="action" value="purge">$this->uappurgeactionlabel</button>
<button type="submit" name="action" value="block">$this->blockactionlabel</button>
<table>
    <tr>
        <th><button type="submit" name="action" value="newuser">$this->uapnewuseractionlabel</button></th>
        <th><input id="allornone" type="checkbox" name="allOrNone" onclick="all()"/></th>
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
</form>
<br/>
$pageNavHTML
$pageSizerHTML
EOT;
  }

  /*
   * For the supplied field, build a link to allow sorting by that column
   *
   * @param $field String: column name
   * @return String: HTML of column name link
   */
  function getSortfieldHTML($field)
  {
    if($this->sortby == $field)
    {
      $idfieldURL = $this->getURL(array('sortasc' => !$this->sortasc) + $this->mParams);
      $idfieldHTML = "<a href=\"$idfieldURL\">$field " . ($this->sortasc ? '&uarr;' : '&darr;') . "</a>";
    }
    else
    {
      $idfieldURL = $this->getURL(array('sortby' => $field, 'sortasc' => 1) + $this->mParams);
      $idfieldHTML = "<a href=\"$idfieldURL\">$field</a>";
    }
    return $idfieldHTML;
  }

  /*
   * Load users from database according to pagination parameters
   *
   * @return array tuple(UserArray,integer) array of Users and the total number
   * of rows (users) for calculating pagination
   */
  function getUsersFromDb()
  {
    $sqlOptions = array();
    $dbr = wfGetDB(DB_SLAVE);

    // SQL WHERE based on filter
    $sqlConds = '';
    if (strlen($this->filtertext) > 0) {
      $sqlConds = $this->mLookupUserField[$this->filterby] . ' ';

      if($this->filterneg)
        $sqlConds .= $this->mNegFilterOps[$this->filterop] . ' ';
      else
        $sqlConds .= $this->mFilterOps[$this->filterop] . ' ';

      $sqlConds .= $dbr->addQuotes($this->filtertext);
    }

    $result = $dbr->select('user', 'user_id', $sqlConds);
    $estRowCount = $result->numRows();
//    $estRowCount = $dbr->estimateRowCount('user', '*', $sqlConds);

    // SQL LIMIT based on pagenum/pagesize
    if ($this->pagesize != 'all')
    {
      $this->pagemax = intval($estRowCount / $this->pagesize) + 1;
      $this->pagenum = min($this->pagenum, $this->pagemax);
      $sqlOptions['OFFSET'] = ($this->pagenum - 1 ) * $this->pagesize;
      $sqlOptions['LIMIT'] = $this->pagesize;
    }
    else
      $this->pagemax = 1;

    $sqlOptions['ORDER BY'] = $this->mLookupUserField[$this->sortby];
    // SQL ORDER BY  based on sortby/sortasc
    if ($this->sortasc == '0')
      $sqlOptions['ORDER BY'] .= ' DESC';

    $MW_users = UserArray::newFromResult($dbr->select('user', '*', $sqlConds, 'DatabaseBase::select', $sqlOptions));

    return array($MW_users, $estRowCount);
  }

  /*
   * Get the HTML for the filter controls
   *
   * @return String: HTML of filter controls
  */
  function getFilterControlsHTML()
  {
    $filterChoicesHTML = '';
    foreach ($this->mFields as $filterChoice)
    {
      $selected = $this->filterby == $filterChoice ? 'selected' : '';
      $filterChoicesHTML .= "<option value=\"$filterChoice\" $selected>$filterChoice</option>";
    }

    $nonDefaultParamsHTML = '';
    foreach($this->getNonDefaultParams($this->mParams) as $key => $value)
    {
      if ($key == 'filtertext' || $key == 'filterby' || $key == 'filterop' || $key == 'filterneg')
        continue;

      $nonDefaultParamsHTML .= "<input type=\"hidden\" name=\"$key\" value=\"$value\"/>";
    }

    $filterNeg0Selected = $this->filterneg == false ? 'selected' : '';
    $filterNeg1Selected = $this->filterneg == true ? 'selected' : '';

    $filterOpsHTML = '';
    $i = 0;
    foreach ($this->mFilterOps as $op)
    {
      $selected = $this->filterop == $i ? 'selected' : '';
      $filterOpsHTML .= "<option value=\"$i\" $selected>$op</option>";
      $i++;
    }

    return <<<EOT
<form name="filterControl" action="$this->mURL" method="get">
   <label for="filterby">$this->filterbylabel:</label> <select id="filterby" name="filterby">$filterChoicesHTML</select>
   <select name="filterneg"><option value="0" $filterNeg0Selected></option><option value="1" $filterNeg1Selected>$this->notlabel</option></select>
   <select name="filterop">$filterOpsHTML</select>
   <input type="text" name="filtertext" value="$this->filtertext"/>
   <input type="submit" value="$this->applyactionlabel"/>
   $nonDefaultParamsHTML
</form>
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
    <td><a href="$editHref">($this->editactionlabel</a> | <a href="$contribsHref">$this->contributionsactionlabel</a> | <a href="$logsHref">$this->logsactionlabel</a>)</td>
    <td><input class="selusr" type="checkbox" name="userids[]" value="$id"/></td>
    <td>$id</td>
    <td><a href="$userPageURL">$userName</a></td>
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
   * Get the HTML for page navigation (Previous 1 2 3 Next)
   *
   * @return String: HTML of page navigation
  */
  function getPageNavHTML()
  {
    $retv = '';
    if ($this->pagenum > 1) {
      $prevURL = $this->getURL(array('pagenum' => $this->pagenum - 1) + $this->mParams);
      $retv .= "<a href=\"$prevURL\">$this->previousactionlabel</a> ";
    }
    $i_max = min($this->pagemax, 10);
    for ($i = 1; $i <= $i_max; $i++) {
      if ($i == $this->pagenum) {
        $retv .= "<b>$i</b> ";
      } else {
        $pageURL = $this->getURL(array('pagenum' => $i) + $this->mParams);
        $retv .= "<a href=\"$pageURL\">$i</a> ";
      }
    }

    if ($this->pagenum < $this->pagemax) {
      $nextURL = $this->getURL(array('pagenum' => $this->pagenum + 1) + $this->mParams);
      $retv .= "<a href=\"$nextURL\">$this->nextactionlabel</a>";
    }
    return $retv;
  }

  /*
   * Get the HTML for page sizer (25 50 100 all)
   *
   * @return String: HTML of page sizer
  */
  function getPageSizerHTML()
  {
    $retv = '';

    $retv .= ' (';
    $pagesizes = array(25, 50, 100, 'all');
    foreach ($pagesizes as $otherPagesize) {
      if ($this->pagesize == $otherPagesize) {
        $retv .= "<b>$otherPagesize</b> |";
      } else {
        $pagesizeURL = $this->getURL(array('pagenum' => 1, 'pagesize' => $otherPagesize) + $this->mParams);
        $retv .= " <a href=\"$pagesizeURL\">$otherPagesize</a> |";
      }
    }
    $retv = substr($retv, 0, strlen($retv) - 2);

    $retv .= ')';

    return $retv;
  }
}
