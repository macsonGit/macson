<?php
/**
 * It defines the Access layer, which will be used to manage permissions
 * in the web project. The site permissions will be associated to roles,
 * so you need to associate users to one or several roles to granted them
 * some permissions.
 *
 * This is a static class.
 */

namespace Drufony\CoreBundle\Model;

/**
 * Access layer, which will be used to manage permissions.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Access
{
    /**
     * getRoles
     *
     * Retrieves all the roles defined in database.
     *
     * @return array $roles
     */
    static public function getRoles() {
        $sql = "SELECT rid, name FROM role";
        $rolesData = db_executeQuery($sql);

        $roles = array();
        while ($oneRole = $rolesData->fetch()) {
            $roles[$oneRole['rid']] = $oneRole['name'];
        }

        return $roles;
    }

    /**
     * getAllRoleAccesses
     *
     * Retrieves all the permissions defined in database.
     *
     * @return void
     */
    static public function getAllRoleAccesses() {
        $sql = 'SELECT * FROM role_access ORDER BY module, access';
        $roleData = db_executeQuery($sql);

        $accesses = $roleData->fetchAll();

        return $accesses;
    }

    /**
     * getAllModuleAccess
     *
     * Retrieves all defined permissions by module.
     *
     * @return void
     */
    static public function getAllModuleAccess() {
        $modules = array();

        $sql   = 'SELECT access, module FROM role_access ORDER BY module';
        $query = db_executeQuery($sql);

        while ($row = $query->fetch()) {
            $modules[str_replace(' ', '_', $row['access'])] = $row['module'];
        }

        return $modules;
    }

    /**
     * roleHasAccess
     *
     * Checks if a given role has granted access.
     *
     * @param mixed $rid
     * @param mixed $access
     *
     * @return bool $hasAccess
     */
    static public function roleHasAccess($rid, $access) {
        $hasAccess = false;

        $sql   = 'SELECT COUNT(1) AS count FROM role_access WHERE access = ? AND rid = ?';
        $query = db_executeQuery($sql, array($access, $rid));

        while ($row = $query->fetch()) {
            if ($row['count'] != 0) {
                $hasAccess = true;
                break;
            }
        }

        return $hasAccess;
    }

    /**
     * cleanAccesses
     *
     * Removes all permissions from database.
     *
     * @return void
     */
    static public function cleanAccesses() {
        $sql = 'TRUNCATE role_access';

        db_executeQuery($sql);
    }

    /**
     * assignAccess
     *
     * Assign a permission access to a given role.
     *
     * @param int $rid
     * @param string $access
     * @param string $module
     *
     * @return void
     */
    static public function assignAccess($rid, $access, $module) {
        $record = array(
            'rid'    => $rid,
            'access' => $access,
            'module' => $module,
        );

        db_insert('role_access', $record);
    }
}
