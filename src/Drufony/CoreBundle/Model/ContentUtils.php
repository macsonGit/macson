<?php
/**
 * Implementation of static Content class. Useful to handle sets of
 * contents based on several criteria. Most of them return an array
 * of Content instances. It defines all the common methods which will
 * be used to manage lists.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Drufony\CoreBundle\Entity\User;
use Drufony\CoreBundle\Model\Mailing;
use Drufony\CoreBundle\Model\Locale;
use Drufony\CoreBundle\Model\Section;
use Drufony\CoreBundle\Model\Item;
use Drufony\CoreBundle\Model\Page;
use Drufony\CoreBundle\Model\Product;
use Drufony\CoreBundle\Entity\Comment;
use Drufony\CoreBundle\Form\ContactFormType;
use Drufony\CoreBundle\Exception\ContentTypeNotFound;

// Class constants
// Defines constants if undefined. Those constants could be defined in
// a setting file.
defined('DEFAULT_LANGUAGE')      or define('DEFAULT_LANGUAGE','en');
defined('HOMEPAGE_SECTION_TYPE') or define('HOMEPAGE_SECTION_TYPE', 'homepage');
defined('BLOG_SECTION_TYPE')     or define('BLOG_SECTION_TYPE', 'blog');
defined('ROLE_ADMIN')            or define('ROLE_ADMIN', 'ROLE_ADMIN');

/**
 * Static Content class. Useful form managing sets of Content instances.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class ContentUtils
{
    /**
     * Retrieves a content object just by its nodeId.
     *
     * @param int $nid: node id.
     * @param string $lang: requested language.
     *
     * @return Content object (Page, Item, Section, Product)
     */
    public static function nodeLoad($nid, $lang = DEFAULT_LANGUAGE, $contentType = null) {
        if (is_null($contentType)) {
            $contentType = self::getContentType($nid);
        }

        $node = call_user_func('self::_get' . ucfirst(strtolower($contentType)), $nid, $lang);

        return $node;
    }

    /**
     * Retrieves a Section object just by its nodeId.
     *
     * @param int $nid: node id.
     * @param string $lang: requested language.
     *
     * @return Section
     */
    private static function _getSection($nid = null, $lang = DEFAULT_LANGUAGE) {
        return new Section($nid, $lang);
    }

    /**
     * Retrieves an Item object just by its nodeId.
     *
     * @param int $nid: node id.
     * @param string $lang: requested language.
     *
     * @return Item
     */
    private static function _getItem($nid = null, $lang = DEFAULT_LANGUAGE) {
        return new Item($nid, $lang);
    }

    /**
     * Retrieves a Page object just by its nodeId.
     *
     * @param int $nid: node id.
     * @param string $lang: requested language.
     *
     * @return Page
     */
    private static function _getPage($nid = null, $lang = DEFAULT_LANGUAGE) {
        return new Page($nid, $lang);
    }

    /**
     * Retrieves a Product object just by its nodeId.
     *
     * @param int $nid: node id.
     * @param string $lang: requested language.
     *
     * @return Product
     */
    private static function _getProduct($nid = null, $lang = DEFAULT_LANGUAGE) {
        return new Product($nid, $lang);
    }

    /**
     * Retrieves a Comment object just by its CommentId.
     *
     * @param int $cid: comment id.
     * @param string $lang: requested language.
     *
     * @return Comment
     */
    private static function _getComment($cid = null, $lang = DEFAULT_LANGUAGE) {
        return new Comment($cid, $lang);
    }

    /**
     * Retrieves promoted content from a given content machine name.
     *
     * @param string $contentType
     * @param string $lang
     * @param int $page
     * @param int $published 0: unpublished, 1: published, null: all
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getPromoted($contentType, $lang = null, $page = 0, $published = null,
                                       $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {

        $configValues = array(
            'promoted'    => 1,
            'page'        => $page,
            'lang'        => $lang,
            'published'   => $published,
            'type'        => $type,
            'orderFields' => array($orderField => $orderCriteria),
        );

        $promotedContent = self::_getContentsByContentType($configValues, $contentType);

        return $promotedContent;
    }

    /**
     * Retrieves the amount of promoted contents from a given content type machine name.
     *
     * @param string $contentType
     * @param string $lang
     * @param int $published 0: unpublished, 1: published, null: all
     * @param string $type
     * @return void
     */
    public static function getPromotedCount($contentType, $lang = null, $published = null, $type = null) {
        $configValues = array(
            'contentTypes' => array($contentType),
            'promoted'     => 1,
            'published'    => $published,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $promotedContentCount = self::_getAllContentsCount($configValues);

        return $promotedContentCount;
    }

    /**
     * Retrieves promoted content from a given content type machine name and user.
     *
     * @param int $uid
     * @param string $contentType
     * @param string $lang
     * @param int $page
     * @param int $published 0: unpublished, 1: published, null: all
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getPromotedByUser($uid, $contentType, $lang = null, $page = 0, $published = null,
                                       $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {

        $configValues = array(
            'promoted'    => 1,
            'page'        => $page,
            'lang'        => $lang,
            'published'   => $published,
            'type'        => $type,
            'uid'         => $uid,
            'orderFields' => array($orderField => $orderCriteria),
        );

        $promotedContent = self::_getContentsByContentType($configValues, $contentType);

        return $promotedContent;
    }

    /**
     * Retrieves the amount of promoted contents from a given content type machine name and user.
     *
     * @param int $uid
     * @param string $contentType
     * @param string $lang
     * @param int $published 0: unpublished, 1: published, null: all
     * @param string $type
     * @return void
     */
    public static function getPromotedCountByUser($uid, $contentType, $lang = null, $published = null, $type = null) {
        $configValues = array(
            'contentTypes' => array($contentType),
            'promoted'     => 1,
            'published'    => $published,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $promotedContentCount = self::_getAllContentsCount($configValues);

        return $promotedContentCount;
    }

    /**
     * Retrieves sticky content from a given content type machine name.
     *
     * @param string $contentType
     * @param string $lang
     * @param int $page
     * @param int $published 0: unpublished, 1: published, null: all
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    static public function getSticky($contentType, $lang = null, $page = 0, $published = null,
                                     $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {

        $configValues = array(
            'sticky'      => 1,
            'page'        => $page,
            'lang'        => $lang,
            'published'   => $published,
            'type'        => $type,
            'orderFields' => array($orderField => $orderCriteria),
        );

        $stickyContent = self::_getContentsByContentType($configValues, $contentType);

        return $stickyContent;
    }

    /**
     * Retrieves the amount of sticky contents from a given content type machine name.
     *
     * @param string $contentType
     * @param string $lang
     * @param int $published 0: unpublished, 1: published, null: all
     * @param string $type
     * @return void
     */
    public static function getStickyCount($contentType, $lang = null, $published = null, $type = null) {
        $configValues = array(
            'contentTypes' => array($contentType),
            'sticky'       => 1,
            'published'    => $published,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $stickyContentCount = self::_getAllContentsCount($configValues);

        return $stickyContentCount;
    }

    /**
     * Retrieves sticky content from a given content type machine name and user.
     *
     * @param int $uid
     * @param string $contentType
     * @param string $lang
     * @param int $page
     * @param int $published 0: unpublished, 1: published, null: all
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    static public function getStickyByUser($uid, $contentType, $lang = null, $page = 0, $published = null,
                                     $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {

        $configValues = array(
            'sticky'      => 1,
            'page'        => $page,
            'lang'        => $lang,
            'published'   => $published,
            'type'        => $type,
            'uid'         => $uid,
            'orderFields' => array($orderField => $orderCriteria),
        );

        $stickyContent = self::_getContentsByContentType($configValues, $contentType);

        return $stickyContent;
    }

    /**
     * Retrieves the amount of sticky contents from a given content type machine name and user.
     *
     * @param int $uid
     * @param string $contentType
     * @param string $lang
     * @param int $published 0: unpublished, 1: published, null: all
     * @param string $type
     * @return void
     */
    public static function getStickyCountByUser($uid, $contentType, $lang = null, $published = null, $type = null) {
        $configValues = array(
            'contentTypes' => array($contentType),
            'sticky'       => 1,
            'published'    => $published,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $stickyContentCount = self::_getAllContentsCount($configValues);

        return $stickyContentCount;
    }

    /**
     * Retrieves published content from a given content type machine name.
     *
     * @param string $contentType
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    static public function getPublished($contentType, $lang = null, $page = 0,
                                     $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {

        $configValues = array(
            'page'        => $page,
            'lang'        => $lang,
            'published'   => 1,
            'type'        => $type,
            'orderFields' => array($orderField => $orderCriteria),
        );

        $publishedContent = self::_getContentsByContentType($configValues, $contentType);

        return $publishedContent;
    }

    /**
     * Retrieves the amount of published contents from a given content type machine name.
     *
     * @param string $contentType
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getPublishedCount($contentType, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => array($contentType),
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Retrieves published content from a given content type machine name and user.
     *
     * @param int $uid
     * @param string $contentType
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    static public function getPublishedByUser($uid, $contentType, $lang = null, $page = 0,
                                     $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {

        $configValues = array(
            'page'        => $page,
            'lang'        => $lang,
            'published'   => 1,
            'type'        => $type,
            'uid'         => $uid,
            'orderFields' => array($orderField => $orderCriteria),
        );

        $publishedContent = self::_getContentsByContentType($configValues, $contentType);

        return $publishedContent;
    }

    /**
     * Retrieves the amount of published contents from a given content type machine name and user.
     *
     * @param int $uid
     * @param string $contentType
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getPublishedCountByUser($uid, $contentType, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => array($contentType),
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Retrieves unpublished content from a given content type machine name.
     *
     * @param string $contentType
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    static public function getUnpublished($contentType, $lang = null, $page = 0,
                                     $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {

        $configValues = array(
            'page'        => $page,
            'lang'        => $lang,
            'published'   => 0,
            'type'        => $type,
            'orderFields' => array($orderField => $orderCriteria),
        );

        $unpublishedContent = self::_getContentsByContentType($configValues, $contentType);

        return $unpublishedContent;
    }

    /**
     * Retrieves the amount of unpublished contents from a given content type machine name.
     *
     * @param string $contentType
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getUnpublishedCount($contentType, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => array($contentType),
            'published'    => 0,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Retrieves unpublished content from a given content type machine name and user.
     *
     * @param int $uid
     * @param string $contentType
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    static public function getUnpublishedByUser($uid, $contentType, $lang = null, $page = 0,
                                     $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {

        $configValues = array(
            'page'        => $page,
            'lang'        => $lang,
            'published'   => 0,
            'type'        => $type,
            'uid'         => $uid,
            'orderFields' => array($orderField => $orderCriteria),
        );

        $unpublishedContent = self::_getContentsByContentType($configValues, $contentType);

        return $unpublishedContent;
    }

    /**
     * Retrieves the amount of unpublished contents from a given content type machine name and user.
     *
     * @param int $uid
     * @param string $contentType
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getUnpublishedCountByUser($uid, $contentType, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => array($contentType),
            'published'    => 0,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Retrieves all published content.
     *
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getAllPublished($lang = null, $page = 0, $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'page'         => $page,
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $unpublishedContent = self::_getAllContents($configValues);

        return $unpublishedContent;
    }

    /**
     * Retrieves the amount of all published contents.
     *
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getAllPublishedCount($lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Retrieves all published content from a given user.
     *
     * @param int $uid
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getAllPublishedByUser($uid, $lang = null, $page = 0, $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'page'         => $page,
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $unpublishedContent = self::_getAllContents($configValues);

        return $unpublishedContent;
    }

    /**
     * Retrieves the amount of all published contents from a given user.
     *
     * @param int $uid
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getAllPublishedCountByUser($uid, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Retrieves all promoted content.
     *
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getAllPromoted($lang = null, $page = 0, $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'page'         => $page,
            'promoted'     => 1,
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $promotedContent = self::_getAllContents($configValues);

        return $promotedContent;
    }

    /**
     * Retrieves the amount of all promoted contents.
     *
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getAllPromotedCount($lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'promoted'     => 1,
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $promotedContentCount = self::_getAllContentsCount($configValues);

        return $promotedContentCount;
    }

    /**
     * Retrieves all promoted content from a given user.
     *
     * @param int $uid
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getAllPromotedByUser($uid, $lang = null, $page = 0, $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'page'         => $page,
            'promoted'     => 1,
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $promotedContent = self::_getAllContents($configValues);

        return $promotedContent;
    }

    /**
     * Retrieves the amount of all promoted contents from a given user.
     *
     * @param int $uid
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getAllPromotedCountByUser($uid, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'promoted'     => 1,
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $promotedContentCount = self::_getAllContentsCount($configValues);

        return $promotedContentCount;
    }

    /**
     * Retrieves all sticky content.
     *
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getAllSticky($lang = null, $page = 0, $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'page'         => $page,
            'sticky'       => 1,
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $stickyContent = self::_getAllContents($configValues);

        return $stickyContent;
    }

    /**
     * Retrieves the amount of all sticky contents.
     *
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getAllStickyCount($lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'sticky'       => 1,
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $stickyContentCount = self::_getAllContentsCount($configValues);

        return $stickyContentCount;
    }

    /**
     * Retrieves all sticky content from a given user.
     *
     * @param int $uid
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getAllStickyByUser($uid, $lang = null, $page = 0, $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'page'         => $page,
            'sticky'       => 1,
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $stickyContent = self::_getAllContents($configValues);

        return $stickyContent;
    }

    /**
     * Retrieves the amount of all sticky contents from a given user.
     *
     * @param int $uid
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getAllStickyCountByUser($uid, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'sticky'       => 1,
            'published'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $stickyContentCount = self::_getAllContentsCount($configValues);

        return $stickyContentCount;
    }

    /**
     * Retrieves all unpublished content.
     *
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getAllUnpublished($lang = null, $page = 0, $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'page'         => $page,
            'published'    => 0,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $unpublishedContent = self::_getAllContents($configValues);

        return $unpublishedContent;
    }

    /**
     * Retrieves the amount of all unpublished contents.
     *
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getAllUnpublishedCount($lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'published'    => 0,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Retrieves all unpublished content from a given user.
     *
     * @param int $uid
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getAllUnpublishedByUser($uid, $lang = null, $page = 0, $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'page'         => $page,
            'published'    => 0,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $unpublishedContent = self::_getAllContents($configValues);

        return $unpublishedContent;
    }

    /**
     * Retrieves the amount of all unpublished contents from a given user.
     *
     * @param int $uid
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getAllUnpublishedCountByUser($uid, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'published'    => 0,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Retrieves all scheduled content.
     *
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getAllScheduled($lang = null, $page = 0, $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'page'         => $page,
            'published'    => 0,
            'scheduled'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $unpublishedContent = self::_getAllContents($configValues);

        return $unpublishedContent;
    }

    /**
     * Retrieves the amount of all scheduled contents.
     *
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getAllScheduledCount($lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'published'    => 0,
            'scheduled'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Retrieves all scheduled content from a given user.
     *
     * @param int $uid
     * @param string $lang
     * @param int $page
     * @param string $orderField
     * @param string $orderCriteria
     * @param string $type
     * @return void
     */
    public static function getAllScheduledByUser($uid, $lang = null, $page = 0, $orderField = 'publicationDate', $orderCriteria = 'ASC', $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'page'         => $page,
            'published'    => 0,
            'scheduled'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $unpublishedContent = self::_getAllContents($configValues);

        return $unpublishedContent;
    }

    /**
     * Retrieves the amount of all scheduled contents from a given user.
     *
     * @param int $uid
     * @param string $lang
     * @param string $type
     * @return void
     */
    public static function getAllScheduledCountByUser($uid, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => self::getAvailableContentTypes(),
            'published'    => 0,
            'scheduled'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Generic method for retrieving an array of Content objects from giving values
     *
     * @param array $configValues
     *      accepted values:
     *          string contentType
     *          int    page
     *          array  orderFields (array( string fieldName => string "ASC|DESC"))
     *          bool   scheduled
     *          int    promoted
     *          int    sticky
     *          int    published
     *          int    showInCalendar
     *          string lang
     *
     *      Notes: Schedule should be 1 to retrieve contents with futurePublicationDate enabled.
     *
     * @return array Contents
     */
    static private function _getContentsByContentType($configValues, $contentType) {
        $queryParams = array();

        if (!$contentType) {
            throw new ContentTypeNotFound();
        }

        $page         = isset($configValues['page'])         ? $configValues['page']         : null;
        $itemsPerPage = isset($configValues['itemsPerPage']) ? $configValues['itemsPerPage'] : ITEMS_PER_PAGE;
        $orderFields  = isset($configValues['orderFields'])  ? $configValues['orderFields']  : null;
        $scheduled    = isset($configValues['scheduled'])    ? $configValues['scheduled']    : null;

        $filterFields = array(
            'promoted'       => (isset($configValues['promoted'])       ? $configValues['promoted']       : null),
            'sticky'         => (isset($configValues['sticky'])         ? $configValues['sticky']         : null),
            'published'      => (isset($configValues['published'])      ? $configValues['published']      : null),
            'showInCalendar' => (isset($configValues['showInCalendar']) ? $configValues['showInCalendar'] : null),
            'lang'           => (isset($configValues['lang'])           ? $configValues['lang']           : null),
            'type'           => (isset($configValues['type'])           ? $configValues['type']           : null),
            'uid'            => (isset($configValues['uid'])            ? $configValues['uid']            : null),
        );

        $sqlContent  = "SELECT ct.*, uf.target as url
                        FROM ${contentType} ct INNER JOIN url_friendly uf ON (ct.id = uf.oid)
                        WHERE uf.expirationDate is NULL AND uf.module = ? ";

        $queryParams[] = $contentType;
        foreach($filterFields as $fieldName => $fieldValue) {
            if (!is_null($fieldValue)) {
                $sqlContent    .= " AND ${fieldName} = ? ";
                $queryParams[]  = $fieldValue;
            }
        }

        if ($scheduled) {
            $sqlContent .= " AND futurePublicationDate IS NOT NULL ";
        }

        if (is_array($orderFields)) {
            foreach ($orderFields as $oneOrderField => $orderCriteria) {
                $orderString[] = "${oneOrderField} ${orderCriteria}";
            }

            $sqlContent .= "ORDER BY " . implode($orderString, ",");
        }

        $contentQuery = db_executeQuery($sqlContent, $queryParams, $page, $itemsPerPage);
        $contentData  = $contentQuery->fetchAll();

        $contents = self::getContentObjects($contentData, $contentType);

        return $contents;
    }

    /**
     * Retrieves Content objects from data in arrays
     *
     * @param array $contentData
     * @param string $contentType
     * @return array
     */
    static public function getContentObjects($contentData, $contentType) {
        $allContents = array();

        if (!empty($contentData)) {
            foreach ($contentData as $oneContent) {
                $content = call_user_func('self::_get' . ucfirst(strtolower($contentType)));

                foreach($oneContent as $fieldName => $fieldValue) {
                    $content->__set($fieldName, $fieldValue);
                }

                $allContents[] = $content;
            }
        }

        return $allContents;
    }

    /**
     * Retrieves mixed content objects array. It must be used JUST when you need to show content
     * list mixing several content types.
     *
     * @author Fran & Trunks. Any doubt or bug must be reported to them.
     * @param array $configValues
     *      accepted values:
     *          array  contentTypes
     *          int    page
     *          array  orderFields (array( string fieldName => string "ASC|DESC"))
     *          bool   scheduled
     *          int    promoted
     *          int    sticky
     *          int    published
     *          int    showInCalendar
     *          string lang
     *
     *      Notes: Schedule should be 1 to retrieve contents with futurePublicationDate enabled.
     *
     * @return array contents
     *
     */
    private static function _getContents($configValues) {
        $contentTypes = isset($configValues['contentTypes']) ? $configValues['contentTypes'] : null;

        if (!is_array($contentTypes) || empty($contentTypes)) {
            throw new ContentTypeNotFound();
        }

        # Check contentTypes number. If just 1, execution flow forks to _getContentsByContentType method.
        if (!is_array($contentTypes) || count($contentTypes <= 1)) {
            $contentType = is_array($contentTypes) ? $contentTypes[0] : $contentTypes;
            $contents    = self::_getContentsByContentType($configValues, $contentType);
        }
        else {
            $contents = self::_getAllContents($configValues);
        }

        return $contents;
    }

    static private function _getAllContentsSqlParams($configValues) {
        $queryParams     = array();
        $where           = "";
        $orderBy         = "";
        $tableJoins      = "";
        $scheduledCondition = array();

        $contentTypes = isset($configValues['contentTypes']) ? $configValues['contentTypes'] : null;
        $page         = isset($configValues['page'])         ? $configValues['page']         : null;
        $itemsPerPage = isset($configValues['itemsPerPage']) ? $configValues['itemsPerPage'] : ITEMS_PER_PAGE;
        $orderFields  = isset($configValues['orderFields'])  ? $configValues['orderFields']  : null;
        $scheduled    = isset($configValues['scheduled'])    ? $configValues['scheduled']    : null;

        $filterFields = array(
            'promoted'       => (isset($configValues['promoted'])       ? $configValues['promoted']       : null),
            'sticky'         => (isset($configValues['sticky'])         ? $configValues['sticky']         : null),
            'published'      => (isset($configValues['published'])      ? $configValues['published']      : null),
            'showInCalendar' => (isset($configValues['showInCalendar']) ? $configValues['showInCalendar'] : null),
            'lang'           => (isset($configValues['lang'])           ? $configValues['lang']           : null),
            'type'           => (isset($configValues['type'])           ? $configValues['type']           : null),
            'uid'            => (isset($configValues['uid'])            ? $configValues['uid']           : null),
        );

        foreach ($contentTypes as $oneContentType) {
            $tableJoins .= " LEFT JOIN ${oneContentType} ON (node.nid = ${oneContentType}.nid)";
        }

        foreach($filterFields as $fieldName => $fieldValue) {
            $fieldsCondition = array();

            if (!is_null($fieldValue)) {
                foreach ($contentTypes as $oneContentType) {
                    $fieldsCondition[] = " ${oneContentType}.${fieldName} = ? ";
                    $queryParams[]     = $fieldValue;
                }

                $where .= " AND (" . implode("OR", $fieldsCondition) . ")";
            }
        }

        if ($scheduled) {
            foreach ($contentTypes as $oneContentType) {
                $scheduledCondition[] = " ${oneContentType}.futurePublicationDate IS NOT NULL ";
            }
            $where .= " AND (" . implode("OR", $scheduledCondition) . ")";
        }

        $typeTokens   = implode(",", array_fill(0, count($contentTypes), "?"));
        $where       .= " AND node.type IN (${typeTokens})";
        $queryParams  = array_merge($queryParams, $contentTypes);

        if (is_array($orderFields)) {
            foreach ($orderFields as $oneOrderField => $orderCriteria) {
                foreach ($contentTypes as $oneContentType) {
                    $orderVirtualField[] = "${oneContentType}.${oneOrderField}";
                }
                $orderField        = ", COALESCE(" . implode(",", $orderVirtualField) . ") as virtual" . $oneOrderField;
                $orderConditions[] = "virtual${oneOrderField} ${orderCriteria}";
            }

            $orderBy .= "ORDER BY " . implode($orderConditions, ",");
        }

        return array($orderField, $tableJoins, $where, $orderBy, $queryParams, $page, $itemsPerPage);
    }

    static private function _getAllContents($configValues) {
        list($orderField, $tableJoins, $where, $orderBy,
            $queryParams, $page, $itemsPerPage) = self::_getAllContentsSqlParams($configValues);

        // FIXME Creo que aquí hay un bug.
        $sql = "SELECT node.nid, node.type ${orderField}
                FROM node ${tableJoins}
                WHERE 1 ${where} ${orderBy}";

        $contentQuery = db_executeQuery($sql, $queryParams, $page, $itemsPerPage);
        $contentData  = $contentQuery->fetchAll();

        $contents = self::getContentObjectsByMixedArray($contentData);

        return $contents;
    }

    static private function _getAllContentsCount($configValues) {
        list($orderField, $tableJoins, $where, $orderBy,
            $queryParams, $page, $itemsPerPage) = self::_getAllContentsSqlParams($configValues);

        // FIXME Creo que aquí hay un bug.
        $sql = "SELECT count(*) ${orderField}
                FROM node ${tableJoins}
                WHERE 1 ${where} ${orderBy}";

        $count = db_fetchColumn($sql, $queryParams);

        return $count;
    }

    static public function getContentObjectsByMixedArray($contentData) {
        $contents       = array();
        $arrayObjects   = array();
        $contentByType  = array();
        $sortedObjects  = array();

        // Group nids by contentType and gets original order array.
        foreach($contentData as $index => $oneContent) {
            $contentByType[$oneContent['type']][] = $oneContent['nid'];
            $contentOrder[$oneContent['nid']]     = $index;
        }

        // Builds an array of objects based on the nids of each content type.
        foreach ($contentByType as $contentTypeName => $contentNids) {
            $nidTokens = implode(",", array_fill(0, count($contentNids), "?"));
            $sql       = "SELECT * FROM ${contentTypeName} WHERE nid IN (${nidTokens})";

            $query              = db_executeQuery($sql, $contentNids);
            $oneTypeContentData = $query->fetchAll();

            $arrayObjects = array_merge($arrayObjects, self::getContentObjects($oneTypeContentData, $contentTypeName));
        }

        // Builds a new array based on the original sorted data.
        foreach ($arrayObjects as $oneObject) {
            $sortedObjects[$contentOrder[$oneObject->getNid()]] = $oneObject;
        }

        ksort($sortedObjects);

        return $sortedObjects;
    }


    /**
     * Retrieves the content type machine name from a node id
     *
     * @param int $nid: the node id
     * @return array with a string that define the content type.
     */
    static public function getContentType($nid) {
        $contentType = null;

        $sql         = "SELECT type from node WHERE nid = ?";
        $contentType = db_fetchColumn($sql, array($nid));

        return $contentType;
    }

    /**
     * Retrieves the available content types
     *
     * @return array with type of contents.
     */
    public static function getAvailableContentTypes() {
        $result = array();
        $query  = "SELECT DISTINCT type FROM node";

        return db_fetchAllColumn($query);
    }

    /**
     * Retrieves the scheduled contents.
     *
     * @param int $page: page numebr to retrive. Integer
     * @param itemsPerPage: number of item per pager. By default constant ITEMS_PER_PAGE
     * @return array with the id of desired contents, empty array otherwise.
     */
    public static function getScheduled($contentType, $page = 0, $itemsPerPage = ITEMS_PER_PAGE, $type = null) {
        $configValues = array(
            'page'          => $page,
            'scheduled'     => 1,
            'itemsPerPage'  => $itemsPerPage,
            'type'          => $type,
        );

        $scheduledContent = self::_getContentsByContentType($configValues, $contentType);

        return $scheduledContent;
    }

    public static function getScheduledCount($contentType, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => array($contentType),
            'published'    => 0,
            'scheduled'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    public static function getScheduledByUser($uid, $contentType, $page = 0, $itemsPerPage = ITEMS_PER_PAGE, $type = null) {
        $configValues = array(
            'page'          => $page,
            'scheduled'     => 1,
            'itemsPerPage'  => $itemsPerPage,
            'type'          => $type,
            'uid'           => $uid,
        );

        $scheduledContent = self::_getContentsByContentType($configValues, $contentType);

        return $scheduledContent;
    }

    public static function getScheduledCountByUser($uid, $contentType, $lang = null, $type = null) {
        $configValues = array(
            'contentTypes' => array($contentType),
            'published'    => 0,
            'scheduled'    => 1,
            'lang'         => $lang,
            'type'         => $type,
            'uid'          => $uid,
            'orderFields'  => array('publicationDate' => 'ASC'),
        );

        $unpublishedContentCount = self::_getAllContentsCount($configValues);

        return $unpublishedContentCount;
    }

    /**
     * Retrieves events.
     *
     * @param int $page: page numebr to retrive. Integer
     * @param itemsPerPage: number of item per pager. By default constant ITEMS_PER_PAGE
     * @return array with the id of desired contents, empty array otherwise.
     */
    public static function getEvents($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $contentType  = 'item';
        $configValues = array(
            'page'          => $page,
            'scheduled'     => 1,
            'itemsPerPage'  => $itemsPerPage,
        );

        $scheduledContent = self::_getContentsByContentType($configValues, $contentType);

        return $scheduledContent;
    }

    /**
     * Retrieves the number of contents in a language.
     *
     * @param int $contentType
     * @param string $lang: language to get results. DEFAULT_LANGUAGE by default it can be null for
     * all languages
     * @return int with total results.
     */
    public static function getCountByLang($contentType, $lang = DEFAULT_LANGUAGE, $type = null) {
        $sql  = "SELECT COUNT(DISTINCT nid) ";
        $sql .= "FROM ${contentType} ";
        $sql .= "WHERE lang = ?";

        $queryParams = array($lang);
        if (!is_null($type)) {
            $sql           .= " AND ${contentType}.type = ?";
            $queryParams[]  = $type;
        }

        $count = db_fetchColumn($sql, $queryParams);

        return $count;
    }

    /**
     * Retrieves the number of contents translated in a language.
     * By default it will retrieve the number of all the contents type translated in a language
     *
     * @param int $contentType
     * @param string $lang: language to get results. DEFAULT_LANGUAGE by default
     * @return int with total results.
     */
    public static function getTranslatedCountByLang($contentType, $lang = DEFAULT_LANGUAGE, $type = null) {
        $sql  = "SELECT COUNT(*) count ";
        $sql .= "FROM node ";
        $sql .= "INNER JOIN ${contentType} translated ";
        $sql .= "ON node.nid = translated.nid ";
        $sql .= "WHERE translated.lang = ?";

        $queryParams = array($lang);
        if (!is_null($type)) {
            $sql           .= ' AND translated.type = ?';
            $queryParams[]  = $type;
        }

        $count = db_fetchColumn($sql, $queryParams);

        return $count;
    }

    /**
     * Retrieves the contents translated in a language.
     *
     * @param int $contentType
     * @param string $lang: language to get results. DEFAULT_LANGUAGE by default
     * @param int $page: page numebr to retrieve. Integer
     * @param itemsPerPage: number of item per pager. By default constant ITEMS_PER_PAGE
     * @return array with the id of desired contents, empty array otherwise.
     */
    public static function getTranslatedByLang($contentType, $lang = DEFAULT_LANGUAGE, $page = 0, $itemsPerPage = ITEMS_PER_PAGE, $type = null) {
        $sql  = "SELECT * ";
        $sql .= "FROM node ";
        $sql .= "INNER JOIN ${contentType} translated ";
        $sql .= "ON node.nid = translated.nid ";
        $sql .= "WHERE translated.lang = ? ";

        $queryParams = array($lang);
        if (!is_null($type)) {
            $sql           .= ' AND translated.type = ?';
            $queryParams[]  = $type;
        }

        $contentData = db_executeQuery($sql, $queryParams, $page, $itemsPerPage);
        $contents    = self::getContentObjects($contentData, $contentType);

        return $contents;
    }

    /**
     * Retrieves the contents type percentaje translated to a language.
     * By default it will retrieve all the contents type percentaje translated to a language.
     *
     * @param int $contentType
     * @param string $lang: language to get results. DEFAULT_LANGUAGE by default
     * @return int with the result.
     */
    public static function getTranslatedPercentage($contentType, $lang = DEFAULT_LANGUAGE, $type = null) {
        $contentsDefault = self::getCountByLang($contentType, DEFAULT_LANGUAGE, $type);
        $translated = self::getTranslatedCountByLang($contentType, $lang, $type);

        if ($contentsDefault != 0) {
            $translatedPercent = ($translated * 100.0) / $contentsDefault;
        }
        else {
            $translatedPercent = 0;
        }

        return $translatedPercent;
    }

    /**
     * Retrieves the number of contents not translated in a language.
     * By default it will retrieve the number of all the contents type not translated in a language
     *
     * @param int $contentType
     * @param string $lang: language to get results. DEFAULT_LANGUAGE by default
     * @return int with total results.
     */
    public static function getUntranslatedCountByLang($contentType, $lang = DEFAULT_LANGUAGE, $type = null) {
        $contentsDefault = self::getCountByLang($contentType, DEFAULT_LANGUAGE, $type);
        $translated      = self::getTranslatedCountByLang($contentType, $lang, $type);

        $result = $contentsDefault - $translated;

        return $result;
    }

    /**
     * Retrieves the contents type not translated to a language.
     *
     * @param int $contentType
     * @param string $lang: language to get results. DEFAULT_LANGUAGE by default
     * @param int $page: page numebr to retrive. Integer
     * @param itemsPerPage: number of item per pager. By default constant ITEMS_PER_PAGE
     * @return array with the id of desired contents, empty array otherwise.
     */
    public static function getUntranslatedByLang($contentType, $lang = DEFAULT_LANGUAGE, $page = 0,
                                                 $itemsPerPage = ITEMS_PER_PAGE, $type = null) {
        $sql  = "SELECT * ";
        $sql .= "FROM node ";
        $sql .= "LEFT JOIN ${contentType} translated ";
        $sql .= "ON translated.nid = node.nid AND translated.lang = ? ";

        $queryParams = array($lang, $contentType);
        if (!is_null($type)) {
            $sql           .= ' AND translated.type = ?';
            $queryParams[]  = $type;
        }

        $sql .= "WHERE node.type = ? and translated.id is NULL ";

        $contentData = db_executeQuery($sql, $queryParams, $page, $itemsPerPage);
        $contents    = self::getContentObjects($contentData, $contentType);

        return $contents;
    }

    /**
     * Returns homepage section
     *
     * @return int $nid of section
     */
    public static function getHomepageSection() {
        $sql = 'SELECT nid FROM section WHERE type = ?';
        $homepageNid = db_executeQuery($sql, array(HOMEPAGE_SECTION_TYPE))->fetch();

        if (empty($homepageNid['nid'])) {
            //Throw exception if any homepage has been defined
            l(ERROR, "Homepage Section doesn't defined");
            throw new NotFoundHttpException("Homepage Section doesn't found");
        }

        $nid = isset($homepageNid['nid']) ? $homepageNid['nid'] : NULL;

        return $nid;
    }

    /**
     * Returns main content blog
     *
     * @return int $nid
     */
    public static function getMainBlogContent() {
        $homepageNid = self::getHomepageSection();
        $sql = 'SELECT s.nid
                FROM section s
                INNER JOIN drufonyHierarchy dh ON s.nid = dh.nid
                WHERE s.type = ? AND dh.parentNid = ?';

        $mainBlogSection = db_executeQuery($sql, array(BLOG_SECTION_TYPE, $homepageNid))->fetch();

        if (empty($mainBlogSection['nid'])) {
            l(ERROR, "Main blog section doesn't defined");
            throw new NotFoundHttpException("Main blog Section doesn't found");
        }

        //Throw exception if any blog has been defined
        $nid = isset($mainBlogSection['nid']) ? $mainBlogSection['nid'] : NULL;

        return $nid;
    }

    /**
     * Returns an array with content of a contentType filtered by type and date
     *
     * @param contentType a content type machine name, eg section
     * @param dateFrom a timestamp with from date
     * @param type an optional subtype
     * @param dateTo an optional timestamp with end interval
     * @return array with nids
     */
    public static function getContentByDate($contentType, $dateFrom, $type=NULL, $dateTo=NULL) {
        $params   = array();
        $params[] = date(DEFAULT_PUBLICATION_DATE_FORMAT, $dateFrom);
        $sql      = 'SELECT DISTINCT(nid) FROM %s WHERE publicationDate >= ?';
        $sql      = sprintf($sql, $contentType);

        if (!empty($type)) {
            $params[]  = $type;
            $sql      .= ' AND type = ?';
        }

        if (!empty($dateTo)) {
            $params[]  = date(DEFAULT_PUBLICATION_DATE_FORMAT, $dateTo);
            $sql      .= ' AND publicationDate <= ?';
        }

        $result = db_executeQuery($sql, $params);
        $list   = array();

        while ($row = $result->fetch()) {
            $list[] = $row['nid'];
        }

        return $list;
    }

    /**
     * Returns an array with content of a contentType filtered by type and date and grouped by
     * months and years. Is useful for widgets in blogs
     *
     * @param contentType a content type machine name, eg section
     * @param dateFrom a timestamp with from date
     * @param type an optional subtype
     * @param dateTo an optional timestamp with end interval
     * @return array keyed by year, month and then nids
     */
    public static function getContentGroupedByDate($contentType, $dateFrom, $type=NULL, $dateTo=NULL) {
        $params = array();

        $params[] = date(DEFAULT_PUBLICATION_DATE_FORMAT, $dateFrom);
        $sql      = 'SELECT DISTINCT(nid), MONTH(publicationDate) as month, YEAR(publicationDate) as year FROM %s WHERE publicationDate >= ?';
        $sql      = sprintf($sql, $contentType);

        if (!empty($type)) {
            $params[]  = $type;
            $sql      .= ' AND type = ?';
        }

        if (!empty($dateTo)) {
            $params[]  = date(DEFAULT_PUBLICATION_DATE_FORMAT, $dateTo);
            $sql      .= ' AND publicationDate <= ?';
        }

        $result = db_executeQuery($sql, $params);
        $list   = array();

        while ($row = $result->fetch()) {
            $list[$row['year']][$row['month']][] = $row['nid'];
        }

        return $list;
    }

    /**
     * Returns contact form
     *
     * @param Controller $action; controller that to retrieve the form
     * @return ContactFormType; form
     */
    public static function getContactForm($action) {
        $contactForm = new ContactFormType();
        $form        = $action->createForm($contactForm, array());

        return $form;
    }

    /**
     * Process contact form and send and email
     *
     * @param Controller $action; controller that want to process the contact form
     * @param string $email; email address to send the email
     * @param Request $POST; request with the form content
     * @return void
     */
    public static function processContactForm($action, $email, $POST) {
        $success     = True;
        $contactForm = new ContactFormType();
        $form        = $action->createForm($contactForm, array());

        $form->handleRequest($POST);

        if($form->isValid()) {
            $formData = $form->getData();

            //TODO: define subject and body for contact form
            $subject    = t("Contact form");
            $template   = 'email-contact-form.html.twig';

            if (!is_null($formData['attachment'])) {
                $fileName   = uniqid() . '.' . $formData['attachment']->guessExtension();
                $attachment = $formData['attachment']->move(FILES_BASE . SUBPATH_CONTACT_ATTACHMENTS, $fileName);
            }

            $attachments  = isset($attachment) ? array($attachment->getPathName()) : array();
            $customParams = array('name' => $formData['name'], 'email' => $formData['email'],
                                'body' => $formData['message'], 'phone' => $formData['phone']);

            Mailing::sendMail($email, $subject, $template, $customParams,
                            DEFAULT_EMAIL_ADDRESS, 'text/html', $attachments);
        }
        else{
            //TODO: what to do if form is not valid
            $success = False;
        }

        return $success;
    }

    /**
     * Retrive all the contents in database and in what languages have been translated
     *
     * @param int $contentType; type of content, if null all contents
     * @param int $page
     * @param int $itemsPerPage
     * @return array with the results
     */
    //FIXME: try to improve using one single query
    public static function getAllTranslationContentStatus($contentType = null, $page = 0, $itemsPerPage = ITEMS_PER_PAGE,
                                                          $orderField = 'publicationDate', $orderCriteria = 'ASC') {
        $contentTypes     = array($contentType);
        $formatedContents = array();
        $languages        = Locale::getAllLanguages();

        unset($languages[DEFAULT_LANGUAGE]);

        if(is_null($contentType)) {
            $contentTypes = self::getAvailableContentTypes();
        }

        $configValues = array(
            'contentTypes' => $contentTypes,
            'page'         => $page,
            'orderFields'  => array($orderField => $orderCriteria),
        );

        $allContents = self::_getAllContents($configValues);

        foreach($allContents as $content) {
            $contentNid  = $content->getNid();
            $contentInfo = array(DEFAULT_LANGUAGE => true, 'title' => $content->getTitle(),
                                 'contentType' => $content->getContentType(), 'nid' => $contentNid);

            foreach($languages as $language => $name) {
                $contentInfo[$language] = false;
            }

            $formatedContents[$contentNid] = $contentInfo;
        }

        foreach($languages as $language => $name) {
            foreach($contentTypes as $type) {
                $allTranslated = self::getTranslatedByLang($type, $language);

                foreach($allTranslated as $translated) {
                    $formatedContents[$translated->getNid()][$language] = true;
                }
            }
        }

        return $formatedContents;
    }

    /**
     * Publishes any content with a scheduled publication date in the past
     *
     * @return void
     */
    public static function publishFutureContent() {
        $types = self::getAvailableContentTypes();
        $rows  = 0;

        foreach ($types as $type) {
            $sql  = "UPDATE $type SET published = 1 WHERE published = 0 AND futurePublicationDate < NOW()";
            $rows = db_executeUpdate($sql);
            l('INFO', "Published $rows of type $type by publish cron");
        }

        return $rows;
    }

    /**
     * Check if a content can be visited by the current user
     *
     * @param User $user; current user connected
     * @param string $contentType
     * @param int $nid
     * @param string $lang
     * @return boolean
     */
    public static function checkAvailableContent($user, $contentType, $nid, $lang) {
        $access  = false;
        $sql     = "SELECT published ";
        $sql    .= "FROM ${contentType} ";
        $sql    .= "WHERE nid = ? AND lang = ? ";

        $result = db_fetchColumn($sql, array($nid, $lang));

        if ($result) {
            $access = true;
        }
        elseif ($result == 0 && !is_null($user)) {
            $user   = new User($user->getUid());
            $roles  = $user->getRoles();
            $access = in_array(ROLE_ADMIN, $roles);
        }

        return $access;
    }

    /**
     * Saves or updates a location in database, depending if it recieves
     * location id or not
     *
     * @param array $locationData
     * @return void
     */
    public static function saveLocation($locationData) {
        if(array_key_exists('id', $locationData)) {
            db_update('locations', $locationData, array('nid' => $locationData['nid']));
        }
        else {
            db_insert('locations', $locationData);
        }
    }

    /**
     * Adds location form fields to from if content is geo localizable
     *
     * @param string $contentType
     * @param FormBuilderInterface $builder
     * @return void
     */
    static public function addLocationFormFields($contentType, $builder) {
        if(in_array($contentType, unserialize(CONTENTS_GEO_POSITION))) {
            $builder->add('latitude', 'hidden')
            ->add('address', 'text', array(
                'max_length' => 255,
                'label' => t('Address'),
                'required' => false,
                'mapped' => false,
                'attr' => array('class' => 'google-map-address'),
            ))
            ->add('longitude', 'hidden')
            ->add('search', 'button', array(
                'label' => t('Search'),
            ));
        }
    }

    static private function _getRssByNodes($channel, $arrayHierarchy) {
        $items = array();

        foreach($arrayHierarchy as $element) {
            if ($element->isPublished()) {
                $publicationDate = new \DateTime($element->getPublicationDate());

                $items[] = array('title'           => $element->getTitle(),
                    'link'            => $element->getUrl(),
                    'description'     => $element->getDescription(),
                    'publicationDate' => $publicationDate->format("D, d M Y H:i:s O"),
                );
            }
        }

        $channel['items'] = $items;

        return $channel;
    }

    static public function getRssBySection($nid, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $section = new Section($nid);
        $nodes = $section->getItems($page, $itemsPerPage);

        $channel = array('title'       => $section->getTitle(),
                         'link'        => $section->getUrl(),
                         //FIXME: set meta-tag description for this content, not implemented yet
                         'description' => $section->getTitle(),
                         );

        return self::_getRssByNodes($channel, $nodes);
    }

    static public function getRssByTid($tid, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $nodes        = Utils::getNodesByTid($tid, 'category');
        $categoryName = Category::getName($tid);

        $channel = array('title'       => $categoryName,
                         //FIXME: set link for this category list
                         'link'        => 'index',
                         'description' => '',
                     );

        return self::_getRssByNodes($channel, $nodes);
    }

    static public function getMainMenu($isLogged) {
        $menuType     = Menu::MENU_ANONYMOUS;
        $registerForm = null;

        // FIXME: $isLogged mustn't be an argument. Use user access method instead.
        if ($isLogged) {
            $menuType = Menu::MENU_REGISTERED;
        }

        return Menu::getMenu(MENU_TYPE_HEADER, $menuType);
    }

}
