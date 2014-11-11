<?php

namespace Drufony\CoreBundle\Model;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Profile; Class to represent user profiles.
 *
 * @package Crononauta
 * @version $Id$
 */

class Profile {

    /**
     * uid; The id of the user related to the profile.
     *
     * @var int
     */

    private $uid;

    /**
     * name
     *
     * @var string
     */

    private $name;

    /**
     * picture
     *
     * @var string
     */

    private $picture;

    /**
     * website
     *
     * @var string
     */

    private $website;

    /**
     * mobile
     *
     * @var string
     */

    private $mobile;

    /**
     * phone
     *
     * @var string
     */

    private $phone;

    /**
     * twitterId
     *
     * @var string
     */

    private $twitterId;

    /**
     * facebookId
     *
     * @var string
     */

    private $facebookId;

    /**
     * googleId
     *
     * @var string
     */

    private $googleId;

    /**
     * addresses
     *
     * @var array
     */

    private $addresses;

    /**
    ** backgroundPicture
    **
    ** @var string
    **/

    private $backgroundPicture;


    /**
     * __construct
     *
     * @param int $uid
     * @return void
     */

    public function __construct($uid = null) {
        if(!empty($uid)) {
            $this->uid = $uid;
            $params = array();
            $sql = "SELECT p.* FROM profile AS p WHERE uid = ?";
            $params[] = $uid;
            $result = db_executeQuery($sql, $params);
            $profile_data = $result->fetch();
            if($profile_data) {
                // Takes the attributes of the class
                $atts = array_keys(get_class_vars(get_class($this)));
                foreach($profile_data as $key => $value) {
                    if(array_search($key, $atts)) {
                        $this->$key = $value;
                    }
                }
            }
        }
    }

    /**
     * getUid
     *
     * @return int
     */

    public function getUid() { return $this->uid; }

    /**
     * getName
     *
     * @return string
     */

    public function getName() { return $this->name; }

    /**
     * getPicture
     *
     * @return string
     */

    public function getPicture() {
        if (!is_object($this->picture)) {
            $sql = "SELECT uri FROM file_managed WHERE fid = ?";
            $results = db_executeQuery($sql, array($this->picture));
            $path = $results->fetchColumn();
            if ($path) {
                $this->picture = new File($path);
            }
            else {
                $this->picture = null;
            }
        }

        return $this->picture;
    }

    /**
     * getWebsite
     *
     * @return string
     */

    public function getWebsite() { return $this->website; }

    /**
     * getMobile
     *
     * @return string
     */

    public function getMobile() { return $this->mobile; }

    /**
     * getPhone
     *
     * @return string
     */

    public function getPhone() { return $this->phone; }

    /**
     * getTwitterId
     *
     * @return string
     */

    public function getTwitterId() { return $this->twitterId; }

    /**
     * getFacebookId
     *
     * @return string
     */

    public function getFacebookId() { return $this->facebookId; }

    /**
     * getGoogleId
     *
     * @return string
     */

    public function getGoogleId() { return $this->googleId; }

    /**
     * getAddresses
     *
     * @return string
     */

    public function getAddresses() {
        if (is_null($this->addresses)) {
	    $sql = "SELECT id, type, address, countryId, province, name, nif, postalCode, city, phone ";
            $sql .= "FROM addresses WHERE uid = ?";
            $params[] = $this->getUid();

            $this->addresses = array();
            if($result = db_executeQuery($sql, $params)) {
                while($row = $result->fetch()) {
                    $this->addresses[$row['id']] = $row;
                }
            }
        }

        return $this->addresses;
    }

    /**
     * getAddress
     *
     * @return string
     */

    public function getAddress($addressId) {
        $addresses = $this->getAddresses();

        $addressFound = array();
        if(array_key_exists($addressId, $this->addresses)) {
            $addressFound = $this->addresses[$addressId];
        }

        return $addressFound;
    }

    public function getBackgroundPicture() {
        if (!is_object($this->backgroundPicture)) {
            $sql = "SELECT uri FROM file_managed WHERE fid = ?";
            $results = db_executeQuery($sql, array($this->backgroundPicture));
            $path = $results->fetchColumn();
            if ($path) {
                $this->backgroundPicture = new File($path);
            }
            else {
                $this->backgroundPicture = null;
            }
        }

        return $this->backgroundPicture;
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }

    public function __toArray()
    {
        $arrayData = array();
        foreach ($this as $key => $value) {
            $arrayData[$key] = $value;
        }

        return $arrayData;
    }
}
