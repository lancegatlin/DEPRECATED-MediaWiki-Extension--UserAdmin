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

class MWPurge_1_16 extends MWPurge {
  
  /*
   * Purge revisions and any empty pages
   * @param $whereSQL string of the WHERE clause to select from the revision table
   */
  public function purgeRevisionsSQL($whereSQL)
  {
    // This abomination produces the following SQL: 
    // SELECT rev_id,rev_page,rev_parent_id,page_latest,page_counter FROM `revision` LEFT JOIN `page` ON ((rev_page=page_id)) WHERE ($whereSQL)
    $revs_rows = $this->dbw->select(array('revision','page'),array('rev_id','rev_page','rev_parent_id','page_latest','page_counter'),array($whereSQL),'DatabaseBase::select',array(),array('page' => array('LEFT JOIN','rev_page=page_id')));

    if($revs_rows->numRows() == 0)
      return;
    
    # Sort by pages to allow detecting if all of a page's revisions are being completely purged
    foreach($revs_rows as $row)
    {
      if(!isset($pages[$row->rev_page]))
      {
        $pages[$row->rev_page] = array( 
            'page_counter' => $row->page_counter,
            'page_latest' => $row->page_latest,
            'revisions' => array($row),
         );
      }
      else
        $pages[$row->rev_page]['revisions'][] = $row;
    }
    
    $emptyPageIDs = array();
    $revIDsToDelete = array();
    foreach($pages as $page_id => $pageInfo)
    {
      # Detect if all of a page's revisions are being completely purged
      if($pageInfo['page_counter'] == count($pageInfo['revisions']))
      {
        # If yes, add it to the list to remove completely later
        $emptyPageIDs[] = $page_id;
      }
      else
      {
        # Otherwise, rethread and add each revision to list to purge
        foreach($pageInfo['revisions'] as $row)
        {
          # Rethread rev_parent_ids to skip this revision
          # lance.gatlin@gmail.com: 20Jul11
          $this->dbw->update('revision',array('rev_parent_id' => $row->rev_parent_id),array('rev_parent_id' => $row->rev_id));

          # If this revision was the page_latest for this revision, update page_latest and page_touched to dump the page cache
          # lance.gatlin@gmail.com: 20Jul11
          if($row->page_latest == $row->rev_id)
            $this->dbw->update('page',array('page_latest' => $row->rev_parent_id, 'page_touched' => $this->dbw->timestamp()),array('page_latest' => $row->rev_id));
          
          $revIDsToDelete[] = $row->rev_id;
        }
      }
    }
    
    # Purge revisions added above for individual purging
    if(count($revIDsToDelete))
      # lance.gatlin@gmail.com: 20Jul11
      $this->dbw->delete('revision',array('rev_id' => $revIDsToDelete));
    
    # Use purgePages to purge pages that have all revisions being purged
    if(count($emptyPageIDs) > 0)
      $this->purgePages($emptyPageIDs);
  }
  
  /*
   * Purge pages and their revisions
   * @param $whereSQL string of the WHERE clause to select from the page table
   */
  public function purgePagesSQL($whereSQL)
  {
    $pages_rows = $this->dbw->select('page', 'page_id', array($whereSQL));
    
    $pageIDs = array();
    foreach($pages_rows as $row)
      $pageIDs[] = $row->page_id;
    
    $this->purgePages($pageIDs);
  }
  
  /*
   * Purge pages and their revisions
   * @param $pageIDs array of page ids to purge
   */
  public function purgePages($pageIDs)
  {
    if(count($pageIDs) == 0)
      return;
    
    # Purge text, revisions and pages
    # lance.gatlin@gmail.com: TESTME
    $this->dbw->deleteJoin('text','revision','old_id','rev_text_id',array('rev_page' => $pageIDs));
    $this->dbw->delete('revision',array('rev_page' => $pageIDs));
    $this->dbw->delete('page',array('page_id' => $pageIDs));
  }

  /*
   * Purge deleted pages
   * @param $whereSQL string of the WHERE clause to select from the archive table
   */
  public function purgeArchivedPagesSQL($whereSQL)
  {
    # Purge any text belonging to deleted pages
    # lance.gatlin@gmail.com: TESTME
    $this->dbw->deleteJoin('text', 'archive','old_id', 'ar_text_id', array($whereSQL));
    # Purge deleted pages 
    # lance.gatlin@gmail.com: TESTME
    $this->dbw->delete('archive',array($whereSQL));
  }
  
  /*
   * Purge users and all related stuff
   * @param $whereSQL string of the WHERE clause to select from the user table
   */
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
  
  /*
   * Purge users and all related stuff
   * @param $userIDs array of user ids to purge
   */
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
  
  /*
   * Do the actual purge of users and all related stuff
   * @param $userIDs array of user ids to purge
   * @param $userNames array of user names to purge (corresponding with $userIDs)
   */
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
  
  /*
   * Purge images, their actual files, thumbnails, their old versions and their pages
   * @param $whereSQL string of the WHERE clause to select from the image table
   */
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
  
  /*
   * Purge an old version of a file upload and its actual file
   * @param $oi_sha1 SHA1 of the file to purge
   */
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
  
  /*
   * Purge deleted file uploads and their archived files
   * @param $whereSQL string of the WHERE clause to select from the filearchive table
   */
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
