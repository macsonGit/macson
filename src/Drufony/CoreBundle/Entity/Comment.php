<?php
/**
 * It defines the Comment entity, which will be used to attach comments to a content
 * in the CMS. It includes static methods for handling comments without an instanced
 * object.
 */

namespace Drufony\CoreBundle\Entity;

use Drufony\CoreBundle\Model\Content;
use Drufony\CoreBundle\Model\ContentUtils;

/**
 * Implements comments in Drufony CMS
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Comment {
    const COMMENT_STATUS_CLOSED         = 0x01;
    const COMMENT_STATUS_OPENED         = 0x02;
    const COMMENT_STATUS_PREMODERATED   = 0x04;
    const COMMENT_STATUS_POSTMODERATED  = 0x08;

    /**
     * Identifies the comment with an unique id.
     *
     * @var int
     */
    protected $cid;

    /**
     * Identifies the parent id for the comment.
     *
     * @var int
     */
    protected $pid;

    /**
     * Identifies the content id which this comment is related.
     *
     * @var int
     */
    protected $nid;

    /**
     * Identifies the unique id from the content type specific table.
     * It's useful to decide which is the lang we need to show to the user.
     *
     * @var int
     *
     * FIXME: En unos métodos estamos usando lang para discriminar idiomas
     * y en otros el id. Hay que unificar criterios.
     */
    protected $id;

    /**
     * Identifies the user id who sends the comment.
     *
     * @var int
     */
    protected $uid;

    /**
     * Identifies the IP of the user who sends the comment.
     *
     * @var string
     */
    protected $ip;

    /**
     * Identifies the subject of the comment.
     *
     * @var string
     */
    protected $subject;

    /**
     * Logs the creation comment datetime.
     *
     * @var datetime
     */
    protected $created;

    /**
     * Logs the latest change comment datetime.
     *
     * @var datetime
     */
    protected $changed;

    /**
     * Identifies the status of the comment. Wether it's published publicly, moderated or unpublished.
     *
     * @var mixed
     */
    protected $status;

    /**
     * Identifies the username who sends the comment.
     *
     * @var string
     */
    protected $name;

    /**
     * Identifies the email of the user who sends the comment.
     *
     * @var string
     */
    protected $mail;

    /**
     * Identifies the body of the comment.
     *
     * @var string
     */
    protected $body;

    /**
     * Identifies de authorname. It's only useful for anonymous comments.
     *
     * @var string
     */
    protected $authorName;

    /*
     * Identifies comment deph, in order to show it, not stored in db
     *
     * @var int
     */
    protected $depth = 0;

    /**
     * Comment constructor. Retrieves Comment object by cid, or and empty Comment object instead
     *
     * @param int $cid
     *
     * @return void
     */
    public function __construct($cid = null) {
        if (!is_null($cid)) {
            $params = array();

            $sql      = "SELECT * FROM comment WHERE cid = ?";
            $params[] = $cid;

            $commentQuery = db_executeQuery($sql, $params);

            while ($commentData = $commentQuery->fetch()) {
                foreach ($commentData as $fieldName => $fieldValue) {
                    $this->__set($fieldName, $fieldValue);
                }
            }
        }
    }

    /**
     * Magic __set method. It's useful for setting whatever attribute from the Comment object.
     *
     * @param string $property
     * @param mixed $value
     *
     * @return void
     */
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }

    /**
     * Retrieves the comment id. Generic getter method.
     *
     * @return int
     */
    public function getCid() {
        return $this->cid;
    }

    /**
     * Retrieves the parent it. Generic getter method.
     *
     * @return int
     */
    public function getPid() {
        return $this->pid;
    }

    /**
     * Retrieves the specific id (lang-dependent) for the content in the contentType table.
     * Generic getter method.
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Retrieves the content id related to the comment. Generic getter method.
     *
     * @return int
     */
    public function getNid() {
        return $this->nid;
    }

    /**
     * Retrieves the uid of the user who sends the comment. Generic getter method.
     *
     * @return int
     */
    public function getUid() {
        return $this->uid;
    }

    /**
     * Retrieves th IP of the user who sends the comment. Generic getter method.
     *
     * @return string
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * Retrieves the subject of the comment. Generic getter method.
     *
     * @return string
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * Retrieves created comment datetime. Generic getter method.
     *
     * @return datetime
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Retrieves latest changed comment datetime. Generic getter method.
     *
     * @return datetime
     */
    public function getChanged() {
        return $this->changed;
    }

    /**
     * Retrieves the comment status. Generic getter method.
     *
     * @return bool
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Retrieves the name of the user who sends the comment. Generic getter method.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Retrieves the email associated to the user who sends the comment. Generic getter method.
     *
     * @return string
     */
    public function getMail() {
        return $this->mail;
    }

    /**
     * Retrieves the body of the comment. Generic getter method.
     *
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Retrieves content author name
     *
     * @return string $authorName
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

    public function getDepth() {
        return $this->depth;
    }

    /**
     * Retrieves the ContentType machineName for Comment
     *
     * @return string $contentType
     */
    public function getContentType() {
        $contentType = Content::TYPE_COMMENT;

        return $contentType;
    }

    /**
     * Retrieves all comments by status, language and/or limiting the results.
     *
     * @param int $status
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $comments
     */
    static public function getAll($status = null, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $params = array();

        $sql = "SELECT * FROM comment WHERE 1";

        if(!is_null($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $commentsData = db_fetchAll($sql, $params, $page, $itemsPerPage);
        $comments     = ContentUtils::getContentObjects($commentsData, Content::TYPE_COMMENT);

        return $comments;
    }

    /**
     * Retrieves the number of all comments by status.
     *
     * @param int $status
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return int
     */
    static public function getAllCount($status = null) {
        $sql = "SELECT COUNT(*) FROM comment WHERE 1";

        $params = array();
        if(!is_null($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $count = db_fetchColumn($sql, $params);

        return $count;
    }

    /**
     * Removes the comment from database.
     *
     * @param void
     *
     * @return void
     */
    public function remove() {
        db_delete('comment', array('cid' => $this->cid));
    }

    /**
     * Approves the comment from database.
     *
     * @param void
     *
     * @return void
     */
    public function approve() {
        if (db_update('comment', array('status' => 1), array('cid' => $this->cid))) {
            l('INFO', 'Comment cid: ' . $this->cid . ' approved successfully');
        }
    }

    /**
     * Retrieves allowed comment statuses
     *
     * @return array $enabledCommentStatus
     *
     * TODO: Check another way to do this (getDefinedCommentsStatus).
     */
    static public function getAllowedCommentStatus() {
        $enabledCommentStatus = array();

        if ( COMMENT_STATUS_MODE & self::COMMENT_STATUS_CLOSED )
            $enabledCommentStatus[self::COMMENT_STATUS_CLOSED] = t('Closed');

        if ( COMMENT_STATUS_MODE & self::COMMENT_STATUS_OPENED )
            $enabledCommentStatus[self::COMMENT_STATUS_OPENED] = t('Opened');

        if ( COMMENT_STATUS_MODE & self::COMMENT_STATUS_PREMODERATED )
            $enabledCommentStatus[self::COMMENT_STATUS_PREMODERATED] = t('Pre-moderated');

        if ( COMMENT_STATUS_MODE & self::COMMENT_STATUS_POSTMODERATED )
            $enabledCommentStatus[self::COMMENT_STATUS_POSTMODERATED] = t('Post-moderated');

        return $enabledCommentStatus;
    }
}
