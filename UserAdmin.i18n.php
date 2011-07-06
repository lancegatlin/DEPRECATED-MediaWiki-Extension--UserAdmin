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

$aliases = array();
$messages = array();
 
$aliases['en'] = array(
        'UserAdmin' => array( 'User Administration' ),
        'AddUser' => array( 'Add User' ),
        'DeleteUser' => array( 'Delete User' ),
        'EditUser' => array( 'Edit User' ),
);
 
$messages['en'] = array(
        'useradmin' => 'User Administration',
        'useradmin-desc' => 'User administration: add/delete users, change password, change user name/email, email reset password/welcome message, etc',
        'adduser' => 'Add user',
        'adduser_desc' => 'Create a new user',
        'deleteuser' => 'Delete user',
        'deleteuser-desc' => 'Delete a user',
        'edituser' => 'Edit user',
        'edituser_desc' => 'Edit a user',
    
        // Fields
        'uadm-useridfield' => 'ID',
        'uadm-usernamefield' => 'User Name',
        'uadm-realnamefield' => 'Real Name',
        'uadm-emailfield' => 'Email',
        'uadm-createddatefield' => 'Created Date',
        'uadm-usertoucheddatefield' => 'User Touched Date',
        'uadm-editcountfield' => 'Edit Count',
        'uadm-usertoucheddatehelp' => 'The last time a user made a change on the site, including logins, changes to pages (any namespace), watchlistings, and preference changes.',
        'uadm-lasteditdatefield' => 'Last Edit Date',
        'uadm-groupsfield' => 'Groups',
        'uadm-emailauthdatefield' => 'Email Authenticated Date',
        'uadm-reasonfield' => 'Reason',
        'uadm-passwordfield' => 'Password',
    
        // Common actions
        'uadm-previousactionlabel' => 'Previous',
        'uadm-nextactionlabel' => 'Next',
        'uadm-blockactionlabel' => 'Block',
        'uadm-emailpasswordactionlabel' => 'Email Password',
        'uadm-applyactionlabel' => 'Apply',
        'uadm-editactionlabel' => 'Edit',
        'uadm-logsactionlabel' => 'Logs',
        'uadm-talkactionlabel' => 'Talk',
        'uadm-backactionlabel' => 'Back',
        'uadm-deleteactionlabel' => 'Delete',
        'uadm-previewactionlabel' => 'Preview',
        'uadm-ipsactionlabel' => 'IPs',
        'uadm-contributionsactionlabel' => 'Contribs',

        // Common labels
        'uadm-notlabel' => 'NOT',
        'uadm-pendinglabel' => 'Pending',
        'uadm-requiredlabel' => '(Required)',
        'uadm-finduserlabel' => 'Find a user',
        'uadm-enterusernamelabel' => 'Enter a username',
        'uadm-editgroupslabel' => 'Edit groups',
        'uadm-editpasswordlabel' => 'Edit password',
        'uadm-setpasswordforuserlabel' => 'Set password for user',
        'uadm-passwordlabel' => 'Password',
        'uadm-verifypasswordlabel' => 'Verify password',
        'uadm-emailpasswordlabel' => 'Email random password message to user',
        'uadm-emailwelcomelabel' => 'Email welcome message with random password to user',
        'uadm-nochangetopasswordlabel' => 'No change to password',
        'uadm-subjectlabel' => 'Subject',
        'uadm-bodylabel' => 'Body',
    
        // Common messages
        'uadm-returntomsg' => 'Return to [[$1]]',
        'uadm-passwordchangesuccessmsg' => 'Password for <b>$1</b> has been changed.',
        'uadm-passwordemailsuccessmsg' => 'Password email for <b>$1</b> sent to $2.',
        'uadm-welcomeemailsuccessmsg' => 'Welcome email for <b>$1</b> sent to $2.',
        'uadm-newuserreasonmsg' => 'New user creation.',
    
        // Common errors
        'uadm-usernameinusemsg' => 'User name \'$1\' is already in use.',
        'uadm-passworderrormsg' => 'Password Error:',
        'uadm-passwordsmustmatchmsg' => 'Passwords must match.',
        'uadm-invaliduseridmsg' => 'Invalid user id.',
        'uadm-usernoexistmsg' => 'User <b>$1</b> does not exist.',
        'uadm-fieldisrequiredmsg' => '$1 is required.',
        'uadm-failedtoloaduserfromidmsg' => 'Failed to load user from id=$1.',
        'uadm-formsubmissionerrormsg' => 'Error in form submission.',
        'uadm-invalidusernamemsg' => 'Invalid user name.',
        'uadm-invalidemailmsg' => 'Invalid email address.',
        'uadm-invalidpasswordmsg' => 'Invalid password.',
    
        // Common log messages
        'uadm-emailwelcomelog' => 'sent welcome email with random password to [[$1]]',
        'uadm-changeduserpasswordlog' => 'changed password for [[$1]]',
        'uadm-changeduseremaillog' => 'changed email for [[$1]] from $2 to $3',
        'uadm-changedusernamelog' => 'changed user name of user id $1 from $2 to $3',
        'uadm-changeduserrealnamelog' => 'changed real name of [[$1]] from $2 to $3',
        'uadm-emailpasswordlog' => 'sent random password email to [[$1]]',
    
        // User admin panel specific
        'uadm-uapdeleteactionlabel' => '(-) Delete',
        'uadm-uapnewuseractionlabel' => '(+) Add User',
        'uadm-filterbylabel' => 'Filter by',
        
        // Edit user specific
        'uadm-edituserlabel' => 'Edit user',
        'uadm-reasonlabel' => 'Reason for changes',
        'uadm-saveuserlabel' => 'Save User',
    
        'uadm-changestogroupsuccessmsg' => 'Changes to groups for <b>$1</b> have been saved.',
        'uadm-changestousersuccessmsg' => 'Changes to <b>$1</b> have been saved.',
        'uadm-passwordactionlabel' => 'Password action',

        // Add user specific
        'uadm-newusersuccessmsg' => 'Successfully created user <b>$1</b>.',
        'uadm-adduserlabel' => 'Add a user',
    
        // Delet user specific
        'uadm-deleteauserlabel' => 'Delete a user:',
        'uadm-confirmdeletelabel' => 'Confirm Delete',
        'uadm-confirmdeletewarningmsg' => 'WARNING: All pages, revisions, and file uploads owned by the following users will also be deleted:',
        'uadm-nodeleteadminmsg' => 'Deletion of administrator account $1 is disallowed.'
);