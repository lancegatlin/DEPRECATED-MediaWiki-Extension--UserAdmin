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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
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
  
  public function initStaticVars()
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
  
  protected function getTableInfo()
  {
    return self::$tables;
  }
  
  protected function getSingleIDWhereSQL($tableName, $id_value)
  {
    $tableInfo = $this->getTableInfo();
    $idField = $tableInfo[$tableName]['idField'];
    $mwTableName = $tableInfo[$tableName]['tableName'];
    
    return "$idField='$id_value'";
  }
  
  protected function getMultiIDWhereSQL($tableName, $IDs)
  {
    $tableInfo = $this->getTableInfo();
    $idField = $tableInfo[$tableName]['idField'];
    $mwTableName = $tableInfo[$tableName]['tableName'];
    $IDs_list = implode(',',$IDs);
    
    return "$idField IN ($IDs_list)";
  }
  
  public function purgeUser($user_id)        { $this->purgeUsersSQL($this->getSingleIDWhereSQL('user',$user_id)); }
  public function purgeUsers($userIDs)       { $this->purgeUsersSQL($this->getMultiIDWhereSQL('user',$userIDs)); }
  public abstract function purgeUsersSQL($whereSQL);
  
  public function purgePage($page_id)        { $this->purgePagesSQL($this->getSingleIDWhereSQL('page',page_id)); }
  public function purgePages($pageIDs)       { $this->purgePagesSQL($this->getMultiIDWhereSQL('page',$pageIDs)); }
  public abstract function purgePagesSQL($whereSQL);
  
  public function purgeArchivedPage($ar_id)        { $this->purgeArchivedPagesSQL($this->getSingleIDWhereSQL('archive',$ar_id)); }
  public function purgeArchivedPages($arIDs)       { $this->purgeArchivedPagesSQL($this->getMultiIDWhereSQL('archive',$arIDs)); }
  public abstract function purgeArchivedPagesSQL($whereSQL);
  
  public function purgeRevision($rev_id)     { $this->purgeRevisionsSQL($this->getSingleIDWhereSQL('revision',$rev_id)); }
  public function purgeRevisions($revIDs)    { $this->purgeRevisionsSQL($this->getMultiIDWhereSQL('revision',$revIDs)); }
  public abstract function purgeRevisionsSQL($whereSQL);
  
  public function purgeImage($img_id)        { $this->purgeImagesSQL($this->getSingleIDWhereSQL('image',$img_id)); }  
  public function purgeImages($imgIDs)       { $this->purgeImagesSQL($this->getMultiIDWhereSQL('image',$imgIDs)); }
  public abstract function purgeImagesSQL($whereSQL);
  
  public function purgeOldImage($oi_sha1)    { $this->purgeOldImagesSQL($this->getSingleIDWhereSQL('oldimage',$oi_sha1)); }
  public function purgeOldImages($oiSHAs)    { $this->purgeOldImagesSQL($this->getMultiIDWhereSQL('oldimage',$oiSHAs)); }
  public abstract function purgeOldImagesSQL($whereSQL);
  
  public function purgeArchivedFile($fa_id)  { $this->purgeArchivedFilesSQL($this->getSingleIDWhereSQL('filearchive',$fa_id)); }
  public function purgeArchivedFiles($faIDs) { $this->purgeArchivedFilesSQL($this->getMultiIDWhereSQL('filearchive',$faIDs)); }
  public abstract function purgeArchivedFilesSQL($whereSQL);
}

?>
