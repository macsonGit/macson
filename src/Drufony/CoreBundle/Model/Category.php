<?php
/**
 * It defines the Category layer, which will be used to categorize contents
 * in the system. It allows create, change and remove categories in database,
 * also sets relations between terms and vocabularies.
 *
 * This is a static class.
 */

namespace Drufony\CoreBundle\Model;

defined('CATEGORY_TABLE') or define('CATEGORY_TABLE', 'category');

/**
 * Allows create, change and remove categories, and set the relations of terms
 * with themself and vocabularies.
 */
class Category {

    const CATEGORY_TYPE = 'category';

    /**
     * Category constructor. Empty method. Makes nothing.
     *
     * @return void
     */
    private function __construct() {
    }

    /**
     * Retrieves the vocabulary vid in database.
     *
     * @param string $vocabulary vocabulary name to search
     *
     * @return int; vocabulary vid found, 0 if it does not exist
     */
    static private function _getVocabularyId($vocabulary) {
        $vid = 0;

        $sql = 'SELECT vid FROM vocabulary WHERE name = ?';

        if ($result = db_fetchColumn($sql, array($vocabulary))) {
            $vid = $result;
        }

        return $vid;
    }

    /**
     * Creates a new vocabulary in database.
     *
     * @param string $vocabulary; vocabulary name to create
     *
     * @return int; vocabulary id inserted
     */
    static public function createVocabulary($vocabulary, $vid = null) {
        $insertData = array('name' => $vocabulary);

        if (!is_null($vid)) {
            $updateCriteria = array('vid' => $vid);
            db_update('vocabulary', $insertData, $updateCriteria);
        }
        else {
            $vid = db_insert('vocabulary', $insertData);
        }

        return $vid;
    }

    /**
     * Retrieves a vocabulary that a category belongs to
     *
     * @param int $tid
     *
     * @return array
     */
    static public function getVocabularyByCategory($tid) {
        $sql  = 'SELECT vocabulary.* FROM vocabulary ';
        $sql .= 'INNER JOIN category ON category.vid = vocabulary.vid ';
        $sql .= 'WHERE category.tid = ?';

        $vocabulary = db_fetchAssoc($sql, array($tid));

        return $vocabulary;
    }

    /**
     * Retrieves a category name giving an id.
     *
     * @param int $tid; category id
     *
     * @return string; name retrieved for the category or empty string if does not exist
     */
    static public function getName($tid) {
        $name = '';

        $sql = 'SELECT name FROM category WHERE tid = ?';

        if ($result = db_fetchColumn($sql, array($tid))) {
            $name = $result;
        }

        return $name;
    }

    /**
     * Retrieves data of a single category from db
     *
     * @param int $tid
     *
     * i@return array
     */
    static public function getCategoryData($tid) {
        $sql = 'SELECT * FROM category WHERE tid = ?';

        $result = db_fetchAssoc($sql, array($tid));

        return $result;
    }

    /**
     * Retrieves the children giving a category id.
     *
     * @param int $vid; category id to get children
     * @param int $depth; The level of children we want to retrieve
     *
     * @return array; the children retrieved
     */
    static public function getChildren($tid, $depth = 0) {
        $children = array();

        $sql  = 'SELECT tid, vid, name, parentId ';
        $sql .= 'FROM category ';
        $sql .= 'WHERE parentId = ?';

        $currentLevel = 0;

        $childrenToProcess = array();

        $childrenList      = db_fetchAll($sql, array($tid), false);
        $childrenToProcess = $childrenList;

        $currentLevel++;
        while ($currentLevel < $depth and count($childrenToProcess) > 0) {
            $currentChildrenToFind = array_shift($childrenToProcess);

            $childrenRetrieved = db_fetchAll($sql, array($currentChildrenToFind['tid']), false);
            $childrenToProcess = array_merge($childrenToProcess, $childrenRetrieved);

            $childrenList = array_merge($childrenList, $childrenRetrieved);
            $currentLevel++;
        }

        return $childrenList;
    }

    /**
     * Retrives all the categories by vocabulary given.
     *
     * @param string $vocabulary; vocabulary name to get categories
     *
     * @return array; categories found
     */
    static public function getChildrenByVocabulary($vocabulary) {
        $children = array();
        $vid      = self::_getVocabularyId($vocabulary);

        $sql  = 'SELECT name FROM category WHERE vid = ?';

        $result = db_executeQuery($sql, array($vid));
        $children = $result->fetchAll();

        return $children;
    }

    /**
     * Retrieves category hierarchy of a vocabulary
     *
     * @param int $vid
     *
     * @return array; category parents and category children
     */
    public static function getCategoryHierarchyByVocabulary($vid) {
        $sql = 'SELECT * FROM category WHERE vid = ? AND parentId = 0 ORDER BY weight';

        $parents = db_fetchAll($sql, array($vid));

        $children = array();
        $sql = 'SELECT * FROM category WHERE vid = ? AND parentId != 0 ORDER BY weight';

        $childQuery = db_executeQuery($sql, array($vid));
        while($row = $childQuery->fetch()) {
            $children[$row['parentId']][] = $row;
        }

        return array($parents, $children);
    }

    /**
     * Giving a list of parents and children, returns the categories formated
     *
     * @param array $parents
     * @param array $children
     *
     * @return array
     */
    public static function getFormatedCategory($parents, $children) {

        $result = array();
        foreach($parents as $parent) {
            $result[$parent['tid']] = $parent['name'];
            $parentId = $parent['tid'];

            if(!empty($children[$parentId])) {
                $parentStack = array();

                $option = array_shift($children[$parentId]);
                while (count($parentStack) > 0 || $option) {

                    if (!$option && (count($parentStack) > 0)) {
                        //Close html if parents stored
                        $parentId = array_pop($parentStack);
                    }
                    else if (!empty($children[$option['tid']])) {
                        //Open html for element with children
                        $levelMark = str_repeat('--', count($parentStack) + 1);
                        $result[$option['tid']] = $levelMark . $option['name'];
                        array_push($parentStack, $parentId);
                        $parentId = $option['tid'];
                    }
                    else {
                        //Element with no children
                        $levelMark = str_repeat('--', count($parentStack) + 1);
                        $result[$option['tid']] = $levelMark . $option['name'];
                    }

                    $option = array_shift($children[$parentId]);
                }
            }
        }

        return $result;
    }

    /**
     * Retrieves all the parents by category given.
     *
     * @param int $tid; category id to get parents
     *
     * @return array; parents found
     */
    static public function getParents($tid) {
        $parents = array();

        $sql  = 'SELECT tid, vid, name, parentId ';
        $sql .= 'FROM category ';
        $sql .= 'WHERE tid = ?';

        // Not really a parent, the category itself
        $currentParent = db_fetchAssoc($sql, array($tid));

        while ($currentParent['parentId'] != '0') {
            $currentParent = db_fetchAssoc($sql, array($currentParent['parentId']));

            // Store current parent
            $parents[] = $currentParent;
        }

        return $parents;
    }

    /**
     * Removes a category and sets its old children parentId to 0
     *
     * @param int $tid; category id to delete
     *
     * @return boolean; true if success
     */
    static public function remove($tid) {
        $updateCriteria = array('parentId' => $tid);
        $updateData     = array('parentId' => 0);
        db_update('category', $updateData, $updateCriteria);

        $deleteCriteria = array('tid' => $tid);
        $result         = db_delete('category', $deleteCriteria);

        return $result;
    }

    /**
     * Removes a category and all its children by category id given.
     *
     * @param int $tid; category id
     *
     * @return boolean; true if success
     */
    static public function removeAll($tid) {
        $sql  = 'SELECT tid, vid, name, parentId ';
        $sql .= 'FROM category ';
        $sql .= 'WHERE parentId = ?';

        $deleteCriteria = array('tid' => $tid);
        $result         = db_delete('category', $deleteCriteria);

        $childrenList   = db_fetchAll($sql, array($tid), false);

        while (count($childrenList) > 0) {
            $currentChildrenToRemove = array_shift($childrenList);

            $childrenRetrieved = db_fetchAll($sql, array($currentChildrenToRemove['tid']), false);
            $childrenList = array_merge($childrenList, $childrenRetrieved);

            $deleteCriteria = array('tid' => $currentChildrenToRemove['tid']);
            $result = db_delete('category', $deleteCriteria);
        }

        return $result;
    }

    /**
     * Removes all the categories of a vocabulary.
     *
     * @param string $vocabulary; vocabulary name
     *
     * @return boolean; true if success
     */
    static public function removeAllByVocabulary($vocabulary) {
        $vid      = self::_getVocabularyId($vocabulary);
        $criteria = array('vid' => $vid);

        $result = db_delete('category', $criteria);

        $result = db_delete('vocabulary', $criteria);

        return $result;
    }

    /**
     * Retrieves a category id
     *
     * @param string $name; name of the category
     * @param int $vid; vocabulary id
     * @param string $parent; category nparent id
     *
     * @return int; category
     */
    static private function _getCategoryByName($name, $vid, $parent) {
        $tid = 0;

        $sql  = 'SELECT tid FROM category ';
        $sql .= 'WHERE name = ? AND vid = ? AND parentId = ?';

        if ($result = db_fetchColumn($sql, array($name, $vid, $parent))) {
            $tid = $result;
        }

        return $tid;
    }

    /**
     * Inserts or updates a category
     *
     * @param string $name; category name to create
     * @param string $vocabulary; vocabylary name that category belongs to
     * @param int $parent; parent id for that categor
     * @param int $tid; category id to update with new data
     *
     * @return int; category id inserted or updated
     */
    static public function save($name, $vocabulary, $parent = 0, $tid = null) {
        $resultTid = null;

        $vid = self::_getVocabularyId($vocabulary);

        // Checks parentVocabulary
        if ($parent) {
            $sql = 'SELECT vid FROM category WHERE tid = ?';

            $parentVocabulary = db_fetchColumn($sql, array($parent));

            if ($parentVocabulary != $vid) {
                throw new \Exception('parentVocabulary != vocabulary');
            }
        }

        if (!$vid) {
            $vid = self::createVocabulary($vocabulary);
        }

        // Insert
        if (is_null($tid)) {

            $resultTid = self::_getCategoryByName($name, $vid, $parent);

            // If that category already exist for same vocabulary and parent, don't save
            if (!$resultTid) {
                $insertData = array('name' => $name, 'vid' => $vid, 'parentId' => $parent);
                $resultTid  = db_insert('category', $insertData);
            }
        }
        // Update
        else {

            $updateCriteria = array('tid' => $tid);
            $updateData     = array('name' => $name, 'vid' => $vid, 'parentId' => $parent);

            db_update('category', $updateData, $updateCriteria);

            $resultTid = $tid;
        }

        return $resultTid;
    }

    /**
     * Retrieves all the existing vocabularies.
     *
     * @return array; all the existing vocabularies
     */
    static public function getVocabularies($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $vocabularies = array();

        $sql  = "SELECT vid, name, 'vocabulary' as contentType ";
        $sql .= "FROM vocabulary ";

        $result = ($page) ? db_executeQuery($sql, array(), $page, $itemsPerPage) : db_executeQuery($sql);

        if ($result) {
            $vocabularies = $result->fetchAll();
        }

        return $vocabularies;
    }

    /**
     * Retrieves all the existing vocabularies.
     *
     * @return array; all the existing vocabularies
     */
    static public function getVocabulariesCount() {
        $sql  = "SELECT COUNT(*) FROM vocabulary";

        $count = db_fetchColumn($sql);

        return $count;
    }

    /**
     * Retrieves vocabulary name
     *
     * @param int $vid
     *
     * @return void
     */
    static public function getVocabularyName($vid) {
        $sql = 'SELECT name FROM vocabulary WHERE vid = ?';

        $name = db_fetchColumn($sql, array($vid));

        return $name;
    }

    /* USERS CATEGORIES */

    /**
     * Gets the users' categories.
     *
     * @param int $uid; The id of the user.
     *
     * @return array
     */
    static public function getUserCategories($uid) {
        return Pool::getPool('userPool', self::CATEGORY_TYPE, $uid);
    }

    /**
     * Sets a category for the user.
     *
     * @param int $uid; The id of the user.
     * @param int $tid; The id of the category
     */
    static public function setUserCategory($uid, $tid) {
        Pool::addToPool('userPool', self::CATEGORY_TYPE, $uid, $tid);
    }

    /**
     * Deletes a category from a user's category pool.
     *
     * @param int $uid; The id of the user.
     * @param int $tid; The id of the category.
     */
    static public function removeUserCategory($uid, $tid) {
        Pool::removeFromPool('userPool', self::CATEGORY_TYPE, $uid, $tid);
    }

    /* NODES CATEGORIES */

    /**
     * Gets the nodes' categories.
     *
     * @param int $nid; The id of the node.
     *
     * @return array
     */
    static public function getNodeCategories($nid) {
        return Pool::getPool('nodePool', self::CATEGORY_TYPE, $nid);
    }

    /**
     * Get all the node categories by type given. It's useful for big data management.
     *
     * @param type of categories
     *
     * @return array, keyed by nid and name as value
     */
    static public function getAllNodeCategories() {
        $sql  = 'SELECT nid, name FROM node_pools np ';
        $sql .= 'INNER JOIN category c ON objectId = tid ';
        $sql .= 'WHERE np.type = ?';

        $categories = array();
        $results    = db_executeQuery($sql, array(self::CATEGORY_TYPE));

        while ($row = $results->fetch()) {
            $categories[$row['nid']][] = $row['name'];
        }

        return $categories;
    }

    /**
     * Retrieves all the vocabularies associated to each node in database.
     *
     * @return array
     *
     * FIXME: ¿Realmente sirve de algo este método? Tiene toda la pinta de tener bugs
     * de lógica no contemplada al definirlo.
     */
    static public function getAllNodeVocabulary() {
        $sql  = 'SELECT np.nid, v.name FROM node_pools np ';
        $sql .= 'INNER JOIN category c ON objectId = tid ';
        $sql .= 'INNER JOIN vocabulary v ON v.vid = c.vid';

        $categories = array();
        $results    = db_executeQuery($sql);

        while ($row = $results->fetch()) {
            $categories[$row['nid']][] = $row['name'];
        }

        return $categories;
    }

    /**
     * Sets a category for the node.
     *
     * @param int $nid; The id of the node.
     * @param int $tid; The id of the category
     */
    static public function setNodeCategory($nid, $tid) {
        Pool::addToPool('nodePool', 'category', $nid, $tid);
    }

    /**
     * Deletes a category from a node's category pool.
     *
     * @param int $nid; The id of the node.
     * @param int $tid; The id of the category.
     */
    static public function removeNodeCategory($nid, $tid) {
        Pool::removeFromPool('nodePool', 'category', $nid, $tid);
    }
}
