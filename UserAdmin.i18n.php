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

$aliases = array();
$messages = array();
 
$aliases['en'] = array(
        'UserAdmin' => array( 'User Administration' ),
        'AddUser' => array( 'Add User' ),
        'PurgeUser' => array( 'Purge User' ),
        'EditUser' => array( 'Edit User' ),
);
 
$messages['en'] = array(
        'useradmin' => 'User Administration',
        'useradmin-desc' => 'UserAdmin is a MediaWiki extension which allows administrators to add users, permanently remove spam or unused accounts, change user passwords, edit user details, send reset password or welcome emails and list users with pagination and filter controls. This extension is primarily for administrators of private wikis that require tighter control of user accounts.',
        'adduser' => 'Add user',
        'adduser_desc' => 'Create a new user',
        'purgeuser' => 'Purge user',
        'purgeuser-desc' => 'Purge a user',
        'edituser' => 'Edit user',
        'edituser_desc' => 'Edit a user',
        'massblock' => 'Block users',
        'massblock_desc' => 'Block multiple users',
    
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
        'uadm-groupsfield' => 'Effective Groups',
        'uadm-emailauthdatefield' => 'Email Authenticated Date',
        'uadm-reasonfield' => 'Reason',
        'uadm-passwordfield' => 'Password',
        'domainfield' => 'Domain',
    
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
        'uadm-purgeactionlabel' => 'Purge',
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
        'uadm-reasonlabel' => 'Reason',
    
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
        'uadm-invaliddomainmsg' => 'Invalid domain',
        'uadm-mailerrormsg' => 'Mail failure: $1',
        'uadm-externalupdateerrormsg' => 'External authentication plug-in update failed (wgAuth).',
    
        // Common log messages
        'uadm-emailwelcomelog' => 'sent welcome email with random password to [[$1]]',
        'uadm-changeduserpasswordlog' => 'changed password for [[$1]]',
        'uadm-changeduseremaillog' => 'changed email for [[$1]] from $2 to $3',
        'uadm-changedusernamelog' => 'changed user name of user id $1 from $2 to $3',
        'uadm-changeduserrealnamelog' => 'changed real name of [[$1]] from $2 to $3',
        'uadm-emailpasswordlog' => 'sent random password email to [[$1]]',
        'uadm-userspurgedlog' => 'purged the following users: $1',
    
        // User admin panel specific
        'uadm-uappurgeactionlabel' => 'Purge',
        'uadm-uapnewuseractionlabel' => 'Add User',
        'uadm-filterbylabel' => 'Filter by',
        
        // Edit user specific
        'uadm-edituserlabel' => 'Edit user',
        'uadm-saveuserlabel' => 'Save User',
    
        'uadm-changestogroupsuccessmsg' => 'Changes to groups for <b>$1</b> have been saved.',
        'uadm-changestousersuccessmsg' => 'Changes to <b>$1</b> have been saved.',
        'uadm-passwordactionlabel' => 'Password action',

        // Add user specific
        'uadm-newusersuccessmsg' => 'Successfully created user <b>$1</b>.',
        'uadm-adduserlabel' => 'Add a user',
        'uadm-hookblocknewusermsg' => 'AbortNewAccount hook blocked account creation: $1',
        'uadm-wgauthaddfailmsg' => 'wgAuth addUser failed',
        'uadm-createextacctfailmsg' => 'Account creation is not allowed by external authorization plug-in (wgAuth).',
    
        // Purge user specific
        'uadm-purgeauserlabel' => 'Purge a user:',
        'uadm-confirmpurgelabel' => 'Confirm Purge',
        'uadm-confirmpurgewarningmsg' => 'WARNING: This operation cannot be undone!<br/>WARNING: ALL pages, revisions, and file uploads created by the following users will be permanently removed from the database and file system.<br/>WARNING: Backup the database before proceeding!',
        'uadm-nopurgeadminmsg' => 'Purging of administrator account $1 is disallowed.',
        'uadm-invalidversionmsg' => 'Purge user special page does not support this MediaWiki version.',
        'uadm-purgesuccessmsg' => 'All requested users have been purged.',
    
        // Block user specific
        'uadm-confirmblocklabel' => 'Confirm Block',
);