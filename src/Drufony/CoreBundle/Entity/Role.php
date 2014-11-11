<?php
/**
 * It defines the Role Entity, which will be used to attach roles to the users.
 *
 * The site permissions will be associated to these roles, so you need to
 * associate users to one or several roles to granted them some permissions.
 */

namespace Drufony\CoreBundle\Entity;

use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Implements roles in Drufony user management system
 *
 * @uses RoleInterface
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 *
 * @ORM\Entity
 * @ORM\Table(name="role")
 */
class Role implements RoleInterface, \Serializable
{
    /**
     * Identifies an unique comment in database.
     *
     * @ORM\Id
     * @ORM\Column(name="rid", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var mixed
     */
    protected $id;

    /**
     * Identifies the role using a machinename.
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @var string
     */
    protected $name;

    /**
     * Retrieves roleName
     *
     * @return void
     */
    public function getRole() {
        return $this->name;
    }

    /**
     * Retrieves json encoded serialized array from Role attributes.
     *
     * @return serialized array
     */
    public function serialize() {
        return \json_encode(array(
            $this->id,
            $this->name,
        ));
    }

    /**
     * Sets Role attributes from a json encoded serialiazed array.
     *
     * @param string $serialized (json encoded)
     *
     * @return void
     */
    public function unserialize($serialized) {
        list(
            $this->id,
            $this->name
        ) = \json_decode($serialized);
    }
}
