<?php
/**
 * Implements a set of utils (helpers) not directly related to Content nor Commerce.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Drufony\CoreBundle\Exception\ContentTypeNotFound;

// Class constants
// Defines constants if undefined. Those constants could be defined in
// a setting file.
defined('DEFAULT_SECTION_ORDER_CRITERIA') or define('DEFAULT_SECTION_ORDER_CRITERIA','DATE');
defined('DEFAULT_SECTION_ORDER_MODE') or define('DEFAULT_SECTION_ORDER_MODE','DESC');
defined('DEFAULT_ALLOWED_EXTENSIONS') or define('DEFAULT_ALLOWED_EXTENSIONS','jpg jpeg gif png bmp txt doc docx xls xlsx pdf ppt pptx pps odt ods odp');

/**
 * This is a set of utils (helpers) which aren't directly related with Content or Commerce.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Utils
{
    static public function checkEmail($email)
    {
        return (filter_var($email, FILTER_VALIDATE_EMAIL)!==false);
    }

    /**
     * Function to save any type of entity
     * For each type define a private method to store data for this type, the name of function
     * will be {$type}DataSave.
     * @param array $data An array with data to save, $data['contentType'] must be defined
     * @return integer with id of created / modified content, 0 if it crashed
     */
    static public function saveData($node)
    {
        if (!empty($node['contentType']) && method_exists(new Utils(), $node['contentType'] . 'DataSave')) {
            $data = $node;
            $data['lang'] = !empty($data['lang']) ? $data['lang'] : getLang();
            list($data['nid'], $data['vid']) = self::saveNode($data);
            $extraFieldsName = array('links', 'images', 'attachments', 'parents', 'videos', 'varieties');
            $extraFields     = array();
            foreach ($extraFieldsName as $field) {
                if (!empty($data[$field])) {
                    $extraFields[$field] = $data[$field];
                    unset($data[$field]);
                }
            }

            $remainingExtrafields = array('freeTags', 'tags');
            foreach ($remainingExtrafields as $field) {
                if (array_key_exists($field, $data)) {
                    $extraFields[$field] = $data[$field];
                    unset($data[$field]);
                }
            }

            //Clean attributes not mapable into database
            $sql = "SHOW COLUMNS FROM {$node['contentType']}";
            $mappedFields = db_fetchAllColumn($sql);
            $contentData = array_intersect_key($data, array_flip($mappedFields));

            $dataToSave = self::_checkFields($contentData);

            $dataToSave['id']          = call_user_func("self::${node['contentType']}DataSave", $dataToSave);
            $dataToSave['contentType'] = $node['contentType'];
            $dataToSave['url']         = $node['url'];

            self::createUrlFriendly($dataToSave);

            if(!empty($node['latitude']) && !empty($node['longitude'])) {
                $locationData = array('nid' => $data['nid'], 'latitude' => $node['latitude'], 'longitude' => $node['longitude']);
                if($node['location']) {
                    $locationData['id'] = $node['location']['id'];
                }
                ContentUtils::saveLocation($locationData);
            }

            //Save extra fields
            foreach($extraFields as $field => $value) {
                if (method_exists(new Utils(), $field . 'FieldSave')) {
                    call_user_func('self::' . $field . 'FieldSave', $value, $dataToSave);
                }
                else {
                    l(WARNING, 'Function ' . $field . 'FieldSave has not been defined');
                }
            }

            // TODO Add function MajorChange
            if (isset($node['majorChange']) && $node['majorChange']) {
                //Create task for each language to review translation
                $languages = Locale::getAllLanguages();
                foreach ($languages as $lang => $langName) {
                    if ($lang != $data['lang']) {
                        $title       = t('Translation review needed in @lang for content @nid', array('@lang' => $langName, '@nid' => $data['nid']));
                        $description = t('Node <a href="@url">@nid</a> needs a translate review in @lang language', array(
                            '@url' => getRouter()->generate('drufony_content_actions', array(
                                'id'   => $data['nid'],
                                'action' => 'edit',
                                'contentType' => $node['contentType'],
                                'lang' => $data['lang'],
                                'langToTranslate' => $data['lang'])),
                            '@nid'  => $data['nid'],
                            '@lang' => $langName));
                        $assigned = $data['uid'];
                        self::createTask($title, $description, $assigned);
                    }
                }
            }

            return $dataToSave['nid'];
        }
    }

    static private function _checkFields($contentData) {
        $dataToSave = array();

        foreach ($contentData as $fieldName => $fieldValue) {
            if (is_object($fieldValue) && get_class($fieldValue) == 'DateTime') {
                $dataToSave[$fieldName] = $fieldValue->format(DEFAULT_PUBLICATION_DATE_FORMAT);
            }
            else {
                $dataToSave[$fieldName] = $fieldValue;
            }
        }

        return $dataToSave;
    }

    /**
     * Function to store data for page nodes
     * @pararm array $data Data to store in database
     */
    static private function pageDataSave($node) {
        $data = $node;

        //Save content type
        if (empty($data['id'])) {
            $data['publicationDate'] = date(DEFAULT_PUBLICATION_DATE_FORMAT);
            if (($data['id'] = db_insert('page', $data)) == FALSE) {
                l(ERROR, 'Error creating page, some fields cannot be found or not existent in database');
            }
            else {
                l(INFO, 'Page created with id ' . $data['id'] . ' successfully');
            }
        }
        else {
            //Get id for content type
            $sql   = 'SELECT id FROM page WHERE nid = ? AND lang = ?';
            $data['id'] = db_fetchColumn($sql, array($data['nid'], $data['lang']));
            if (is_null($data['id'])) {
                l(ERROR, 'Page not found to modify');
            }
            else {
                if(!db_update('page', $data, array('nid' => $data['nid'], 'lang' => $data['lang']))) {
                    l(ERROR, 'Error updating page, some fields cannot be found or not existent in database');
                }
                else {
                    l(INFO, 'Page modified with id ' . $data['id'] . ' successfully');
                }
            }
        }

        return $data['id'];
    }

    /**
     * Function to store data for section nodes
     * @pararm array $data Data to store in database
     */
    static private function sectionDataSave($node) {
        $data = $node;

        //Save content type
        if (empty($data['id'])) {
            $data['publicationDate'] = date(DEFAULT_PUBLICATION_DATE_FORMAT);
            $data['orderCriteria']   = DEFAULT_SECTION_ORDER_CRITERIA;
            $data['orderMode']       = DEFAULT_SECTION_ORDER_MODE;
            if (($data['id'] = db_insert('section', $data)) === FALSE) {
                l(ERROR, 'Error creating section, some fields cannot be found or not existent in database');
            }
            else {
                l(INFO, 'Section created with id ' . $data['id'] . ' successfully');
            }
        }
        else {
            //Get id for content type
            $sql = 'SELECT id FROM section WHERE nid = ? AND lang = ?';
            $data['id'] = db_fetchColumn($sql, array($data['nid'], $data['lang']));
            if (is_null($data['id'])) {
                l(ERROR, 'Section not found to modify');
            }
            else {
                if (!db_update('section', $data, array('nid' => $data['nid'], 'lang' => $data['lang']))) {
                    l(ERROR, 'Error updating section, some fields cannot be found or not existent in database');
                }
                else {
                    l(INFO, 'Section modified with id ' . $data['id'] . ' successfully');
                }
            }
        }

        return $data['id'];
    }

    /**
     * Function to store data for item nodes
     * @pararm array $data Data to store in database
     */
    static private function itemDataSave($node) {
        $data = $node;

        $data['dateCalendar']    = !empty($data['dateCalendar']) ? $data['dateCalendar']->format(DEFAULT_PUBLICATION_DATE_FORMAT) : NULL;
        //Save content type
        if (empty($data['id'])) {
            $data['publicationDate'] = date(DEFAULT_PUBLICATION_DATE_FORMAT);
            if (($data['id'] = db_insert('item', $data)) === FALSE) {
                l(ERROR, 'Error creating item, some fields cannot be found or not existent in database');
            }
            else {
                l(INFO, 'Item created with id ' . $data['id'] . ' successfully');
            }
        }
        else {
            //Get id for content type
            $sql = 'SELECT id FROM item WHERE nid = ? AND lang = ?';
            $data['id'] = db_fetchColumn($sql, array($data['nid'], $data['lang']));
            if (is_null($data['id'])) {
                l(ERROR, 'Item not found to modify');
            }
            else {
                db_update('item', $data, array('nid' => $data['nid'], 'lang' => $data['lang']));
            }
        }

        return $data['id'];
    }

    /**
     * Function to store data for item nodes
     * @pararm array $data Data to store in database
     */
    static private function productDataSave($node) {
        $data = $node;

        $data['dateCalendar']    = !empty($data['dateCalendar']) ? $data['dateCalendar']->format(DEFAULT_PUBLICATION_DATE_FORMAT) : NULL;
        //Save content type
        if (empty($data['id'])) {
            $data['publicationDate'] = date(DEFAULT_PUBLICATION_DATE_FORMAT);
            if (($data['id'] = db_insert('product', $data)) === FALSE) {
                l(ERROR, 'Error creating product, some fields cannot be found or not existent in database');
            }
            else {
                l(INFO, 'Product created with id ' . $data['id'] . ' successfully');
            }
        }
        else {
            //Get id for content type
            $sql = 'SELECT id FROM product WHERE nid = ? AND lang = ?';
            $data['id'] = db_fetchColumn($sql, array($data['nid'], $data['lang']));
            if (is_null($data['id'])) {
                l(ERROR, 'Product not found to modify');
            }
            else {
                if (!db_update('product', $data, array('nid' => $data['nid'], 'lang' => $data['lang']))) {
                    l(ERROR, 'Error updating page, some fields cannot be found or not existent in database');
                }
                else {
                    l(INFO, 'Section modified with id ' . $data['id'] . ' successfully');
                }
            }
        }

        return $data['id'];
    }

    /**
     * Function to create Drupal node, is common to every node entities
     */
    static private function saveNode($data) {
        //Save into table node
        $now            = time();
        $newRevision    = FALSE;
        $isNew          = TRUE;
        $node           = array();
        $node_fields    = array('language', 'title', 'uid', 'status', 'changed', 'comment', 'promote', 'sticky', 'tnid', 'translate');
        $revisionFields = array('nid', 'uid', 'title', 'log', 'timestamp', 'status', 'comment', 'promote', 'sticky');
        //New node
        if (empty($data['nid'])) {
            if (self::_canSaveNode($data)) {
                $newRevision = TRUE;
                $node = array(
                    'type'      => $data['contentType'],
                    'language'  => $data['lang'],
                    'title'     => $data['title'],
                    'uid'       => $data['uid'],
                    'status'    => !empty($data['published']) ? $data['published'] : 1,
                    'created'   => $now,
                    'changed'   => $now,
                );
                $data['nid'] = $node['nid'] = db_insert('node', $node);
                l(INFO, 'Node with nid ' . $data['nid'] . ' create successfully');
            }
            else {
                l(ERROR, 'Error node cannot be created because some fields are missing');
            }
        }
        else { //Modification
            $isNew = FALSE;
            $node = array_intersect_key($data, array_flip($node_fields));
            if (!empty($data['lang'])) {
                $node['language'] = $data['lang'];
            }
            //If it is a new revision
            if (isset($data['revision']) && $data['revision']) {
                $newRevision = TRUE;
            }
            db_update('node', $node, array('nid' => $data['nid']));
            $node['nid'] = $data['nid'];
        }

        //Save node revision
        // FIXME Remove node revision related code from save node method
        if ($newRevision) {
            $nodeRevision['log'] = !empty($data['log']) ? $data['log'] : '';
            $nodeRevision['timestamp'] = $now;
            if (!empty($data['nid'])) {
                //For updates and new revision required we need to load node
                $sql = "SELECT * FROM node
                    WHERE nid = ?";
                if (($node = db_executeQuery($sql, array($data['nid']))->fetch()) === FALSE) {
                    l(ERROR, 'Node nid not existent to modify');
                }
            }
            $nodeRevision += $node;
            $nodeRevision  = array_intersect_key($nodeRevision, array_flip($revisionFields));
            $vid = $revision['vid'] = db_insert('node_revision', $nodeRevision);
            if (!$vid) {
                l(WARNING, 'Error creating node_revision');
            }
            else {
                l(INFO, 'Node revision with vid ' . $vid . ' created successfully');
                if(!db_update('node', $revision, array('nid' => $node['nid']))) {
                    l(WARNING, 'Error updating vid');
                }
            }
        }
        else {
            //Get vid from node
            $sql = "SELECT vid FROM node
                WHERE nid = ?";
            $vid = 0;
            if ($query = db_executeQuery($sql, array($node['nid']))) {
                $aux=$query->fetch();
		$vid = $aux['vid'];
            }
            if (!$vid) {
                l(WARNING, 'Node has no vid defined');
            }
            $nodeRevision = array_intersect_key($node, array_flip($revisionFields));
            if (!db_update('node_revision', $nodeRevision, array('vid' => $vid))) {
                l(WARNING, 'Error updating node revision');
            }
        }

        return array(!empty($data['nid']) ? $data['nid'] : FALSE, $vid);
    }

    static public function createUrlFriendly($node) {
        //Set url
        $sql    = "SELECT COUNT(1) FROM url_friendly WHERE oid = ? AND module = ?";
        $isNew  = db_fetchColumn($sql, array($node['id'], $node['contentType'])) == 0;
        $target = empty($node['url']) ? self::_generateValidUrl($node) : self::_sanitizeUrl($node['url']);

        $urlRecord = array(
            'target' => $target,
            'oid'    => $node['id'],
            'module' => $node['contentType'],
        );

        if ($isNew) {
            if (!self::_existRedirectUrl($urlRecord)) {
                if (db_insert('url_friendly', $urlRecord) === NULL) {
                    l(ERROR, 'Error creating url friendly for this node');
                }
                else {
                    l(INFO, 'Url alias ' . $urlRecord['target'] . ' created successfully');
                }
            }
            else {
                $updateData = array('expirationDate' => null, 'oid' => $urlRecord['oid'], 'module' => $urlRecord['module']);
                $updateCriteria = array('target' => $urlRecord['target']);
                db_update('url_friendly', $updateData, $updateCriteria);
            }
        }
        else {

		self::_updateUrlTarget($urlRecord);
        }

    }

    static private function _updateUrlTarget($urlRecord) {
        $sql       = "SELECT target FROM url_friendly ";
        $sql      .= "WHERE oid = ? AND module = ? AND expirationDate is NULL";
        $oldTarget = db_fetchColumn($sql, array($urlRecord['oid'], $urlRecord['module']));

        //If its a new url
        if ($oldTarget && $urlRecord['target'] != $oldTarget) {

            //Checks if exists a redirection with same target for same content type
            if (!self::_existRedirectUrl($urlRecord)) {
                //Insert new one
                db_insert('url_friendly', $urlRecord);
            }
            else {
                //Update existing one as new target
                $updateData = array('expirationDate' => null, 'oid' => $urlRecord['oid'], 'module' => $urlRecord['module']);
                $updateCriteria = array('target' => $urlRecord['target']);
                db_update('url_friendly', $updateData, $updateCriteria);
            }

            //Update existing one with expirationDate
            $validityDate = date('Y-m-d H:i:s', strtotime("now +" . URL_REDIRECT_VALIDITY . " day"));
            $updateData = array('target' => $oldTarget, 'module' => $urlRecord['module']);
            db_update('url_friendly', array('expirationDate' => $validityDate), $updateData);
        }
    }

    static private function _existRedirectUrl($urlRecord) {
        $sql  = 'SELECT COUNT(1) FROM url_friendly ';
        $sql .= 'WHERE expirationDate is not NULL AND target = ?';

        $count = db_fetchColumn($sql, array($urlRecord['target']));

        return ($count == 1);
    }

    /**
     * Checks if an url exists for a different content
     *
     * @param string $module
     * @param string $target
     * @param int $oid
     *
     * @return boolean
     */
    static public function existUrl($module, $target, $oid) {
        $sql  = 'SELECT * FROM url_friendly ';
        $sql .= 'WHERE expirationDate is NULL AND target = ?';

        $url = db_fetchAssoc($sql, array($target));

        $exists = false;
        if($url) {
            if($url['module'] == $module) {
                $exists = !($oid == $url['oid']);
            }
            else if($url['module'] != $module) {
                $exists = true;
            }
        }

        return $exists;
    }

    /**
     * Function to save link fields
     */
    static private function linksFieldSave($data, $node) {
        $sql = 'DELETE l.* FROM links l
            INNER JOIN nodeLinks nl ON nl.lid = l.lid
            WHERE nl.id = ?';
        db_executeUpdate($sql, array($node['id']));
        db_delete('nodeLinks', array('id' => $node['id']));
        foreach($data as $weight => $link) {
            if (!empty($link['url'])) {
                $link['weight'] = $weight;
                $lid = db_insert('links', $link);
                $relation = array(
                    'lid' => $lid,
                    'id'  => $node['id'],
                    'nid' => $node['nid'],
                );
                if (!db_insert('nodeLinks', $relation)) {
                    l(ERROR, 'Error inserting link in database');
                }
                else {
                    l(INFO, 'Node links with lid ' . $lid . ' created successfully');
                }
            }
        }
    }

    /**
     * Function to save hierarchy fields
     */
    static private function parentsFieldSave($data, $node) {
        db_delete('drufonyHierarchy', array('nid' => $node['nid']));
        $isFirst = TRUE;
        foreach($data as $weight => $parent) {
            if (!empty($parent)) {
                $link['nid'] = $node['nid'];
                $record = array(
                    'parentNid' => $parent,
                    'nid' => $node['nid'],
                );
                if ($isFirst) {
                    $record['mainParent'] = 1;
                    $isFirst = FALSE;
                }
                if (!db_insert('drufonyHierarchy', $record)) {
                    l(ERROR, 'Error inserting parent in database');
                }
                else {
                    l(INFO, 'Relation with parent nid ' . $parent . ' created successfully');
                }
            }
        }
    }

    /**
     * Function to save image fields
     * FIXME declare as private when first content load is done
     */
    static public function imagesFieldSave($data, $node) {
        $imagesSaved = array();
        foreach($data as $weight => $image) {
            if (!empty($image)) {
                $isNew = empty($image['iid']);
                if (!empty($image['image'])) {
                    $imageFile = $image['image'];
                    list($image['fid'], $imageFile) = self::_saveNodeFile($imageFile, $node, SUBPATH_IMAGES);
                    ImageEffects::generateImageEffects($imageFile);
                }
                if (!empty($image['fid'])) {
                    //Insert information in custom table
                    $imageCustom = array(
                        'fid'         => $image['fid'],
                        'link'        => !empty($image['link'])  ? $image['link'] : '',
                        'title'       => !empty($image['title']) ? $image['title'] : '',
                        'alt'         => !empty($image['alt'])   ? $image['alt'] : '',
                        'weight'      => $weight,
                        'description' => !empty($image['description']) ? $image['description'] : '',
                    );
                    if ($isNew) {
                        if (($image['iid'] = db_insert('images', $imageCustom)) === FALSE) {
                            l(ERROR, 'Error inserting image information in database');
                        }
                        else {
                            l(INFO, 'Image information with iid ' . $image['iid'] . ' created successfully');
                        }
                    }
                    else {
                        if (!db_update('images', $imageCustom, array('iid' => $image['iid']))) {
                            l(ERROR, 'Error updating image information in database');
                        }
                        else {
                            l(INFO, 'Image information with iid ' . $image['iid'] . ' modified successfully');
                        }
                    }
                    $imagesSaved[] = array(
                        'iid' => $image['iid'],
                        'nid' => $node['nid'],
                        'id'  => $node['id'],
                    );

                }
            }
        }
        //Insert relatioship before we must to delete all previous relationships
        db_delete('nodeImages', array('id' => $node['id'], 'nid' => $node['nid']));
        foreach($imagesSaved as $relation) {
            if(!db_insert('nodeImages', $relation)) {
                l(ERROR, 'Error inserting image relation to node');
            }
            else {
                l(INFO, 'Image relation inserted successfully');
            }
        }
    }

    /**
     * Function to save attachment fields
     */
    static private function attachmentsFieldSave($data, $node) {
        $attachmentsSaved = array();
        foreach($data as $weight => $attachment) {
            if (!empty($attachment)) {
                $isNew = empty($attachment['aid']);
                if (!empty($attachment['file'])) {
                    $attachmentFile    = $attachment['file'];
                    list($attachment['fid'], $attachmentFile) = self::_saveNodeFile($attachmentFile, $node, SUBPATH_ATTACHMENTS);
                    $aid = 0;
                }
                if (!empty($attachment['fid'])) {
                    //Insert information in custom table
                    $attachmentCustom = array(
                        'fid'         => $attachment['fid'],
                        'weight'      => $weight,
                        'title'       => !empty($attachment['title']) ? $attachment['title'] : '',
                        'description' => !empty($attachment['description']) ? $attachment['description'] : '',
                    );
                    if ($isNew) {
                        if (($attachment['aid'] = db_insert('attachment', $attachmentCustom)) == FALSE) {
                            l(ERROR, 'Error inserting attachment information in database');
                        }
                        else {
                            l(INFO, 'Attachment information with aid ' . $attachment['aid'] . ' created successfully');
                        }
                    }
                    else {
                        if (!db_update('attachment', $attachmentCustom, array('aid' => $attachment['aid']))) {
                            l(ERROR, 'Error updating attachment information in database');
                        }
                        else {
                            l(INFO, 'Attachment information with aid ' . $attachment['aid'] . ' modified successfully');
                        }
                    }
                    $attachmentsSaved[] = array(
                        'aid' => $attachment['aid'],
                        'nid' => $node['nid'],
                        'id'  => $node['id'],
                    );
                }
            }
        }
        //Insert relatioship before we must to delete all previous relationships
        db_delete('nodeAttachments', array('id' => $node['id'], 'nid' => $node['nid']));
        foreach ($attachmentsSaved as $relation) {
            if (!db_insert('nodeAttachments', $relation)) {
                l(ERROR, 'Error inserting attachment relation to node');
            }
            else {
                l(INFO, 'Attachment relation inserted successfully');
            }
        }
    }

    /**
     * Function to save video fields
     */
    static private function videosFieldSave($data, $node) {
        $videoSaved = array();
        foreach($data as $weight => $video) {
            if (!empty($video)) {
                $isNew = empty($video['vid']);
                if (!empty($video['video'])) {
                    $videoFile = $video['video'];
                    $pathUrl = FILES_BASE . SUBPATH_TEMPORARY_VIDEOS . self::getNodeFilesPath($node['nid']);
                    if (!file_exists($pathUrl . "/")) {
                        mkdir($pathUrl, 0755, TRUE);
                    }
                    $fileName = Drupal::file_munge_filename(uniqid() . "." . $videoFile->guessExtension(), DEFAULT_ALLOWED_EXTENSIONS);
                    $videoFile = $videoFile->move($pathUrl, $fileName);

                    $videoProvider = Video::getInstance();
                    $video['token'] = $videoProvider->upload($videoFile, $video['title'], $video['description']);
                    //Erase uploaded video file
                    unlink($videoFile->getPathname());
                    $vid = 0;
                }
                if (!empty($video['token'])) {
                    //Save video info into database
                    $record = array(
                        'token' => $video['token'],
                        'description' => $video['description'],
                        'title'       => $video['title'],
                        'weight'      => $weight,
                        'provider'    => VIDEO_PROVIDER,
                    );
                    if ($isNew) {
                        if (($video['vid'] = db_insert('videos', $record)) == FALSE) {
                            l(ERROR, 'Error inserting video information into database');
                        }
                        else {
                            l(INFO, 'Video information with vid ' . $video['vid'] . ' created successfully');
                        }
                    }
                    else {
                        if (!db_update('videos', $record, array('vid' => $video['vid']))) {
                            l(ERROR, 'Error updating video information into database');
                        }
                        else {
                            l(INFO, 'Video information with vid ' . $video['vid'] . ' modified successfully');
                        }
                    }
                    $videoSaved[] = array(
                        'vid' => $video['vid'],
                        'nid' => $node['nid'],
                        'id'  => $node['id'],
                    );
                }
            }
        }

        //Insert relationship before we must to delete all previous relationships
        db_delete('nodeVideos', array('id' => $node['id'], 'nid' => $node['nid']));
        foreach ($videoSaved as $relation) {
            if (!db_insert('nodeVideos', $relation)) {
                l(ERROR, 'Error inserting video relation to node');
            }
            else {
                l(INFO, 'Video relation inserted successfully');
            }
        }
    }

    static private function varietiesFieldSave($data, $node) {
        //db_delete('varietiesByProduct', array('productId' => $node['id']));
	//var_dump($data);
	foreach($data as $type => $variety) {
	    $sql="SELECT id FROM variety WHERE value=? and type=?";
	    $result=db_fetchAssoc($sql,array($variety,$type));
	    if(!empty($result)){
		$varietyId=$result['id'];		
	    }
	    else{
		$varietyId=db_insert('variety',array('type'=>$type,'value'=>$variety));
	    }
            $record = array(
                'productId' => $node['id'],
                'varietyId' => $varietyId,
            );
            if (!db_insert('varietiesByProduct', $record)) {
                l(ERROR, 'Error inserting variety for this node');
            }
            else {
                l(INFO, 'Variety with id ' . $varietyId . ' has been created successfully');
            }
        }
    }

    static private function freeTagsFieldSave($data, $node) {

        if(!is_array($data)) {

            $freeTags = explode(',', $data);

            //Removes old tags
            $sql  = "DELETE np.* FROM node_pools np ";
            $sql .= "INNER JOIN category c on c.tid = np.objectId ";
            $sql .= "INNER JOIN vocabulary v ON v.vid = c.vid ";
            $sql .= "WHERE v.name = ? AND np.nid = ? AND np.type = ? ";
            $sql .= "AND c.name not in ('" . implode("','", $freeTags) . "')";

            db_executeUpdate($sql, array(FREE_TAGS, $node['nid'], 'category'));

            foreach ($freeTags as $tag) {
                if(strlen($tag) > 0){
                    $tid = Category::save($tag, FREE_TAGS);
                    Category::setNodeCategory($node['nid'], $tid);
                }
            }
        }
    }

    static private function tagsFieldSave($data, $node) {

        //Removes old tags
        $sql  = 'DELETE np.* FROM node_pools np ';
        $sql .= 'INNER JOIN category c ON c.tid = np.objectId ';
        $sql .= "INNER JOIN vocabulary v ON v.vid = c.vid ";
        $sql .= 'WHERE np.type = ? AND v.name != ? AND np.nid = ? ';

        //If any tag is selected, skip those tags
        if (!empty($data)) {
            $sql .= 'AND np.objectId not in (' . implode(",", $data) .')';
        }

        db_executeUpdate($sql, array('category', FREE_TAGS, $node['nid']));

        foreach ($data as $tid) {
            Category::setNodeCategory($node['nid'], $tid);
        }
    }

    /**
     * Generic function to save files into Drupal database
     */
    static private function _saveNodeFile($file, $node, $subPath = '') {
        $isNew    = FALSE;
        //Ensure change fileName
        $pathUrl = FILES_BASE . $subPath . self::getNodeFilesPath($node['nid']);
        list($fid, $file) = self::saveFile($file, $pathUrl, $node['uid']);

        return array($fid, $file);
    }

    /**
     * Save a file into database and save a permanent file in a folder
     *
     * @param file, UploadedFile type from upload widget
     * @param path, Folder path to store this file
     * @param uid, owner of file
     * @return array with fid and file object with updated data
     */
    static public function saveFile($file, $path, $uid) {
        if (!file_exists($path . "/")) {
            mkdir($path, 0755, TRUE);
        }
        $fileName = Drupal::file_munge_filename(uniqid() . "." . $file->guessExtension(), DEFAULT_ALLOWED_EXTENSIONS);
        $file = $file->move($path, $fileName);
        $path = $path . $fileName;
        $fileManaged = array(
            'uid'       => $uid,
            'filename'  => $fileName,
            'status'    => 1,
            'uri'       => $path,
            'filemime'  => $file->getMimeType(),
            'filesize'  => $file->getSize(),
            'timestamp' => time(),
        );
        if (($fid = db_insert('file_managed', $fileManaged)) === FALSE) {
            l(ERROR, 'Error inserting file information in table file_managed');
        }
        else {
            l(INFO, 'File information for fid ' . $fid . ' inserted successfully');
        }

        return array($fid, $file);
    }

    /**
     * Function to check if a node can be saved
     */
    static private function _canSaveNode($data) {
        $mandatoryFields = array('title', 'uid', 'type', 'language');
        $commonFields    = array_intersect(array_keys($data), $mandatoryFields);
        $canSave         = FALSE;
        if (!empty($commonFields)) {
            $canSave = TRUE;
        }

        return $canSave;
    }

    /**
     * Calculates the path where the files of this node are stored
     * ($id = 1234 -> $path = $base/1/2/1234-$filename)
     * @return the calculated path
     */
    public static function getNodeFilesPath($id, $prefix = '', $depth = FILE_PREPATH_DEPTH) {
        //Convert the id to string
        $stringId = strval($id);
        //Depth of the nested folders?
        $path = array();
        if($depth > strlen($stringId)) {
            $depth = strlen($stringId);
        }
        for ($i=0; $i < $depth; $i++) {
            $path[] = $stringId[$i];
        }
        return $prefix . implode ("/$prefix", $path) . '/';
    }

    private static function _generateValidUrl($data) {
        $url = 'node/' .  $data['nid'];
        switch($data['contentType']) {
            case 'item':
                $url = self::_checkUniqueUrl(self::_sanitizeUrl($data['title']), $data);
                break;
            case 'section':
                $url = self::_checkUniqueUrl(self::_sanitizeUrl($data['title']), $data);
                break;
            case 'page':
                $url = self::_checkUniqueUrl(self::_sanitizeUrl($data['title']), $data);
                break;
            case 'product':
                $url = self::_checkUniqueUrl(self::_sanitizeUrl('product/' . $data['title']), $data);
                break;
            case 'default':
                l(WARNING, 'Any url_friendly pattern has been created for type ' . $data['type']);
                break;
        }

        return $url;
    }

    private static function _sanitizeUrl($url) {
        $url = strtolower($url);
        foreach(self::_getConflictingChars() as $char => $solve) {
            $url = str_replace($char, $solve, $url);
        }

        return Drupal::check_plain($url);
    }

    private static function _checkUniqueUrl($url, $data) {
        $sql = 'SELECT COUNT(1) as count FROM url_friendly WHERE target = ? AND oid != ? AND expirationDate is NULL';
        $result = db_executeQuery($sql, array($url, $data['id']));
        $originalUrl = $url;
        $idUnique = 1;
	$aux=$result->fetch();
        while(($row = $aux['count']) != 0) {
            $url = $originalUrl . '-' . $idUnique;
            $result = db_executeQuery($sql, array($url, $data['id']));
            l(WARNING, 'Trying to create a node with the same url of another, trying to add -' .$idUnique);
            $idUnique++;
        }

        return $url;
    }

    private static function _getConflictingChars() {
        return array(
            'á'  => 'a',
            'é'  => 'e',
            'í'  => 'i',
            'ú'  => 'u',
            'à'  => 'a',
            'è'  => 'e',
            'ì'  => 'i',
            'ù'  => 'u',
            'ñ'  => 'n',
            'ç'  => 'c',
            '\'' => '',
            '"'  => '',
            ' '  => '-',
        );
    }

    /**
     * Get all objects (nodes or users) related to a tid
     *
     * @param int $tid; category id
     * @param string $poolType; type of pool in node_pool
     * @param string $tableName; indicate if is node users
     * @return array; with found objects
     */
    private static function _getObjectsByTid($tid, $poolType, $tableName) {
        $tableInfo = array('node' => array('id' => 'nid', 'poolsTable' => 'node_pools'),
                        'users' => array('id' => 'uid', 'poolsTable' => 'user_pools'));

        //$sql  = "SELECT ${tableName}.* ";
        $id = $tableInfo[$tableName]['id'];
        $poolsTable = $tableInfo[$tableName]['poolsTable'];

        $sql = "SELECT ${tableName}.* ";
        if ($tableName == 'node') {
            $sql  = "SELECT ${tableName}.nid as nid, ${tableName}.type as type ";
        }

        $sql .= "FROM ${tableName} ";
        $sql .= "INNER JOIN ${poolsTable} ";
        $sql .= "ON ${poolsTable}.${id} = ${tableName}.${id} ";
        $sql .= "WHERE ${poolsTable}.objectId = ? AND ${poolsTable}.type = ?";

        $objects = array();
        $resultQuery = db_executeQuery($sql, array($tid, $poolType));
        $result = $resultQuery->fetchAll();

        if ($tableName == 'users') {
            $objects = $result;
        }
        else {
            $objects= ContentUtils::getContentObjectsByMixedArray($result);
        }

        return $objects;
    }

    /**
     * Get all nodes related to a tid
     *
     * @param int $tid; category id
     * @param string $poolType; type of pool in node_pool
     * @return array; with found nodes
     */
    public static function getNodesByTid($tid, $poolType) {
        return self::_getObjectsByTid($tid, $poolType, 'node');
    }

    /**
     * Get all users related to a tid
     *
     * @param int $tid; category id
     * @param string $poolType; type of pool in node_pool
     * @return array; with found users
     */
    public static function getUsersByTid($tid, $poolType) {
        return self::_getObjectsByTid($tid, $poolType, 'users');
    }

    /**
     * Delete a content and all its fields
     * @param nid; nid for this content
     *
     * FIXME Dividir código deleteContent en funciones privadas
     */
    public static function deleteContent($nid) {
        $sql     = "SELECT type FROM node WHERE nid = ?";
        $results = db_executeQuery($sql, array($nid));
        $type    = $results->fetchColumn();

        // Complex or logical required removes
        if (!$type) {
            throw new ContentTypeNotFound();
        }
        self::_deleteUrlFriendly($type, $nid);
        self::_deleteAttachments($nid);
        self::_deleteLinks($nid);
        self::_deleteImages($type, $nid);
        self::_deleteVideos($nid);
        self::_deleteLocation($nid);
        self::_deleteNodePools($nid);

        // Atomical removes
        db_delete('drufonyHierarchy', array('parentNid' => $nid));
        db_delete('drufonyHierarchy', array('nid' => $nid));
        db_delete($type, array('nid' => $nid));
        db_delete('node_revision', array('nid' => $nid));
        db_delete('node', array('nid' => $nid));
    }

    static private function _deleteUrlFriendly($type, $nid) {
        $sql     = "SELECT id FROM ${type} WHERE nid = ?";
        $results = db_executeQuery($sql, array($nid));

        while ($row = $results->fetch()) {
            db_delete('url_friendly', array('oid' => $row['id'], 'module' => $type));
        }
    }

    static private function _deleteImages($type, $nid) {
        $sql    = "SELECT i.iid, i.fid, f.uri FROM nodeImages ni
            INNER JOIN images i ON i.iid = ni.iid
            INNER JOIN file_managed f ON f.fid = i.fid WHERE ni.nid = ?";
        $result = db_executeQuery($sql, array($nid));

        //Delete in table type
        db_delete($type, array('nid' => $nid));

        while ($row = $result->fetch()) {
            unlink($row['uri']);
            db_delete('file_managed', array('fid' => $row['uri']));
            db_delete('images', array('iid' => $row['iid']));
        }

        // Removes node relations
        db_delete('nodeImages', array('nid' => $nid));
    }

    static private function _deleteAttachments($nid) {
        $sql = "SELECT a.aid, a.fid, f.uri FROM nodeAttachments na
            INNER JOIN attachment a ON a.aid = na.aid
            INNER JOIN file_managed f ON f.fid = a.fid WHERE na.nid = ?";
        $result = db_executeQuery($sql, array($nid));

        while ($row = $result->fetch()) {
            unlink($row['uri']);
            db_delete('file_managed', array('fid' => $row['uri']));
            db_delete('attachment', array('aid' => $row['aid']));
        }

        // Removes node relations
        db_delete('nodeAttachments', array('nid' => $nid));

    }

    static private function _deleteLinks($nid) {
        $sql    = "SELECT lid FROM nodeLinks WHERE nid = ?";
        $result = db_executeQuery($sql, array($nid));

        while ($row = $result->fetch()) {
            db_delete('links', array('lid' => $row['lid']));
        }

        // Removes node relations
        db_delete('nodeLinks', array('nid' => $nid));
    }

    static private function _deleteVideos($nid) {
        $sql    = "SELECT vid FROM nodeVideos WHERE nid = ?";
        $result = db_executeQuery($sql, array($nid));

        while ($row = $result->fetch()) {
            db_delete('videos', array('vid' => $row['vid']));
        }

        // Removes node relations
        db_delete('nodeVideos', array('nid' => $nid));
    }

    static private function _deleteLocation($nid) {
        $deleteCriteria = array('nid' => $nid);

        db_delete('locations', $deleteCriteria);
    }

    static private function _deleteNodePools($nid) {
        $deleteCriteria = array('nid' => $nid);

        db_delete('node_pools', $deleteCriteria);
    }

    /**
     * Gives the redirect of an url
     *
     * @param array $urlData
     * @return string
     */
    static public function getCorrectUrl($urlData) {
        $target = null;
        $sql = 'SELECT target, expirationDate FROM url_friendly WHERE oid = ? AND module = ? and expirationDate is NULL';
        $realUrlData = db_fetchAssoc($sql, array($urlData['oid'], $urlData['module']));

        if($realUrlData) {
            $currentDate = date('Y-m-d', strtotime("now"));
            $expirationDate = date('Y-m-d', strtotime($urlData['expirationDate']));

            if($currentDate > $expirationDate) {
                l(DEBUG, "URL ${urlData['target']} has expired, deleting...");
                db_delete('url_friendly', array('target' => $urlData['target'],
                                                'oid'    => $urlData['oid'],
                                                'module' => $urlData['module']));
            }

            $target = $realUrlData['target'];
        }

        return $target;
    }

    /**
     * createTask
     *
     * @param string $title
     * @param string $description
     * @param string $assigned uid for assigned user
     * @param int $status
     * @param int $level
     * @return int id of created Task
     */
    static public function createTask($title, $description, $assigned = NULL, $status = Task::STATUS_NEW, $level = Task::LEVEL_TASK) {
        return Task::save(compact('title', 'description', 'assigned', 'status', 'level'));
    }
}
