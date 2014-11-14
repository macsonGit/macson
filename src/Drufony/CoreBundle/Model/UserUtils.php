<?php

namespace Drufony\CoreBundle\Model;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Config\FileLocator;
use Drufony\CoreBundle\Exception\EmailNotFound;
use Drufony\CoreBundle\Exception\UserNameNotFound;
use Drufony\CoreBundle\Entity\User;


require_once('../lib/fb-sdk/facebook.php');

defined('USER_PICTURE_DEFAULT_WIDTH') or define('USER_PICTURE_DEFAULT_WIDTH', 600);
defined('USER_PICTURE_DEFAULT_HEIGHT') or define('USER_PICTURE_DEFAULT_HEIGHT', 600);
defined('USER_PICTURE_FONT_SIZE') or define('USER_PICTURE_FONT_SIZE', 150);

/**
 * UserUtils
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class UserUtils
{
    const FACEBOOK_OAUTH_URL = 'https://graph.facebook.com/oauth/access_token?';
    const FOLLOWING_PENDING  = 1;
    const FOLLOWING_ACCEPTED = 2;
    const FOLLOWING_REJECTED = 3;
    const USER_REGISTERED    = 1;
    const USER_UNREGISTERED  = 0;

    static private $facebook;

    /**
     * getAll
     *
     * Retrieves all the users in database.
     *
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $users
     */
    static public function getAll($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $queryParams = array();
        $sql = "SELECT uid FROM users";

        $query = db_executeQuery($sql, $queryParams, $page);
        $users = $query->fetchAll();

        return $users;
    }

    /**
     * getUsersByStatus
     *
     * Retrieves all the users in database by status given.
     *
     * @param int $status
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $users
     */
    static private function _getUsersByStatus($status, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $queryParams = array($status);
        $sql = "SELECT uid FROM users WHERE active = ?";

        $query = db_executeQuery($sql, $queryParams, $page, $itemsPerPage);
        $users = $query->fetchAll();

        return $users;
    }

    /**
     * getRegistered
     *
     * Retrieves all the registered users from database.
     *
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $users
     */
    static public function getRegistered($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        return self::_getUsersByStatus(self::USER_REGISTERED, $page, $itemsPerPage);
    }

    /**
     * getUnregistered
     *
     * Retrieves all the unregistered users from database.
     *
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $users
     */
    static public function getUnregistered($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        return self::_getUsersByStatus(self::USER_UNREGISTERED, $page, $itemsPerPage);
    }

    /**
     * getUsersInfo
     *
     * Retrieves the userInfo from all the users in database.
     *
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $users
     */
    static public function getUsersInfo($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $queryParams = array();
        $sql = "SELECT u.*, r.name as role, 'User' AS contentType
                FROM users AS u
                LEFT JOIN users_roles AS ur ON u.uid = ur.uid
                LEFT JOIN role AS r ON ur.rid = r.rid";

        $query = db_executeQuery($sql, $queryParams, $page, $itemsPerPage);
        $users = $query->fetchAll();

        return $users;
    }

    /**
     * getFavorites
     *
     * Retrieves favorited nodes by given user from the user's pool.
     *
     * @param int $uid
     *
     * @return array $favorites
     */
    static public function getFavorites($uid) {
        return Pool::getPool('userPool', 'favorites', $uid);
    }

    /**
     * isFavorited
     *
     * Returns if a content is set as favorite by a user
     *
     * @param int $uid
     * @param int $nid
     * @return bool
     */
    static public function isFavorited($uid, $nid) {
        $item = Pool::getPoolItem('userPool', 'favorites', $uid, $nid);

        return !empty($item);
    }

    /**
     * setFavorite
     *
     * Adds a favorite node for a given user in the user's pool.
     *
     * @param int $uid
     * @param int $nid
     *
     * @return void
     */
    static public function setFavorite($uid, $nid) {
        Pool::addToPool('userPool', 'favorites', $uid, $nid);
    }

    /**
     * removeFavorite
     *
     * Removes a favorite node for a given user from the user's pool.
     *
     * @param int $uid
     * @param int $nid
     *
     * @return void
     */
    static public function removeFavorite($uid, $nid) {
        Pool::removeFromPool('userPool', 'favorites', $uid, $nid);
    }

    /**
     * getBlockedUsers
     *
     * Retrieves all the users which given user has blocked.
     *
     * @param int $uid
     * @param int $page
     * @param int $size
     *
     * @return $users
     */
    static private function getBlockedUsers($uid, $page = 0, $size = ITEMS_PER_PAGE) {
        return Pool::getPool('userPool', 'following', $uid, self::FOLLOWING_REJECTED, $page, $itemsPerPage);
    }

    /**
     * getFollowing
     *
     * Retrieves all the followed users by a given user and status.
     *
     * @param int $uid
     * @param int $status
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $followers
     */
    static public function getFollowing($uid, $status = null, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $followers = array();

        switch ($status) {
            case self::FOLLOWING_REJECTED:
                $followers = self::_getFollowingRejected($uid, $page, $itemsPerPage);
                break;
            case self::FOLLOWING_ACCEPTED:
                $followers = self::_getFollowingAccepted($uid, $page, $itemsPerPage);
                break;
            case self::FOLLOWING_PENDING:
                $followers = self::_getFollowingPending($uid, $page, $itemsPerPage);
                break;
            default:
                $followers = self::_getAllFollowings($uid, $page, $itemsPerPage);
                break;
        }

        return $followers;
    }

    /**
     * _getFollowingPending
     *
     * Retrieves all the pending followed users by a given user.
     *
     * @param int $uid
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $followers
     */
    static private function _getFollowingPending($uid, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        return Pool::getPool('userPool', 'following', $uid, self::FOLLOWING_PENDING, $page, $itemsPerPage);
    }

    /**
     * _getFollowingAccepted
     *
     * Retrieves all the accepted followed users by a given user.
     *
     * @param int $uid
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $followers
     */
    static private function _getFollowingAccepted($uid, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        return Pool::getPool('userPool', 'following', $uid, self::FOLLOWING_ACCEPTED, $page, $itemsPerPage);
    }

    /**
     * _getFollowingRejected
     *
     * Retrieves all the rejected followed users by a given user.
     *
     * @param int $uid
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $followers
     */
    static private function _getFollowingRejected($uid, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        return self::getBlockedUsers($uid, $page, $itemsPerPage);
    }

    /**
     * _getAllFollowings
     *
     * Retrieves all the followed users by a given user.
     *
     * @param int $uid
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $followers
     */
    static private function _getAllFollowings($uid, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        return Pool::getPool('userPool', 'following', $uid, NULL, $page, $itemsPerPage);
    }

    /**
     * addFollowing
     *
     * Adds a following to the user's pool
     *
     * @param int $uid
     * @param int $followedUser
     *
     * @return void
     */
    static public function addFollowing($uid, $followedUser) {
        Pool::addToPool('userPool', 'following', $uid, $followedUser, 0, self::FOLLOWING_PENDING);
    }

    /**
     * removeFollowing
     *
     * Removes a following user from the user's pool.
     *
     * @param int $uid
     * @param int $followedUser
     *
     * @return void
     */
    static public function removeFollowing($uid, $followedUser) {
        Pool::removeFromPool('userPool', 'following', $uid, $followedUser);
    }

    /**
     * isFollowing
     *
     * Returns true if a user is been follewed by another
     *
     * @param int $uid
     * @param int $followedUser
     * @return bool
     */
    static public function isFollowing($uid, $followedUser) {
        $item = Pool::getPoolItem('userPool', 'following', $uid, $followedUser);

        return !empty($item);
    }

    /**
     * acceptFollower
     *
     * Accepts a follower request.
     *
     * @param int $uid
     * @param int $followedUser
     *
     * @return void
     */
    static public function acceptFollower($uid, $followedUser) {
        Pool::updateStatusFromPool('userPool', 'following', $followedUser, $uid, self::FOLLOWING_ACCEPTED);
    }

    /**
     * ignoreFollower
     *
     * Ignores a follower request.
     *
     * @param mixed $uid
     * @param mixed $followedUser
     *
     * @return void
     */
    static public function ignoreFollower($uid, $followedUser) {
        Pool::updateStatusFromPool('userPool', 'following', $followedUser, $uid, self::FOLLOWING_REJECTED);
    }

    /**
     * getUidByEmail
     *
     * Retrieves the user id by given email.
     *
     * @param string $email
     *
     * @return int $uid
     */
    static public function getUidByEmail($email) {
        $uid   = false;

        $sql   = "SELECT uid FROM users WHERE email = ?";
        $query = db_executeQuery($sql, array($email));
        $uid   = $query->fetchColumn();

        if (is_null($uid)) {
            throw new EmailNotFound($email);
        }

        return $uid;
    }

    /**
     * getUidByUsername
     *
     * Retrieves the user id by given userName.
     *
     * @param string $username
     *
     * @return int $uid
     */
    static public function getUidByUsername($username) {
        $uid = false;

        $sql   = "SELECT uid FROM users WHERE username = ?";
        $query = db_executeQuery($sql, array($username));
        $uid   = $query->fetchColumn();

        if (is_null($uid)) {
            throw new UserNameNotFound($username);
        }

        return $uid;
    }

    /**
     * updateLoginDate
     *
     * Updates the loginDate for a given user. Uses current time.
     *
     * @param int $uid
     *
     * @return void
     */
    static public function updateLoginDate($uid) {
        $date   = date(DEFAULT_PUBLICATION_DATE_FORMAT);
        $record = array('loginDate' => $date);

        db_update('users', $record, array('uid' => $uid));
    }

    /**
     * getInstanceFB
     *
     * Retrieves the previous instanced Facebook Session object.
     *
     * @return Facebook
     */
    static public function getInstanceFB() {
        if (is_null(self::$facebook)) {
            $config = array(
                'appId' => FACEBOOK_API_KEY,
                'secret' => FACEBOOK_PRIVATE_KEY,
            );
            $facebook = new \Facebook($config);
            self::$facebook = $facebook;
        }

        return self::$facebook;
    }

    /**
     * getFBUrlForLogin
     *
     * Retrieves loginUrl for Facebook login.
     *
     * @return string login Url
     */
    static public function getFBUrlForLogin() {
        $facebook = self::getInstanceFB();
        $router   = getRouter();

        $loginUrl = $facebook->getLoginUrl(array(
            'scope' => 'email',
            'display' => 'page',
            'redirect_uri' => $router->generate('drufony_login', array('lang' => getLang()), true)
        ));

        return $loginUrl;
    }

    /**
     * getUserDataByFacebook
     *
     * Retrieves the user info by Facebook token given
     *
     * @param string $clientCode
     *
     * @return array $userData
     */
    static public function getUserDataByFacebook($clientCode) {
        $params   = null;
        $userData = array();
        $facebook = self::getInstanceFB();
        $router   = getRouter();

        // Builds query parameters for Facebook login
        $parameters = array(
            'client_id' => FACEBOOK_API_KEY,
            'redirect_uri' => $router->generate('drufony_login', array('lang' => getLang()), true),
            'client_secret' => FACEBOOK_PRIVATE_KEY,
            'code'          => $clientCode,
        );

        $queryUrl = http_build_query($parameters);
        $url      = self::FACEBOOK_OAUTH_URL . $queryUrl;

        // Create curl resource handle
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);

        // Retrieves data from Facebook servers
        // FIXME: Securizar con timeouts
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        $fbResponse = curl_exec($curlHandle);
        curl_close($curlHandle);

        parse_str($fbResponse, $params);

        if (!empty($params['access_token'])) {
            $facebook->setAccessToken($params['access_token']);

            $userData        = $facebook->api('/me', 'GET');
            $mail            = !empty($userData['email']) ? $userData['email'] : $userData['id'] . '@facebook.com';
            $userData['uid'] = self::getUidByEmail($mail);
        }

        return $userData;
    }

    /**
     * getAllRoles
     *
     * @return array $roles
     */
    static public function getAllRoles() {
        $roles  = array();

        $sql   = "SELECT r.name as roleName, r.rid FROM role r";
        $query = db_executeQuery($sql);

        while ($roleData = $query->fetch()) {
            $roles[$roleData['roleName']] = $roleData['rid'];
        }

        return $roles;
    }

    /**
     * getUserRates
     *
     * Retrieves all the rates received by a given user.
     *
     * @param int $uid
     *
     * @return array $rates
     */
    static public function getUserRates($uid) {
        $rates = array();
        $pool  = new UserPool('userRate', $uid);

        $relation = $pool->current();

        do {
            if ($relation) {
                $rates[] = (object)array('value' => $relation->value, 'id' => $relation->id);
            }
        } while ($relation = $pool->next());

        return $rates;
    }

    /**
     * getUserAverageRate
     *
     * Retrieves the average rate received by a given user.
     *
     * @param int $uid
     *
     * @return int $average
     */
    static public function getUserAverageRate($uid) {
        $average = null;
        $rates   = self::getUserRates($uid);

        if (!empty($rates)) {
            $rateSum = 0;

            foreach($rates as $oneRate) {
                $rateSum += $oneRate->value;
            }

            $average = $rateSum / count($rates);
        }

        return $average;
    }

    /**
     * setUserRate
     *
     * Sets a rate for a given user
     *
     * @param int $uidOrigin
     * @param int $uidDestination
     * @param int $value
     *
     * @return void
     */
    static public function setUserRate($uidOrigin, $uidDestination, $value) {
        Pool::addToPool('userPool', 'userRate', $uidDestination, $uidOrigin, $value);
    }

    /**
     * Removes a previuos rate.
     *
     * @param mixed $uidOrigin; The user who rated.
     * @param mixed $uidDestination; The user who received the rate.
     * @return void
     */
    static public function removeUserRate($uidOrigin, $uidDestination) {
        Pool::removeFromPool('userPool', 'favorites', $uidDestination, $uidOrigin);
    }

    /**
     * getContentRateByUid
     *
     * Retrieves the rate given a user id and nid
     *
     * @param int $nid
     * @param int $uid
     * @return int
     */
    static public function getContentRateByUid($nid, $uid = NULL) {
        $results = 0;
        if ($uid != NULL) {
            $poolItem = Pool::getPoolItem('nodePool', 'nodeRate', $nid, $uid);
            if (!empty($poolItem)) {
                $results = $poolItem->value;
            }
        }

        return $results;
    }

    /**
     * getContentRates
     *
     * Retrieves all the rates received by a given content.
     *
     * @param int $nid
     * @return array $rates
     */
    static public function getContentRates($nid) {
        $pool  = new NodePool('nodeRate', $nid);
        $rates = array();

        $relation = $pool->current();

        do {
            if ($relation) {
                $rates[] = (object) array('value' => $relation->value, 'id' => $relation->id);
            }
        } while ($relation = $pool->next());

        return $rates;
    }

    /**
     * getContentAverageRate
     *
     * Retrieves the average rate by a given content.
     *
     * @param int $nid
     *
     * @return int $average
     */
    static public function getContentAverageRate($nid) {
        $rates   = self::getContentRates($nid);
        $average = null;

        if (!empty($rates)) {
            $rateSum = 0;

            foreach ($rates as $oneRate) {
                $rateSum += $oneRate->value;
            }

            $average = $rateSum / count($rates);
        }

        return $average;
    }

    /**
     * setContentRate
     *
     * Sets a rate for a given content.
     *
     * @param int $nid
     * @param int $uid
     * @param int $value
     *
     * @return void
     */
    static public function setContentRate($nid, $uid, $value) {
        Pool::addToPool('nodePool', 'nodeRate', $nid, $uid, $value);
    }

    /**
     * removeContentRate
     *
     * Removes a previous rate.
     *
     * @param int $nid
     * @param int $uid
     *
     * @return bool $removed
     */
    static public function removeContentRate($nid, $uid) {
        Pool::removeFromPool('nodePool', 'nodeRate', $nid, $uid);
    }

    /**
     * getMostPopular
     *
     * Retrieves the most popular contents in database.
     *
     * @param string $contentType
     * @param string $lang
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $contentData
     */
    static public function getMostPopular($contentType = null, $lang = DEFAULT_LANG, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $params = array($lang);
        $sql = "SELECT np.nid, avg(np.value) AS grade
                FROM node_pools AS np, node AS n
                WHERE np.nid = n.nid AND n.language = ? AND np.type = 'nodeRate'";

        if (!empty($contentType)) {
            $sql .= " AND n.type = ?";
            $params[] = $contentType;
        }

        $sql .= " GROUP BY nid ORDER BY grade DESC";

        $contentData = db_executeQuery($sql, $params, $page, $itemsPerPage);

        return $contentData->fetchAll();
    }

    /**
     * getLatest
     *
     * Retrieves latest registered users
     *
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return array $users
     */
    static public function getLatest($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $params  = array();
        $sql     = "SELECT uid, creationDate FROM users ORDER BY creationDate DESC";
        $results = db_executeQuery($sql, $params, $page, $itemsPerPage);

        $users   = $results->fetchAll();

        return $users;
    }

    /**
     * Generate an image with the 2 firsts initials of a name
     *
     * @param string $name
     * @param int $width
     * @param int $height
     *
     * @return File
     */
    static public function generateUserProfilePicture($name, $width = USER_PICTURE_DEFAULT_WIDTH, $height = USER_PICTURE_DEFAULT_HEIGHT) {

        $inicials = '';
        $words    = explode(' ', $name);

        $currentWord = 0;
        while (($currentWord <= count($words) - 1) && (strlen($inicials) < 2)) {
            $inicials .= strtoupper(substr($words[$currentWord], 0, 1));
            $currentWord++;
        }

        $configDirectories = __DIR__ . '/../Resources/public/fonts';
        $locator           = new FileLocator($configDirectories);
        $font              = $locator->locate('arial.ttf');

        $image    = ImageCreate($width, $height);
        $bg       = ImageColorAllocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
        $white    = ImageColorAllocate($image, 255, 255, 255);

        imagefill($image, 0, 0, $bg);

        $arraySize = imagettfbbox(USER_PICTURE_FONT_SIZE, 0, $font, $inicials);

        $textWidth  = $arraySize[0] + $arraySize[2];
        $textHeight = $arraySize[1] + $arraySize[7];

        $positionCenter = ceil(($width - $textWidth) / 2);
        $positionMiddle = ceil(($height - $textHeight) / 2);

        imagettftext($image, USER_PICTURE_FONT_SIZE, 0, $positionCenter, $positionMiddle, $white, $font, $inicials);

        $imgTempPath = '/tmp/generated_profile' . uniqid() . '.jpg';
        imagejpeg($image, $imgTempPath);

        $profileImage = new File($imgTempPath, true);

        return $profileImage;
    }

    /**
     * saveProfile
     *
     * Saves an User Profile into database.
     *
     * @param Profile $profile
     *
     * @return void
     */
    static public function saveProfile($profile) {
        $profileData = $profile->__toArray();
        //$profileData = $profile;
        unset($profileData['addresses']);

        if (isset($profileData['picture'])) {
            unset($profileData['picture']);
        }

        if (isset($profileData['backgroundPicture'])) {
                unset($profileData['backgroundPicture']);
        }

        $uid    = $profileData['uid'];
        $params = array($uid);

        $sql    = "SELECT uid FROM profile WHERE uid = ?";
        $result = db_executeQuery($sql, $params);

        $exists = $result->fetch();

        if($exists) {
            db_update('profile', $profileData, array('uid' => $profileData['uid']));
        }
        else {
            db_insert('profile', $profileData);
        }
    }

    /**
     * saveAddress
     *
     * Saves an address into database.
     *
     * @param array $addressData
     *
     * @return void
     */
    static public function saveAddress($addressData) {
        if(!empty($addressData['id'])) {
            db_update('addresses', $addressData, array('id' => $addressData['id']));
        }
        else {
            db_insert('addresses', $addressData);
        }
    }

    /**
     * removeAddress
     *
     * Removes a defined address from database.
     *
     * @param int $id
     *
     * @return void
     */
    static public function removeAddress($id) {
        db_delete('addresses', array('id' => $id));
    }

    /**
     * deleteUser
     *
     * Removes the user account identified by uid.
     *
     * @param int $uid
     *
     * @return void
     */



    static public function createUser($userInfo) {


       $user =  array(
		'username'=> $userInfo['firstname']+' '+$userInfo['lastname'],
		'password'=> $userInfo['passwd'],		
		'email'=> $userInfo['email'],		
		'username'=> $userInfo['email'],		
		'active'=> 1,
		'roles'=>array('ROLE_USER')			
	);

       $sqlUser = 'SELECT uid FROM users WHERE email=?';

       $query = db_fetchColumn($sqlUser,array($userInfo['email']));
       if(!is_null($query)){
		$user['uid']=$query;
       }

 
       $id = User::save($user);	


       //$user->setSalt(md5(time()));
       return $id;
       

   }


    static public function deleteUser($uid) {

        // Removes all related addresses
        $profile = new Profile($uid);
        $addresses = $profile->getAddresses();
        foreach ($addresses as $address) {
            self::removeAddress($address['id']);
        }

        // Removes User Profile
        db_delete('profile', array('uid' => $uid));

        //Delete Removes the user
        db_delete('users', array('uid' => $uid));
    }

    /**
     * addForgotToken
     *
     * Creates a new forgotPassword tokeni record in database for a given user.
     *
     * @param string $token
     * @param int $uid
     *
     * @return void
     */
    static public function addForgotToken($token, $uid) {
        $record = array(
            'uid'   => $uid,
            'token' => $token,
            'used'  => 0,
        );

        db_insert('forgot_pass_token', $record);
    }

    /**
     * isForgotTokenUsed
     *
     * Checks for an used/unused oneTimeLink token.
     *
     * @param string $token
     * @param int $uid
     *
     * @return bool $isUsed
     */
    static public function isForgotTokenUsed($token, $uid) {
        $sql     = "SELECT used FROM forgot_pass_token WHERE token = ? AND uid = ?";
        $results = db_executeQuery($sql, array($token, $uid));

        $isUsed  = (bool) $results->fetchColumn();

        return $isUsed;
    }

    /**
     * markAsUsedForgotToken
     *
     * Marks given token as used.
     *
     * @param string $token
     * @param int $uid
     *
     * @return void
     */
    static public function markAsUsedForgotToken($token, $uid) {
        db_update('forgot_pass_token', array('used' => 1), array('token' => $token, 'uid' => $uid));
    }
}
