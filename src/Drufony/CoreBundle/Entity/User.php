<?php
/**
 * It defines the User Entity, which will be used to manage users in the system.
 * The site permissions will be associated to roles, so you need to
 * associate users to one or several roles to granted them some permissions.
 *
 * It includes static methods for handling users without an instanced object.
 */

namespace Drufony\CoreBundle\Entity;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\DBAL\DriverManager;
use Drufony\CoreBundle\Model\Profile;
use Drufony\CoreBundle\Model\Mailing;
use Doctrine\ORM\Mapping as ORM;
use Drufony\CoreBundle\Exception\UserNotSaved;
use Drufony\CoreBundle\Exception\EmailNotFound;
use Drufony\CoreBundle\Model\Drupal;
use Drufony\CoreBundle\Model\UserUtils;

defined('DEFAULT_LANGUAGE') or define('DEFAULT_LANGUAGE','en');
defined('USER_DEFAULT_TIME_ZONE') or define('USER_DEFAULT_TIME_ZONE','Europe/Madrid');

/**
 * Implements Drufony user management system.
 *
 * @uses UserInterface
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 *
 * @ORM\Entity
 * @ORM\Table(name="users")
 *
 */
class User implements UserInterface, \Serializable
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_USER  = 'ROLE_USER';
    const ROLE_FOR_NEW_USERS = 'ROLE_USER';

    /**
     * Identifies the user as unique in database.
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     *
     * @var int
     */
    private $uid;

    /**
     * Identifies a user using a machinename
     *
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $username;

    /**
     * Identifies which email owns the user.
     *
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $email;

    /**
     * Logs latest login date in CMS.
     *
     * @ORM\Column(type="datetime")
     *
     * @var datetime
     */
    private $loginDate;

    /**
     * Identifies which is the favorite navigation language for the user.
     *
     * @ORM\Column(name="lang", type="string", length=12)
     *
     * @var string
     */
    private $language = DEFAULT_LANGUAGE;

    /**
     * Logs the creation date just after the registration.
     *
     * @ORM\Column(type="datetime")
     *
     * @var datetime
     */
    private $creationDate;

    /**
     * Identifies if the user is active or not.
     *
     * @ORM\Column(type="integer", length=1)
     *
     * @var bool
     */
    private $active = 0;

    /**
     * Stores a user passsword hash.
     *
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $password;

    /**
     * salt used to adds entrophy to the hashed password.
     *
     * @var string
     */
    private $salt;

    /**
     * Hashed password stored in database. It's generated using crypt algorithms adding some entrophy.
     *
     * @var string
     */
    private $passwordHash;

    /**
     * Stores the permissions for this user.
     *
     * @var array
     */
    private $accesses = array();

    /**
     * Stores the user roles.
     *
     * @ORM\ManyToMany(targetEntity="Role")
     * @ORM\JoinTable(name="users_roles",
     *     joinColumns={@ORM\JoinColumn(name="uid", referencedColumnName="uid")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="rid", referencedColumnName="rid")}
     * )
     *
     * @var array
     */
    private $roles;

    /**
     * Stores the profile associated to the user. It's only loaded on the first call to getProfile().
     *
     * @var Profile
     */
    private $profile;
    

    private $newsletter;

    /**
     * User constructor. Retrieves the user object by uid, or an empty user object instead.
     *
     * @param int $uid
     *
     * @return void
     */
    public function __construct($uid = null) {
        if (is_numeric($uid)) {

            $this->_loadUserBy($uid, 'uid');

            if ( !isset($this->uid) ) {
                $this->uid = 0;
            }
        }
    }

    /**
     * Retrieves the User object from the active Session, or using the argument uid instead.
     *
     * @param int $uid
     *
     * @return User $user
     */
    public static function load($uid = null) {
        $user = false;

        if (is_null($uid)) {
            if ($session = Session::getActiveSession()) {
                $user = new User($session['uid']);
            }
        }
        else {
            $user = new User($uid);
        }

        return $user;
    }

    /**
     * Retrieves uid. Generic getter method.
     *
     * @return int
     */
    public function getUid() {
        return $this->uid;
    }

    /**
     * Retrieves email. Generic getter method.
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Retrieves latest login datetime. Generic getter method.
     *
     * @return datetime
     */
    public function getLoginDate() {
        return $this->loginDate;
    }

    /**
     * Retrieves favorite lang for the user. Generic getter method.
     *
     * @return string
     */
    public function getLang() {
        return $this->language;
    }

    /**
     * Retrieves creation account datetime. Generic getter method.
     *
     * @return datetime
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * Checks if the user is enabled in database.
     *
     * @return bool
     */
    public function isActive() {
        return $this->active;
    }

    public function getNewsletter() {
        return $this->active;
    }
    /**
     * Checks if the instanced User is a valid user or an empty object.
     *
     * @return bool
     */
    public function isNull() {
        return $this->getUid() == 0;
    }

    /**
     * Retrieves the username. Generic getter method. Public method defined in the interface.
     *
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Method undefined. Public method defined in the interface.
     *
     * @return void
     */
    public function eraseCredentials() {
    }

    /**
     * Retrieves the roles associated to the user. Generic getter method.
     * Public method defined in the interface.
     *
     * @return array
     */
    public function getRoles() {
        return $this->_getRoles();
    }

    /**
     * Retrieves the password. Generic getter method. Public method defined in the interface.
     *
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Retrieves the salt used to generate the hashed password. Generic getter method.
     * Public method defined in the interface.
     *
     * @return string
     */
    public function getSalt() {
        return DRUFONY_SALT;
    }

    /**
     * Retrieves Profile Object associated to the instanced User
     *
     * @return void
     */
    public function getProfile() {
        if(empty($this->profile)) {
            $this->profile = new Profile($this->uid);
        }

        return $this->profile;
    }

    /**
     * Retrieves json encoded serialized array from User attributes.
     *
     * @return void
     */
    public function serialize() {
        return \json_encode(array(
            $this->uid,
            $this->username,
            $this->password,
            $this->email,
            $this->loginDate,
            $this->language,
            $this->creationDate,
            $this->active,
            $this->roles,
            $this->newsletter 
        ));
    }

    /**
     * Sets User attributes from a json encoded serialiazed array.
     *
     * @param string $serialized (json encoded)
     *
     * @return void
     */
    public function unserialize($serialized) {
        list (
            $this->uid,
            $this->username,
            $this->password,
            $this->email,
            $this->loginDate,
            $this->language,
            $this->creationDate,
            $this->active,
            $this->roles,
            $this->newsletter
        ) = \json_decode($serialized);
    }

    /**
     * Password setter.
     *
     * @param string $password
     *
     * @return void
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * Checks if the instanced user has been granted with provided access.
     *
     * @param $access String
     *
     * @return bool $hasAccess  It's True if this user has been granted with that access,
     *                          false otherwise.
     */
    public function hasAccess($access) {
        if (empty($this->accesses)) {
            _loadAccesses();
        }

        $hasAccess = in_array($access, $this->accesses);

        return $hasAccess;
    }

    /**
     * Saves user attributes to database and sets User attributes by the array in argument.
     *
     * @param array $userData
     *
     * @return int $uid
     */
    static public function save($userData) {
        $updateMode  = isset($userData['uid']) ? true : false;
        $user        = $userData;
        $updated     = FALSE;
        $inserted    = FALSE;
        $roles       = NULL;

        if (!empty($userData['roles'])) {
            $roles = $userData['roles'];
            unset($user['roles']);
        }

        if (empty($userData['password'])) {
            unset($user['password']);
        }

        if ($updateMode) {
            $updated = db_update('users', $user, array('uid' => $userData['uid']));
        }
        else {
            $user['creationDate']  = date(DEFAULT_PUBLICATION_DATE_FORMAT);
            $user['active']        = USER_DEFAULT_STATUS;
            $user['lang']          = isset($userData['lang']) ? $userData['lang'] : DEFAULT_LANGUAGE;
	    Mailing::sendRegisterEmail($user['email']);
            $inserted = $user['uid'] = db_insert('users', $user);

            if (!$inserted) {
                throw new UserNotSaved($user);
            }
        }

        /** FIXME how to validate a user has been updated successfully
        if (!$updated && !$inserted) {
            throw new UserNotSaved($user);
        }*/

        if (!empty($roles)) {
            self::_createRoles($user['uid'], $roles);
        }

        return $user['uid'];
    }

    /**
     * Creates associated roles to the user.
     *
     * @param int $uid
     * @param array $roles
     *
     * @return void
     */
    static private function _createRoles($uid, $roles) {
        db_delete('users_roles', array('uid' => $uid));

        $rolesDefined = UserUtils::getAllRoles();

        foreach($roles as $role) {
            $rid = $rolesDefined[$role];

            $record = array(
                'uid' => $uid,
                'rid' => $rid,
            );
            db_insert('users_roles', $record);
        }
    }

    /**
     * Magic __set method. It's useful for setting whatever attribute from the User object.
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
     * Loads the accesses granted to the instanced user from database.
     *
     * @return void
     */
    private function _loadAccesses() {
        $sql = 'SELECT access
        FROM role_access, users_roles
        WHERE role_access.rid = users_roles.rid
        AND users_roles.uid = ?';

        $query = db_executeQuery($sql, array($this->uid));
        $this->accesses = $query->fetchAll();
    }

    /**
     * Loads User object by the fields passed in arguments.
     *
     * @param mixed $value
     * @param string $fieldName
     *
     * @return void
     */
    private function _loadUserBy($value, $fieldName = 'uid') {
        $sql = "SELECT * FROM users WHERE $fieldName = ?";

        if ($query = db_executeQuery($sql, array($value))) {
            $userData = $query->fetch();

            if ($userData) {
                $this->_setAttr($userData);
            }
        }
    }

    /**
     * Sets all the attributes for the instanced User by the passed array in arguments.
     *
     * @param array $userData
     *
     * @return void
     */
    private function _setAttr($userData) {
        foreach ($userData as $fieldName => $fieldValue) {
            $this->__set($fieldName, $fieldValue);
        }
    }

    /**
     * Retrives all the roles defined to the instanced user in database.
     *
     * @return array $roles
     */
    private function _getRoles() {
        if (is_null($this->roles)) {
            $this->roles = array();
            $sql = "SELECT r.name as roleName FROM users_roles ur INNER JOIN role r ON r.rid = ur.rid WHERE uid = ?";

            $result = db_executeQuery($sql, array($this->getUid()));
            while ($row = $result->fetch()) {
                $this->roles[] = $row['roleName'];
            }
        }

        return $this->roles;
    }
}
