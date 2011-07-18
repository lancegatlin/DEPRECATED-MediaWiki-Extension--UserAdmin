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

class MWPurge_1_16 extends MWPurge {
  
  public function purgeRevisionsSQL($whereSQL)
  {
    $revs_rows = $this->dbw->select('revision',array('rev_id','rev_page','rev_parent_id'),array($whereSQL));
    
    if($revs_rows->numRows() == 0)
      return;
    
//    echo "whereSQL=$whereSQL count=" . $revs_rows->numRows() . " " . "sql=" . $this->dbw->selectSQLText('revision',array('rev_id','rev_page','rev_parent_id'),array($whereSQL));
    $pageIDs = array();
    foreach($revs_rows as $row)
    {
//      echo "rev_id=$row->rev_id ";
      # Accumulate list of distinct page_ids of pages
      $pageIDs[] = $row->rev_page;
      
      # Rethread rev_parent_ids to skip this revision
      # lance.gatlin@gmail.com: TESTME
      $this->dbw->update('revision',array('rev_parent_id' => $row->rev_parent_id),array('rev_parent_id' => $row->rev_id));

      # Rethread page_latest for tihs revision, update page_touched to dump the page cache
      # lance.gatlin@gmail.com: TESTME
      $this->dbw->update('page',array('page_latest' => $row->rev_parent_id, 'page_touched' => $this->dbw->timestamp()),array('page_latest' => $row->rev_id));
    }
    $pageIDs_list = implode(',',array_unique($pageIDs));
    
    # Purge any text belonging to revisions
    # lance.gatlin@gmail.com: TESTME
    $this->dbw->deleteJoin('text','revision','old_id','rev_text_id', array($whereSQL));
    
    # Purge all revisions
    # lance.gatlin@gmail.com: TESTME
    $this->dbw->delete('revision',array($whereSQL));
    
    # Purge any pages that now have no revisions
    # lance.gatlin@gmail.com: TESTME
    if(strlen($pageIDs_list)> 0)
      $this->dbw->query("DELETE FROM $this->pageTable LEFT JOIN $this->revisionTable ON page_id=rev_page WHERE page_id IN ($pageIDs_list) AND rev_id IS NULL");
    
  }
  
  public function purgePagesSQL($whereSQL)
  {
    # Purge text, revisions and pages
    # lance.gatlin@gmail.com: TESTME
    $revs_rows = $this->dbw->query("SELECT rev_id FROM $this->revisionTable JOIN $this->pageTable ON rev_page=page_id WHERE $whereSQL");
    
    if($revs_rows->numRows() == 0)
      return;
    
    $revIDs = array();
    foreach($revs_rows as $row)
      $revIDs[] = $row->rev_id;
    $revIDs_list = implode(',',$revIDs);
    //$this->dbw->query("DELETE FROM $this->textTable JOIN $this->revisionTable ON old_id=rev_text_id WHERE rev_page IN ($revIDs_list)");
    $this->dbw->deleteJoin('text','revision','old_id','rev_text_id',array("rev_page IN ($revIDs_list)"));
    $this->dbw->deleteJoin('revision','page','rev_page','page_id',array($whereSQL));
    $this->dbw->delete('page',array($whereSQL));
  }

  public function purgeArchivedPagesSQL($whereSQL)
  {
    # Purge any text belonging to deleted pages
    # lance.gatlin@gmail.com: TESTME
    $this->dbw->deleteJoin('text', 'archive','old_id', 'ar_text_id', array($whereSQL));
    # Purge deleted pages 
    # lance.gatlin@gmail.com: TESTME
    $this->dbw->delete('archive',array($whereSQL));
  }
  
  public function purgeUsersSQL($whereSQL)
  {
    $userRows = $this->dbw->select('user',array('user_id'), array($whereSQL));
    $userIDs = array();
    $userNames = array();
    foreach($userRows as $userRow)
    {
      $userIDs[] = $userRow->user_id;
      $userNames[] = $user->getName();
    }
    $this->doPurgeUsers($userIDs, $userNames);
  }
  
  public function purgeUsers($userIDs)
  {
    $userNames = array();
    foreach($userIDs as $user_id)
    {
      $user = User::newFromID($user_id);
      $userNames[] = "'" . $user->getName() . "'";
    }
    $this->doPurgeUsers($userIDs, $userNames);
  }
  
  protected function doPurgeUsers($userIDs, $userNames)
  {
    if(count($userIDs) == 0)
      return;
    
    $userIDs_list = implode(',',$userIDs);
    $userNames_list = implode(',',$userNames);
    
    // Purge data associated with users
    $this->purgeUserInfo($userIDs_list);
    $this->purgeUserProperties($userIDs_list);
    $this->purgeWatchListForUser($userIDs_list);
    $this->purgeBlocksOnUser($userIDs_list);
    $this->purgeUserPageAndUserTalkPage($userNames_list);
    
    // Purge tracking related to users
    $this->purgeLogEntriesForUser($userIDs_list);
    $this->purgeRecentChangesByUser($userIDs_list);

    // Purge user's editing
    $this->purgeArchivedFilesUploadedByUser($userIDs_list);
    $this->purgeOldVersionsOfImagesUploadedByUser($userIDs_list);
    $this->purgeImagesUploadedByUser($userIDs_list);
    $this->purgeDeletedPagesCreatedByUser($userIDs_list);
    $this->purgeRevisionsByUser($userIDs_list);
  }
  
  public function purgeImagesSQL($whereSQL)
  { 
    # Purge files of any images
    # lance.gatlin@gmail.com: tested good 16Jul11
    $images_rows = $this->dbw->select('image', array('*'), array($whereSQL));
    
    if($images_rows->numRows() == 0)
      return;
    
    $imageNames = array();
    foreach($images_rows as $row)
    {
      $file = LocalFile::newFromRow($row, $this->repo);
      
      # Purge thumbnails and purge cache for pages using this image
      $file->purgeEverything();
      
      # Purge thumbnail directories
      // TODO: Mediawiki does not purge the directories used to store thumbnails
      
      $path = $file->getPath();
      if($path !== false && file_exists($path))
        unlink($path);
      
      $imageNames[] = "'" . $row->img_name . "'";
    }
    
    # Purge images
    # lance.gatlin@gmail.com: tested good 9Jul11
    $this->dbw->delete('image',array($whereSQL));
    
    # Purge old versions of these images
    $imageNames_list = implode (',',$imageNames);
    $this->purgeOldImagesSQL("oi_name IN ($imageNames_list)");
    $this->purgePagesSQL("page_title IN ($imageNames_list) AND page_namespace=$this->nsFile");
  }
  
  function purgeOldImagesSQL($whereSQL) 
  { 
    # Purge files of old version of any images uploaded by this user
    # lance.gatlin@gmail.com: tested good 16Jul11
    // MW is not adding the JOIN properly
//    $oldImagesByUser_rows = $this->dbw->select('oldimage', array('oldimage.*'), array('img_user' => $this->user_id),'DatabaseBase::select',array(),array('image'=> array('LEFT JOIN','oi_name=img_name')));
    $oldImages_rows = $this->dbw->query("SELECT * FROM $this->oldimageTable JOIN $this->imageTable ON oi_name=img_name WHERE $whereSQL");
    
    if($oldImages_rows->numRows() == 0)
      return;
    
    foreach($oldImages_rows as $row)
    {
      # Images that are old versions of an existing image use OldLocalFile path => $IP/images/archive      
      $oldFile = OldLocalFile::newFromRow($row, $this->repo);
      
      # Purge thumbnails and purge cache for pages using this image
      $oldFile->purgeEverything();
      
      # Purge thumbnail directories
      // TODO: Mediawiki does not purge the directories used to store thumbnails
      
      $path = $oldFile->getPath();
      if($path !== false && file_exists($path))
        unlink($path);
    }
    
    # Purge old version of any images created by this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $this->dbw->deleteJoin('oldimage','image','oi_name','img_name',array($whereSQL));
  }
  
  function purgeArchivedFilesSQL($whereSQL)
  { 
    # Purge files of archived images
    # lance.gatlin@gmail.com: TESTME
    $archivedImages_rows = $this->dbw->select('filearchive', array('*'), array($whereSQL));
    
    if($archivedImages_rows->numRows() == 0)
      return;
    
    foreach($archivedImages_rows as $row)
    {
      # Images that have been deleted use ArchivedFile path => $IP/images/deleted
      $archivedFile = ArchivedFile::newFromRow($row);
      
      // No path helper in ArchivedFile class
      // Path code taken from DeleteArchiveFiles maintenance class
      $key = $archivedFile->getKey();
      $path = $this->repo->getZonePath( 'deleted' ) . '/' . $this->repo->getDeletedHashPath( $key ) . $key;
      if($path !== false && file_exists($path))
        unlink($path);
    }
    
    # Purge the archived images from database
    # lance.gatlin@gmail.com: TESTME
    $this->dbw->delete('filearchive',array($whereSQL));
  }

  protected function purgeUserInfo($userIDs_list)
  {
    # Purge user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $this->dbw->delete('user',array("user_id IN ($userIDs_list)"));
  }
  
  protected function purgeUserProperties($userIDs_list)
  {
    # Purge properties for this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $this->dbw->delete('user_properties',array("up_user IN ($userIDs_list)"));
  }
  
  protected function purgeWatchListForUser($userIDs_list)
  {
    # Purge any watchlist entries for this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $this->dbw->delete('watchlist',array("wl_user IN ($userIDs_list)"));   
  }
  
  protected function purgeBlocksOnUser($userIDs_list)
  {
    # Zero id of any blocks on this user (preserve any specific IP blocks)
    # lance.gatlin@gmail.com: tested good 9Jul11
    $this->dbw->update('ipblocks', array( 'ipb_user' => 0),array("ipb_user IN ($userIDs_list)"));
  }
  
  protected function purgeLogEntriesForUser($userIDs_list)
  {
    # Zero id for any log entries for this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $this->dbw->update('logging', array( 'log_user' => 0),array("log_user IN ($userIDs_list)"));
  }
  
  protected function purgeRecentChangesByUser($userIDs_list)
  {
    # Purge any recent change entries for this user
    # lance.gatlin@gmail.com: tested good 9Jul11
    $this->dbw->delete('recentchanges',array("rc_user IN ($userIDs_list)"));
  }
  
  protected function purgeUserPageAndUserTalkPage($userNames_list)
  {
    $this->purgePagesSQL("page_title IN ($userNames_list) AND (page_namespace=$this->nsUser OR page_namespace=$this->nsUserTalk)");
  }
  
  protected function purgeDeletedPagesCreatedByUser($userIDs_list)
  {
    $this->purgeArchivedPagesSQL("ar_user IN ($userIDs_list)");
  }
  
  protected function purgeArchivedFilesUploadedByUser($userIDs_list)
  {
    $this->purgeArchivedFilesSQL("fa_user IN ($userIDs_list)");
    
    # Zero id for any images deleted by user but not created by user
    # lance.gatlin@gmail.com: NOT TESTED
    $this->dbw->update('filearchive', array( 'fa_deleted_user' => 0),array("fa_deleted_user IN ($userIDs_list)"));
  }
  
  protected function purgeOldVersionsOfImagesUploadedByUser($userIDs_list)
  {
    # Purge old revisions uploaded by this user where user uploaded a new revision to someone else's file
    $this->purgeOldImagesSQL("oi_user IN ($userIDs_list)");
  }
  
  protected function purgeImagesUploadedByUser($userIDs_list)
  {
    $this->purgeImagesSQL("img_user IN ($userIDs_list)");
  }
  
  protected function purgeRevisionsByUser($userIDs_list)
  {
    $this->purgeRevisionsSQL("rev_user IN ($userIDs_list)");
  }
}
?>
