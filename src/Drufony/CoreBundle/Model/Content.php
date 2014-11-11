<?php
/**
 * Implementation of Content abstract Entity. It provides the abstract
 * class which will be inherited by all the Content type classes.
 * It defines all the common methods and properties which must be
 * used to generate a content type.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Symfony\Component\HttpFoundation\File\File;
use Drufony\CoreBundle\Entity\Comment;
use Drufony\CoreBundle\Model\DrufonyImage;
use Drufony\CoreBundle\Model\DrufonyAttachment;
use Drufony\CoreBundle\Model\ContentUtils;
use Drufony\CoreBundle\Entity\User;
use Drufony\CoreBundle\Exception\TranslateNotFound;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Class constants
// Defines constants if undefined. Those constants could be defined in
// a setting file.
// FIXME: 5 constants defined from another constant value
defined('ITEMS_PER_PAGE')           or define('ITEMS_PER_PAGE',       20);
defined('IMAGES_PER_PAGE')          or define('IMAGES_PER_PAGE',      ITEMS_PER_PAGE);
defined('ATTACHMENTS_PER_PAGE')     or define('ATTACHMENTS_PER_PAGE', ITEMS_PER_PAGE);
defined('VIDEOS_PER_PAGE')          or define('VIDEOS_PER_PAGE',      ITEMS_PER_PAGE);
defined('LINKS_PER_PAGE')           or define('LINKS_PER_PAGE',       ITEMS_PER_PAGE);
defined('COMMENTS_PER_PAGE')        or define('COMMENTS_PER_PAGE',    ITEMS_PER_PAGE);
defined('DEFAULT_LANG')             or define('DEFAULT_LANG',         'en');
defined('CONTENT_HOME_DEFAULT_URL') or define('CONTENT_HOME_DEFAULT_URL', 'index');

/**
 * Abstract Content class. It's a base for all Content Type classes.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
abstract Class Content
{
    const TYPE_PRODUCT          = 'product';
    const TYPE_ITEM             = 'item';
    const TYPE_SECTION          = 'section';
    const TYPE_PAGE             = 'page';
    const TYPE_COMMENT          = 'comment';

    const ORDER_CRITERIA_BLOG   = 'BLOG';
    const ORDER_CRITERIA_DATE   = 'DATE';
    const ORDER_CRITERIA_WEIGHT = 'WEIGHT';
    const ORDER_MODE_ASC        = 'ASC';
    const ORDER_MODE_DESC       = 'DESC';

    // FIXME Eliminar textos sin traducir y en constantes para generación de tasks automáticas.
    const TASK_SUBJECT_COMMENTS_PENDING = 'Comments pending to moderate';

    /**
     * Identifies the content with an unique id.
     *
     * @var int
     */
    protected $nid;

    /**
     * Identifies the translated content with an unique id.
     *
     * @var int
     */
    protected $id = 0;

    /**
     * Identifies the author of the content.
     *
     * @var int
     */
    protected $uid;

    /**
     * Identifies the friendly url which will be used for routing.
     *
     * @var string
     */
    protected $url;

    /**
     * Identifies the title of the content.
     *
     * @var string
     */
    protected $title;

    /**
     * Identifies the subtype of the content.
     *
     * @var string
     */
    protected $type;

    /**
     * Identifies the content type of the content.
     *
     * @var string
     */
    protected $contentType;

    /**
     * Identifies the description of the content.
     *
     * @var string
     */
    protected $description;

    /**
     * Identifies the teaser (a short summary useful for showing contents in lists).
     * of the content.
     *
     * @var string
     */
    protected $teaser;

    /**
     * Identifies the summary of the content.
     *
     * @var string
     */
    protected $summary;

    /**
     * Identifies the body of the content.
     *
     * @var string
     */
    protected $body;

    /**
     * Identifies whether the content is published or not.
     *
     * @var bool
     */
    protected $published;

    /**
     * Identifies the language of the content.
     *
     * @var string
     */
    protected $lang;

    /**
     * Identifies the weight of the content. It could be used for
     * sorting contents in lists.
     *
     * @var int
     */
    protected $weight = 0;

    /**
     * Identifies whether the content is sticky or not. Useful for lists.
     *
     * @var bool
     */
    protected $sticky;

    /**
     * Identifies whether the content is promoted in homepage or not.
     *
     * @var bool
     */
    protected $promoted;

    /**
     * Logs the publication datetime for the content.
     *
     * @var datetime
     */
    protected $publicationDate;

    /**
     * Logs the modification datetime for the content.
     *
     * @var datetime
     */
    protected $modificationDate;

    /**
     * Identifies the publication datetime which will be used for
     * scheduling publication in the future.
     *
     * @var datetime
     */
    protected $futurePublicationDate;

    /**
     * Identifies whether the content is shown in usermap or not.
     *
     * @var bool
     */
    protected $userMap;

    /**
     * Identifies whether the content is shown in xmlmap or not.
     *
     * @var bool
     */
    protected $xmlMap;

    /**
     * Identifies whether the content is opened to user comments or not.
     *
     * @var bool
     */
    protected $commentStatus = COMMENT_DEFAULT_STATUS;

    /**
     * Logs all the comments attached to this content.
     *
     * @var array
     */
    protected $comments;

    /**
     * Logs all the new comments attached to this content.
     *
     * @var array
     */
    protected $newComments = array();

    /**
     * Logs the unsaved comments attached to this content.
     *
     * @var array
     */
    protected $unsavedComments = array();

    /**
     * Identifies the amount of comments attached to this content.
     *
     * @var int
     */
    protected $commentsCount;

    /**
     * Identifies the amount of new comments attached to this content.
     *
     * @var int
     */
    protected $newCommentCount = 0;

    /**
     * Identifies the images attached to this content.
     *
     * @var array
     */
    protected $images = array();

    /**
     * Identifies the videos attached to this content.
     *
     * @var array
     */
    protected $videos = array();

    /**
     * Identifies the attachments linked to this content.
     *
     * @var array
     */
    protected $attachments = array();

    /**
     * Identifies the tags related to this content.
     *
     * @var array
     */
    protected $tags = array();

    /**
     * Identifies the links attached to this content.
     *
     * @var array
     */
    protected $links = array();

    /**
     * Identifies the content name for this content.
     * FIXME: Really used ??
     *
     * @var string
     */
    protected $contentName;

    /**
     * Identifies the main parent for this content.
     *
     * @var int
     */
    protected $mainParent;

    /**
     * Identifies the parents related to this content.
     *
     * @var array
     */
    protected $parents;

    /**
     * Identifies the children related to this content.
     *
     * @var array
     */
    protected $children;

    /**
     * Identifies the author name for this content.
     *
     * @var string
     */
    protected $authorName;

    /**
     * Identifies whether the content is a major change from a previous version or not.
     *
     * @var mixed
     */
    protected $majorChange;

    /**
     * Identifies the geoposition for this content. It includes longitude and latitude.
     *
     * @var mixed
     */
    protected $location;

    /**
     * Identifies the latitude geodata for this content. It's needed to submit the forms.
     *
     * @var float
     */
    protected $latitude;

    /**
     * Identifies the longitude geodata for this content. It's needed to submit the forms.
     *
     * @var float
     */
    protected $longitude;

    /**
     * Identifies the free tags related to this content.
     *
     * @var array
     */
    protected $freeTags = array();

    /**
     * Retrieves the nid of this content.
     *
     * @return int
     */
    public function getNid() {
        return $this->nid;
    }

    /**
     * Retrieves the id of this content.
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Retrieves the uid of this content.
     *
     * @return int
     */
    public function getUid() {
        return $this->uid;
    }

    /**
     * Retrieves the title of this content.
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Retrieves the subtype of this content.
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Retrieves the description of this content.
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Retrieves the teaser of this content.
     *
     * @return string
     */
    public function getTeaser() {
        return $this->teaser;
    }

    /**
     * Retrieves the body of this content.
     *
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Retrieves the summary of this content.
     *
     * @return string
     */
    public function getSummary() {
        return $this->summary;
    }

    /**
     * Retrieves the language of this content.
     *
     * @return string
     */
    public function getLang() {
        return $this->lang;
    }

    /**
     * Retrieves the weight of this content.
     *
     * @return int
     */
    public function getWeight() {
        return $this->weight;
    }

    /**
     * Retrieves the publication datetime for this content.
     *
     * @return datetime
     */
    public function getPublicationDate() {
        return $this->publicationDate;
    }

    /**
     * Retrieves the modification datetime for this content.
     *
     * @return datetime
     */
    public function getModificationDate() {
        return $this->modificationDate;
    }

    /**
     * Retrieves whether the content is published or not.
     *
     * @return bool
     */
    public function isPublished() {
        return $this->published;
    }

    /**
     * Retrieves the content typ of this content.
     *
     * @return string
     */
    public function getContentType() {
        return $this->contentType;
    }

    /**
     * Retrieves whether the content is sticky for lists or not.
     *
     * @return bool
     */
    public function isSticky() {
        return $this->sticky;
    }

    /**
     * Retrieves whether the content is promoted for homepage or not.
     *
     * @return bool
     */
    public function isPromoted() {
        return $this->promoted;
    }

    /**
     * Retrieves whether the content must be shown in usermap or not.
     *
     * @return bool
     */
    public function hasXmlMap() {
        return $this->xmlMap;
    }

    /**
     * Retrieves whether the content must be shown in xmlmap or not.
     *
     * @return bool
     */
    public function hasUserMap() {
        return $this->userMap;
    }

    /**
     * Retrieves the comment status related to this content.
     *
     * @return bool
     */
    public function getCommentStatus() {
        return $this->commentStatus;
    }

    /**
     * Retrieves if this an empty Content object or not.
     *
     * @return bool
     */
    public function isNull() {
        return is_null($this->nid);
    }

    /**
     * Retrieves if this is a major change or not.
     *
     * @return bool
     */
    public function isMajorChange() {
        return is_null(!$this->majorChange) ? $this->majorChange == 1 : FALSE;
    }

    /**
     * Retrieves the scheduled publication datetime
     *
     * @return datetime
     */
    public function getFuturePublicationDate() {
        return $this->futurePublicationDate;
    }

    /**
     * Retrieves all the parents related to this content.
     *
     * @return array
     */
    public function getParents() {
        if (empty($this->parents)) {
            $this->parents = $this->_getParents();
        }

        return $this->parents;
    }

    /**
     * Retrieves the main parent related to this content.
     *
     * @return Content
     */
    public function getMainParent() {
        if (empty($this->mainParent)) {
            $sql                = 'SELECT h.parentNid as nid, n.type
                                   FROM drufonyHierarchy h
                                   INNER JOIN node n ON n.nid = h.parentNid
                                   WHERE h.mainParent = 1 AND h.nid = ?';
            $query              = db_executeQuery($sql, array($this->nid));
            $parent             = ContentUtils::getContentObjectsByMixedArray($query->fetchAll());
            $this->mainParent   = count($parent) > 0 ? $parent[0] : new Section(0, $this->lang);
        }

        return $this->mainParent;
    }

    /**
     * Retrieves the children associated to this content.
     *
     * @return array
     */
    public function getChildren() {
        if (empty($this->children)) {
            $sql            = 'SELECT h.nid, n.type FROM drufonyHierarchy h INNER JOIN node n ON n.nid = h.nid WHERE h.parentNid = ?';
            $result         = db_executeQuery($sql, array($this->nid));

            $children       = $result->fetchAll();
            $this->children = ContentUtils::getContentObjectsByMixedArray($children);
        }

        return $this->children;
    }

    /**
     * Retrieves the items associated to this content.
     *
     * @return array
     */
    public function getItems($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        if (empty($this->children)) {
            $sql            = 'SELECT h.nid, n.type FROM drufonyHierarchy h ';
            $sql           .= 'INNER JOIN node n ON n.nid = h.nid WHERE h.parentNid = ? AND n.type = \'item\'';
            $result         = db_executeQuery($sql, array($this->nid));

            $children       = $result->fetchAll();
            $this->children = ContentUtils::getContentObjectsByMixedArray($children, $page, $itemsPerPage);
        }

        return $this->children;
    }

    /**
     * Stores the unsaved comments in database.
     *
     * @param array
     *
     * @return int
     */
    public function saveComment($commentData) {
        if (!empty($commentData['cid'])) {
            $cid = $commentData['cid'];

            db_update('comment', $commentData, array('cid' => $commentData['cid']));
        }
        else {
            $cid = db_insert('comment', $commentData);
        }

        if ($cid) {
            l(INFO, 'Comment with cid: ' . $cid . ' registered');
        }

        if ($this->getCommentStatus() == Comment::COMMENT_STATUS_PREMODERATED || $this->getCommentStatus() == Comment::COMMENT_STATUS_POSTMODERATED) {
            $sql           = "SELECT COUNT(1) FROM tasks WHERE title = ? AND status <> ?";
            $isTaskPending = db_fetchColumn($sql, array(self::TASK_SUBJECT_COMMENTS_PENDING, Task::STATUS_DONE));

            if (!$isTaskPending) {
                $title       = self::TASK_SUBJECT_COMMENTS_PENDING;
                $description = 'New comments pending to moderate';

                if (Utils::createTask($title, $description)) {
                    l('INFO', 'New task created to moderate comments');
                }
            }
        }

        return $cid;
    }

    /**
     * Retrieves an array of comments for this node.
     *
     * @param int * If you want to retrieves approved or moderated comments
     * @param int
     * @param int
     *
     * @return array * Assoc array with keys ('cid', 'nid', 'uid', 'subject', 'body', 'created',  'status', 'thread', 'name');
     */
    public function getComments($approved = COMMENT_SHOW_DEFAULT_STATE, $page = 0, $itemsPerPage = COMMENTS_PER_PAGE) {
        if (is_null($this->comments) && $this->getCommentStatus() != Comment::COMMENT_STATUS_CLOSED) {
            $sql      = "SELECT * FROM comment WHERE nid = ? AND id = ? AND status = ? ORDER BY pid, cid";
            $results  = db_executeQuery($sql, array($this->getNid(), $this->getId(), $approved), $page, $itemsPerPage);
            $comments = array();

            while ($row = $results->fetch()) {
                $cids[$row['cid']]       = $row;
                $children[$row['pid']][] = $row['cid'];
            }

            if (isset($cids)) {
                $depth = 0;

                foreach ($cids as $commentItem) {
                    $commentItem['depth']          = $depth;
                    $comments[$commentItem['cid']] = $commentItem;

                    $this->getCommentChildren($cids, $children, $commentItem['cid'], $comments, $depth);
                    unset($cids[$commentItem['cid']]);

                    if (empty($cids)) {
                        break;
                    }
                }
            }

            $this->comments = ContentUtils::getContentObjects($comments, Content::TYPE_COMMENT);
        }

        return $this->comments;
    }

    /**
     * Retrieves all the children for a comment cid
     *
     * @param array * All the unprocessed comments
     * @param array * Array with key pid (parent id) and value cid of child
     * @param int   * Current cid
     * @param array * Array with all processed comments
     * @param int   * Depth for the current level
     *
     * @return void
     */
    private function getCommentChildren(&$comments, $hierarchy, $element, &$commentsProcessed, $depth) {
        $children = isset($hierarchy[$element]) ? $hierarchy[$element] : array();
        $depth++;

        foreach ($children as $child) {
            $comments[$child]['depth'] = $depth;
            $commentsProcessed[$child] = $comments[$child];

            $this->getCommentChildren($comments, $hierarchy, $child, $commentsProcessed, $depth);
            unset($comments[$child]);
        }
    }

    /**
     * Retrieves the amount of comments for the node.
     *
     * @return int
     */
    public function getCommentsCount () {
        if (is_null($this->commentsCount)) {
            $sql = "SELECT COUNT(1) FROM comment WHERE nid = ? AND id = ?";
            $this->commentsCount = db_fetchColumn($sql, array($this->getNid(), $this->getId()));
        }

        return $this->commentsCount;
    }

    /**
     * Retrieves an array with the ids of all images for a content.
     *
     * @param int * Num of item to get per page. (It is used only if $page > 0)
     * @param int * 0 if we want to get all items (No pager defined), >0 to get a particular page.
     *
     * @return int array; with images ids for the current content.
     */
    public function getImages($page = 0, $itemsPerPage = IMAGES_PER_PAGE)
    {
        if (empty($this->images) && $this->nid) {
            $this->_loadImages($page, $itemsPerPage);
        }

        return $this->images;
    }

    /**
     * Retrieves the main image of a content
     *
     * @return array; with image main fields
     */
    public function getMainImage() {

        $images = $this->getImages();

        //Get last image since getImages order by weight ASC
        $mainImage = count($images) > 0 ? $images[count($images) - 1] : null;

        return $mainImage;
    }

    /**
     * Retrieves an array with the ids of all files (no images) for this content.
     *
     * @param int * Num of item to get per page. (It is used only if $page > 0)
     * @param int * 0 if we want to get all items (No pager defined), >0 to get a particular page.
     *
     * @return array * File ids for the current content.
     */
    public function getAttachments($page = 0, $itemsPerPage = ATTACHMENTS_PER_PAGE)
    {
        if (empty($this->attachments) && $this->nid) {
            $this->_loadAttachments($page, $itemsPerPage);
        }

        return $this->attachments;
    }

    /**
     * Retrieves an array with the ids of all videos for this content.
     *
     * @param int
     * @param int
     *
     * @return array
     */
    public function getVideos($page = 0, $itemsPerPage = VIDEOS_PER_PAGE)
    {
        if (empty($this->videos) && $this->nid) {
            $this->_loadVideos($page, $itemsPerPage);
        }

        return $this->videos;
    }


    /**
     * Retrieves an assoc array with the links for current content.
     *
     * @param int * Num of item to get per page. (It is used only if $page > 0)
     * @param int * 0 if we want to get all items (No pager defined), >0 to get a particular page.
     *
     * @return array * Array with keys(id, nid, url, linkText, weight, target);
     */
    public function getLinks($page = 0, $itemsPerPage = LINKS_PER_PAGE)
    {
        if (empty($this->links)) {
            $this->_loadLinks($page, $itemsPerPage);
        }

        return $this->links;
    }

    /**
    * Retrieves the breadcrumb related to this content.
    *
    * @return array * Associative with the schema
    * ([linkText 1] => '[path 1'], [linkText current content] => 'path cur content')
    */
    public function getBreadCrumb() {
        $breadCrumbs = array();

        if (!$this->isNull()) {
            $breadCrumbs[$this->getTitle()] = $this->getUrl(); // First item is the instanced item
            $parentContent = $this->getMainParent();

            if (!$parentContent->isNull()) {
                do {
                    $breadCrumbs[$parentContent->getTitle()] = $parentContent->getUrl();
                    $parentContent = $parentContent->getMainParent();
                } while (!$parentContent->isNull() && !$parentContent->getMainParent()->isNull()); // Checks parents
            }
        }

        $breadCrumbs[t("Home")] = CONTENT_HOME_DEFAULT_URL;

        return array_reverse($breadCrumbs);
    }

    /**
     * Retrieves content URL
     *
     * @return string
     */
    public function getUrl() {
        if (empty($this->url)) {
            $sql       = 'SELECT target FROM url_friendly WHERE oid = ? AND module = ? AND expirationDate is NULL';
            $this->url = db_fetchColumn($sql, array($this->getId(), $this->getContentType()));
        }

        return $this->url;
    }

    /**
     * Retreives content author name
     *
     * @return string
     */
    public function getAuthorName() {
        if (empty($this->authorName)) {
            $sql = 'SELECT users.username as username, profile.name as name
                    FROM users LEFT JOIN profile ON (users.uid = profile.uid)
                    WHERE users.uid = ?';
            $authorData = db_fetchAssoc($sql, array($this->uid));

            $this->authorName = isset($authorData['name']) ? $authorData['name'] : $authorData['username'];
        }

        return $this->authorName;
    }

    /**
     * Loads all the images attached to this content.
     *
     * @param int
     * @param int
     *
     * @return int
     */
    protected function _loadImages($page = 0, $itemsPerPage)
    {
        $sql = "SELECT i.iid,i.weight, i.fid, uri, link, i.title,i.description,alt
            FROM images i
            INNER JOIN file_managed fm ON fm.fid = i.fid
            INNER JOIN nodeImages ni ON ni.iid = i.iid
            WHERE ni.id = ?
            AND ni.nid = ?
            ORDER BY i.weight ASC";

        $query = db_executeQuery($sql, array($this->getId(), $this->getNid()), $page, $itemsPerPage);

        if ($query) {
            while ($row = $query->fetch()) {
                $row['image']   = new File($row['uri']);
                $this->images[] = $row;
            }
        }

        return count($this->images);
    }

    /**
     * Loads all the attachments to this content.
     *
     * @param int
     * @param int
     *
     * @return int
     */
    protected function _loadAttachments($page = 0, $itemsPerPage)
    {
        $sql = "SELECT a.aid, a.fid, a.weight, uri, a.title, a.description
            FROM attachment a
            INNER JOIN file_managed fm ON fm.fid = a.fid
            INNER JOIN nodeAttachments na ON na.aid = a.aid
            WHERE na.id = ?
            AND na.nid = ?
            ORDER BY a.weight ASC";

        $query = db_executeQuery($sql, array($this->getId(), $this->getNid()), $page, $itemsPerPage);

        if ($query) {
            while ($row = $query->fetch()) {
                $row['file']         = new File($row['uri']);
                $this->attachments[] = $row;
            }
        }

        return count($this->attachments);
    }

    /**
     * Loads all the videos attached to this content.
     *
     * @param int
     * @param int
     *
     * @return int
     */
    protected function _loadVideos($page = 0, $itemsPerPage)
    {
        $sql = "SELECT v.* FROM videos v INNER JOIN nodeVideos nv ON nv.vid = v.vid
            WHERE nv.id = ? AND nv.nid = ? ORDER BY v.weight ASC";

        $query = db_executeQuery($sql, array($this->getId(), $this->getNid()), $page, $itemsPerPage);

        if ($query) {
            while ($row = $query->fetch()) {
                $video                   = Video::getInstance($row['provider']);
                $row['providerData']     = $video->get($row['token']);
                $row['thumbnail_small']  = $row['providerData']->thumbnail_small;
                $row['thumbnail_medium'] = $row['providerData']->thumbnail_medium;
                $row['thumbnail_large']  = $row['providerData']->thumbnail_large;

                $this->videos[]          = $row;
            }
        }

        return count($this->videos);
    }

    /**
     * Loads all thi links related to this content.
     *
     * @param int
     * @param int
     *
     * @return int
     */
    protected function _loadLinks($page = 0, $itemsPerPage)
    {
        $sql = "SELECT l.*
        FROM links l
        INNER JOIN nodeLinks nl ON nl.lid = l.lid
        INNER JOIN  ". $this->contentType ." ct ON ct.id = nl.id
        WHERE nl.id = ?
        AND nl.nid = ?
        ORDER by l.weight ASC";

        $query = db_executeQuery($sql, array($this->getId(), $this->getNid()), $page, $itemsPerPage);

        if ($query) {
            while ($row = $query->fetch()) {
                $this->links[$row['lid']] = $row;
                $this->links[$row['lid']]['newWindow'] = $this->links[$row['lid']]['newWindow'] == 1;
            }
        }

        return count($this->links);
    }

    /**
     * Loads all the attributes associated to the content.
     *
     * @param array
     *
     * @return void
     */
    protected function _loadAttrs($nodeData) {
        $nodeTimeCols    = array();
        $nodeBooleanCols = array();

        $sql      = "SHOW COLUMNS from $this->contentType WHERE Type IN ('tinyint(1)', 'timestamp')";
        $nodeCols = db_fetchAll($sql);

        foreach ($nodeCols as $oneCol) {
            if ($oneCol['Type'] == 'timestamp') {
                $nodeTimeCols[] = $oneCol['Field'];
            }
            else {
                $nodeBooleanCols[] = $oneCol['Field'];
            }
        }

        foreach ($nodeData as $fieldName => $fieldValue) {
            if (in_array($fieldName, $nodeBooleanCols)) {
                $this->$fieldName = ($fieldValue == 1);
            }
            elseif (in_array($fieldName, $nodeTimeCols)) {
                $this->$fieldName = !empty($fieldValue) ? new \DateTime($fieldValue) : NULL;
            }
            else {
                $this->$fieldName = $fieldValue;
            }
        }
    }

    /**
     * Loads all the attributes related to the content. Makes some checks previously to the load.
     *
     * @param int
     * @param string
     *
     * @return object
     */
    protected function _loadNode($nid, $lang)
    {
        $sql = "SELECT * from $this->contentType
        WHERE lang = ?
        AND nid = ?";

        $query       = db_executeQuery($sql, array($lang, $nid));
        $contentData = $query->fetch();

        $accessAvailable = ContentUtils::checkAvailableContent(getCurrentUser(), $this->contentType, $nid, $lang);

            //	var_dump(getCurrentUser());
        if (!empty($contentData) && $accessAvailable) {
            $this->_loadAttrs($contentData);
        }
        else {
            if (!$accessAvailable) {
                throw new NotFoundHttpException(t('Page not found'));
            }
            else {
                $sql = "SELECT COUNT(1) FROM $this->contentType
                    WHERE nid = ?";
                $num = db_fetchColumn($sql, array($nid));
                if ($num) {
                    throw new TranslateNotFound($lang);
                }
                else {
                    throw new NotFoundHttpException(t('Page not found'));
                }
            }
        }

        return (object) $contentData;
    }

    /**
     * Retrieves an array with the parents content ids for current object, order ASC by weight.
     *
     * @return array * Empty array if no parents content was found. Array with parents id otherwise.
     */
    protected function _getParents() {
        $sql       = "SELECT h.parentNid AS nid
                      FROM drufonyHierarchy h
                      WHERE h.nid = ? ORDER BY weight ASC";

        $result    = db_fetchAllColumn($sql, array($this->nid));

        return $result;
    }

    /**
     * Magic method to assign private attributes
     *
     * @param string
     * @param mixed
     *
     * @return void
     */
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }

    /**
     * Retrieves all the pool types which are related to this node
     *
     * @return array
     */
    public function getPools() {
        $sql         = "SELECT DISTINCT type FROM node_pools WHERE nid = ?";
        $queryParams = array($this->node);

        $query     = db_executeQuery($sql, $queryParams);

        $nodeTypes = array();
        while ($row = $query->fetch()) {
            $nodeTypes[] = $row['type'];
        }
        return $nodeTypes;
    }

    /**
     * Retrieves an associative array from all the attributes related to the content.
     * FIXME: Explains why is needed to implement thsi method.
     *
     * @return array
     */
    public function __toArray()
    {
        $arrayData = array();

        foreach ($this as $key => $value) {
            $arrayData[$key] = $value;
        }

        return $arrayData;
    }

    /**
     * Retrieves the geoposition associated to this content.
     *
     * @return array
     */
    public function getLocation() {
        if (!$this->location) {
            $sql            = 'SELECT * FROM locations WHERE nid = ?';
            $this->location = db_fetchAssoc($sql, array($this->getNid()));
        }

        return $this->location;
    }

    /**
     * Retrieves the latitude associated to this content.
     *
     * @return float
     */
    public function getLatitude() {
        $latitude = 0;

        if ($this->getLocation()) {
            $latitude = $this->location['latitude'];
        }

        return $latitude;
    }

    /**
     * Retrieves the longitude associated to this content.
     *
     * @return float
     */
    public function getLongitude() {
        $longitude = 0;

        if ($this->getLocation()) {
            $longitude = $this->location['longitude'];
        }

        return $longitude;
    }

    public function getFreeTags() {
        if (!$this->freeTags) {
            $this->freeTags = array();
            $allCategories = Category::getNodeCategories($this->nid);

            $sql  = "SELECT c.tid, c.name FROM category c INNER JOIN vocabulary v ON c.vid = v.vid ";
            $sql .= "WHERE c.tid IN ('" . implode("','", $allCategories) . "') AND v.name = ?";

            $queryResult = db_executeQuery($sql, array(FREE_TAGS));
            while ($row = $queryResult->fetch()) {
                $this->freeTags[$row['tid']] = $row['name'];
            }
        }

        return implode(',', $this->freeTags);
    }

    public function getTags() {

        if (!$this->tags) {
            $this->tags = array();

            $allCategories = Category::getNodeCategories($this->nid);

            $this->tags = $allCategories;
        }

        return $this->tags;
    }
}
