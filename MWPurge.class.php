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

abstract class MWPurge {
  var $dbw;
  
  static $initd = false;
  var $repo;
  var $nsUser;
  var $nsUserTalk;
  var $nsMedia;
  static $tables = array(
      'page' => array('idField' => 'page_id', 'tableName' => null),
      'revision' => array('idField' => 'rev_id', 'tableName' => null),
      'oldimage' => array('idField' => 'oi_sha1', 'tableName' => null),
      'image' => array('idField' => 'img_sha1', 'tableName' => null),
      'filearchive' => array('idField' => 'fa_id', 'tableName' => null),
      'user' => array('idField' => 'user_id', 'tableName' => null),
      'text' => array('idField' => 'old_id', 'tableName' => null),
      'archive' => array('idField' => 'ar_id', 'tableName' => null),
  );
  
  /*
   * Initialize internal static variables
   */
  private function initStaticVars()
  {
    if(self::$initd)
      return;
    
    # Master for write queries
    $dbr = wfGetDB(DB_SLAVE);
    
    $this->repo = RepoGroup::singleton()->getLocalRepo();
    
    foreach(self::$tables as $tableName => &$tableInfo)
      $tableInfo['tableName'] = $dbr->tableName($tableName);
    
    self::$initd = true;
  }
  
  public function __construct()
  {
    $this->initStaticVars();
    
    # Master for write queries
    $this->dbw = wfGetDB(DB_MASTER);
    
    foreach(self::$tables as $tableName => $tableInfo)
    {
      $propertyName = $tableName . 'Table';
      $this->$propertyName = $tableInfo['tableName'];
    }
    
    $this->nsUser = NS_USER;
    $this->nsUserTalk = NS_USER_TALK;
    $this->nsFile = NS_FILE;
  }
  
  /*
   * Get information on MW tables
   * @return array( 'tableName' => array('idField' => string, 'tableName' => string(quoted table name))
   */
  protected function getTableInfo()
  {
    return self::$tables;
  }
  
  /*
   * Build a generic WHERE clause for a single ID
   * @param $tableName unquoted table name 
   * @param $id_value 
   * @return string WHERE clause
   */
  protected function getSingleIDWhereSQL($tableName, $id_value)
  {
    $tableInfo = $this->getTableInfo();
    $idField = $tableInfo[$tableName]['idField'];
    $mwTableName = $tableInfo[$tableName]['tableName'];
    
    return "$idField='$id_value'";
  }
  
  /*
   * Build a generic WHERE clause for multiple IDs
   * @param $tableName unquoted table name 
   * @param $IDs array of ids
   * @return string WHERE clause
   */
  protected function getMultiIDWhereSQL($tableName, $IDs)
  {
    $tableInfo = $this->getTableInfo();
    $idField = $tableInfo[$tableName]['idField'];
    $mwTableName = $tableInfo[$tableName]['tableName'];
    $IDs_list = implode(',',$IDs);
    
    return "$idField IN ($IDs_list)";
  }
  
  /*
   * Purge a user and all related stuff
   * @param $user_id User id to purge
   */
  public function purgeUser($user_id)        { $this->purgeUsersSQL($this->getSingleIDWhereSQL('user',$user_id)); }
  /*
   * Purge users and all related stuff
   * @param $userIDs array of user ids to purge
   */
  public function purgeUsers($userIDs)       { $this->purgeUsersSQL($this->getMultiIDWhereSQL('user',$userIDs)); }
  /*
   * Purge users and all related stuff
   * @param $whereSQL string of the WHERE clause to select from the user table
   */
  public abstract function purgeUsersSQL($whereSQL);
  
  /*
   * Purge a page and its revisions
   * @param $page_id Page id to purge
   */
  public function purgePage($page_id)        { $this->purgePagesSQL($this->getSingleIDWhereSQL('page',page_id)); }
  /*
   * Purge pages and their revisions
   * @param $pageIDs array of page ids to purge
   */
  public function purgePages($pageIDs)       { $this->purgePagesSQL($this->getMultiIDWhereSQL('page',$pageIDs)); }
  /*
   * Purge pages and their revisions
   * @param $whereSQL string of the WHERE clause to select from the page table
   */
  public abstract function purgePagesSQL($whereSQL);
  
  /*
   * Purge a deleted page
   * @param $ar_id Archived (deleted) page id to purge
   */
  public function purgeArchivedPage($ar_id)        { $this->purgeArchivedPagesSQL($this->getSingleIDWhereSQL('archive',$ar_id)); }
  /*
   * Purge deleted pages
   * @param $arIDs array of archived (deleted) pages ids to purge
   */
  public function purgeArchivedPages($arIDs)       { $this->purgeArchivedPagesSQL($this->getMultiIDWhereSQL('archive',$arIDs)); }
  /*
   * Purge deleted pages
   * @param $whereSQL string of the WHERE clause to select from the archive table
   */
  public abstract function purgeArchivedPagesSQL($whereSQL);
  
  /*
   * Purge a revision and its page if empty
   * @param $rev_id Revision id to purge
   */
  public function purgeRevision($rev_id)     { $this->purgeRevisionsSQL($this->getSingleIDWhereSQL('revision',$rev_id)); }
  /*
   * Purge revisions and any empty pages
   * @param $revIDs array of revision ids to purge
   */
  public function purgeRevisions($revIDs)    { $this->purgeRevisionsSQL($this->getMultiIDWhereSQL('revision',$revIDs)); }
  /*
   * Purge revisions and any empty pages
   * @param $whereSQL string of the WHERE clause to select from the revision table
   */
  public abstract function purgeRevisionsSQL($whereSQL);
  
  /*
   * Purge a file upload, actual file, thumbnails, its old versions and its page
   * @param $img_id Image (file upload) id to purge
   */
  public function purgeImage($img_id)        { $this->purgeImagesSQL($this->getSingleIDWhereSQL('image',$img_id)); }  
  /*
   * Purge images, their actual files, thumbnails, their old versions and their pages
   * @param $imgIDs array of user ids to purge
   */
  public function purgeImages($imgIDs)       { $this->purgeImagesSQL($this->getMultiIDWhereSQL('image',$imgIDs)); }
  /*
   * Purge images, their actual files, thumbnails, their old versions and their pages
   * @param $whereSQL string of the WHERE clause to select from the image table
   */
  public abstract function purgeImagesSQL($whereSQL);
  
  /*
   * Purge an old version of a file upload and its actual file
   * @param $oi_sha1 SHA1 of the file to purge
   */
  public function purgeOldImage($oi_sha1)    { $this->purgeOldImagesSQL($this->getSingleIDWhereSQL('oldimage',$oi_sha1)); }
  /*
   * Purge old versions of file uploads and their actual files
   * @param $oi_sha1 SHA1 of the file to purge
   */
  public function purgeOldImages($oiSHAs)    { $this->purgeOldImagesSQL($this->getMultiIDWhereSQL('oldimage',$oiSHAs)); }
  /*
   * Purge old versions of file uploads and their actual files
   * @param $whereSQL string of the WHERE clause to select from the oldimage table
   */
  public abstract function purgeOldImagesSQL($whereSQL);
  
  /*
   * Purge a deleted file upload and its archived file
   * @param $fa_id Archived (deleted) file upload id to purge
   */
  public function purgeArchivedFile($fa_id)  { $this->purgeArchivedFilesSQL($this->getSingleIDWhereSQL('filearchive',$fa_id)); }
  /*
   * Purge deleted file uploads and their archived files
   * @param $faIDs array of archived (deleted) file upload ids to purge
   */
  public function purgeArchivedFiles($faIDs) { $this->purgeArchivedFilesSQL($this->getMultiIDWhereSQL('filearchive',$faIDs)); }
  /*
   * Purge deleted file uploads and their archived files
   * @param $whereSQL string of the WHERE clause to select from the filearchive table
   */
  public abstract function purgeArchivedFilesSQL($whereSQL);
}

?>
