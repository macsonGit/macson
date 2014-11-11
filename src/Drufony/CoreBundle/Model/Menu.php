<?php

namespace Drufony\CoreBundle\Model;

define('MENU_LINK_FILTERS', serialize(array('%username%')));

/**
 * Static class, realize needed operations in Menu
 */
class Menu
{
    const MENU_ANONYMOUS  = 0x01;
    const MENU_REGISTERED = 0x02;
    const MENU_EVERYBODY  = 0x04;

    /**
     * Returns all menus in database
     *
     * @return array
     */
    public static function getAll() {
        $sql  = 'SELECT itemId, title, type, url, parentId, lang, userTarget ';
        $sql .= 'FROM menu ';
        //$sql .= 'WHERE parentId = 0';

        $result = db_executeQuery($sql);

        return $result->fetchAll();
    }

    /**
     * Save or update a menu
     *
     * @param array $menuData
     * @return int; itemId
     */
    public static function save($menuData) {
        $insertData = array('title'      => $menuData['title'],
                            'type'       => $menuData['type'],
                            'url'        => $menuData['url'],
                            'parentId'   => $menuData['parentId'],
                            'lang'       => $menuData['lang'],
                            'linkText'   => $menuData['linkText'],
                            'weight'     => $menuData['weight'],
                            'userTarget' => $menuData['userTarget']);
        if(!array_key_exists('itemId', $menuData)) {
            $menuId = db_insert('menu', $insertData);
        }
        else {
            $updateCriteria = array('itemId' => $menuData['itemId']);
            db_update('menu', $insertData, $updateCriteria);
            $menuId = $menuData['itemId'];
        }
        return $menuId;
    }

    /**
     * Return a single menu from database
     *
     * @param int $itemId
     * @return array
     */
    public static function get($itemId) {
        $sql  = 'SELECT itemId, title, type, url, parentId, lang, userTarget, linkText, weight ';
        $sql .= 'FROM menu ';
        $sql .= 'WHERE itemId = ?';

        $result = db_fetchAssoc($sql, array($itemId));

        return $result;
    }

    /**
     * Retrieves the children giving a item id.
     *
     * @param int $itemId; item id to get children
     *
     * @return array; the children retrieved
     */
    public static function getChildren($itemId) {
        $children = array();

        $sql = 'SELECT * FROM menu WHERE parentId = ? ORDER BY weight';

        $menusToProcess = array();
        $menusToProcess[] = array('itemId' => $itemId);
        do {
            $currentMenu = array_shift($menusToProcess);

            $childrenRetrieved = db_fetchAll($sql, array($currentMenu['itemId']));
            if (count($childrenRetrieved) > 0 ) {
                $children[$currentMenu['itemId']] = $childrenRetrieved;
            }

            $menusToProcess = array_merge($menusToProcess, $childrenRetrieved);

        } while (count($menusToProcess) > 0);

        return $children;
    }

    /**
     * Retrieves all menu of a giving type
     *
     * @param int $menuType
     *
     * @return array; main parents and children
     */
    public static function getMenu($menuType, $userTarget = null) {
        $queryParams = array($menuType);

        $sql  = 'SELECT * FROM menu WHERE type = ? AND parentId = 0 ';
        if (!is_null($userTarget)) {
            $sql .= 'AND userTarget = ? ';
            $queryParams[] = $userTarget;
        }
        $sql .= 'ORDER BY weight';

        $parents = db_fetchAll($sql, $queryParams);

        $children = array();

        $queryParams = array($menuType);
        $sql  = 'SELECT * FROM menu WHERE type = ? AND parentId != 0 ';
        if (!is_null($userTarget)) {
            $sql .= 'AND userTarget = ? ';
            $queryParams[] = $userTarget;
        }
        $sql .= 'ORDER BY weight';

        $childQuery = db_executeQuery($sql, $queryParams);
        while($row = $childQuery->fetch()) {
            $children[$row['parentId']][] = $row;
        }

        $menu = new \stdClass();
        $menu->parents = $parents;
        $menu->children = $children;

        return $menu;
    }

    /**
     * Removes a menu and all its children from database
     *
     * @param int $itemId
     */
    public static function delete($itemId) {
        $sql  = 'SELECT itemId, parentId FROM menu WHERE parentId = ?';

        $childrenToProcess = array();
        $childrenToProcess[] = array('itemId' => $itemId);
        do {
            $currentChildrenToRemove = array_shift($childrenToProcess);

            $childrenRetrieved = db_fetchAll($sql, array($currentChildrenToRemove['itemId']));
            $childrenToProcess = array_merge($childrenToProcess, $childrenRetrieved);

            $deleteCriteria = array('itemId' => $currentChildrenToRemove['itemId']);
            $result = db_delete('menu', $deleteCriteria);

        } while (count($childrenToProcess) > 0);

        return $result;
    }

    /**
     * Returns menus available for forms
     *
     * @param integer $excludeId
     * @return array
     */
    public static function getMenusForForm($excludeId = null) {
        $sql  = 'SELECT itemId, title ';
        $sql .= 'FROM menu';

        $menus = array('0' => 'None');
        $result = db_executeQuery($sql);

        while ($row = $result->fetch()) {
            if($excludeId != $row['itemId']) {
                $menus[$row['itemId']] = t($row['title']);
            }
        }

        return $menus;
    }

    /**
     * Indicate if an url is valid or not
     *
     * @param string $url
     * @param string $baseUrl
     * @return array
     */
    public static function validateUrl($url, $baseUrl) {
        $urlToStore = '';
        $urlToCheck = '';

        //TODO: improve regex if possible
        //Check absolute url
        $absoluteUrlRegex= "/^(https?\:\/\/)?([a-z0-9]+\.)?([a-z0-9\-]{3,}\.)*[a-z]{2,3}(\/[a-z0-9\-]+\/?)*$/i";

        ////Check relative url
        $relativeUrlRegex= "/^\/?([a-z0-9\-]*\/?)+$/";

        //Checks if is an absolute url
        if(preg_match($absoluteUrlRegex, $url)) {
            //Protects characters in url
            $baseUrlProtected = preg_replace('/\//', '\/', $baseUrl);

            $regexHttp = "/^http:\/\//";
            $regexBaseUrl = "/^$baseUrlProtected/";

            $BaseUrlNotHttp = preg_replace($regexHttp, '', $baseUrl);

            $regexBaseUrlNotHttp = "/^$BaseUrlNotHttp/";

            //Check if it's and intern url
            if(preg_match($regexBaseUrl, $url)) {
                $urlToStore = preg_replace($regexBaseUrl, '', $url);
                $urlToStore = preg_replace($regexHttp, '', $urlToStore);
            }
            else if(preg_match($regexBaseUrlNotHttp, $url)) {
                $urlToStore = preg_replace($regexBaseUrlNotHttp, '', $url);
            }
            //Check extern url
            else if(!preg_match($regexHttp, $url)) {
                $urlToStore = 'http://' . $url;
                $urlToCheck = $urlToStore;
            }
            else {
                $urlToStore = $url;
                $urlToCheck = $urlToStore;
            }
        }
        //Check relative url
        else if(preg_match($relativeUrlRegex, $url)) {
            $urlToStore = $url[0] != '/' ? $urlToStore = '/' . $url : $url;
        }

        //Check intern url
        return array($urlToStore, $urlToCheck);
    }

    /**
     * Return true if the givin url exists, false otherwise
     *
     * @param string $url
     * @return boolean
     */
    public static function urlExists($url) {
        $exists = true;
        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle ,CURLOPT_TIMEOUT, 2);

        $response = curl_exec($handle);

        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if($httpCode == 404 || $httpCode == 0) {
            $exists = false;
        }

        curl_close($handle);
        return $exists;
    }

    /**
     * Returns menu target types
     *
     * @return array
     */
    public static function getMenuTarget() {
        $menuTypes = array(self::MENU_ANONYMOUS => t('Anonymous'),
                           self::MENU_REGISTERED => t('Registered'),
                           self::MENU_EVERYBODY => t('Everybody'));

        return $menuTypes;
    }

    /**
     * Retrieves the linkText giving a string between %%, if not found returns
     * the string without %
     *
     * @param string $originalLinkText
     *
     * @return string
     */
    public static function getLinkText($originalLinkText) {
        $resultLinkText = '';

        if (preg_match('/^%.*%$/', $originalLinkText) && in_array($originalLinkText, Menu::getLinkFilters())) {
            $resultLinkText = Menu::_getLinkTextFiltered($originalLinkText);
        }
        else {
            $resultLinkText = $originalLinkText;
        }

        return $resultLinkText;
    }

    /**
     * Retrieves menu link filters
     *
     * @return array
     */
    public static function getLinkFilters() {
        return unserialize(MENU_LINK_FILTERS);
    }

    /**
     * Calls the filter function
     *
     * @param string $text
     *
     * @return string
     */
    private static function _getLinkTextFiltered($text) {
        $functionName = '_get' . ucfirst(strtolower(str_replace('%', '', $text)));
        $result = '';

        if (method_exists(get_class(), $functionName)) {
            $result = call_user_func('self::' . $functionName);
        }
        else {
            $result = str_replace('%', '', $text);
        }

        return $result;
    }

    /**
     * Filter function for link texts as %username%
     *
     * @return array
     */
    private static function _getUsername() {
        $username = '';
        $user = getCurrentuser();
        if(!is_null($user)) {
            $username = $user->getUsername();
        }
        return $username;
    }
}
