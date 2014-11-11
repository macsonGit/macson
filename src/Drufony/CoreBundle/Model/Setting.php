<?php
/**
 * This class allows to get/set system configuration values from/to
 * database. It's commonly used for those settings which must be managed
 * by the admin user interface.
 */

namespace Drufony\CoreBundle\Model;

/**
 * Settings class allows to get/set system configuration values from/to database.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Setting
{
    /**
     * Returns a saved setting value form database
     * @param attribute, an existent attribute setted in database
     * @return a string for this value, null otherwise
     */
    static public function get($attribute) {
        $sql = "SELECT value FROM settings WHERE attribute = ?";
        $setting = db_fetchColumn($sql, array($attribute));

        return $setting ? unserialize($setting) : NULL;
    }

    /**
     * Set an attribute value into database
     * @param attribute, string for key setting
     * @param value, mixed value, it will be serialized
     */
    static public function set($attribute, $value) {
        $sql = "SELECT COUNT(1) AS count FROM settings WHERE attribute = ?";
        $results = db_executeQuery($sql, array($attribute));
        $count = $results->fetch()['count'];
        $value = serialize($value);
        if ($count) {
            if (db_update('settings', array('value' => $value), array('attribute' => $attribute))) {
                l(INFO, 'Setting: ' . $attribute . ' updated successfully');
            }
            else {
                l(ERROR, 'Error updating setting: ' . $attribute . ' ');
            }
        }
        else {
            if (db_insert('settings', array('attribute' => $attribute, 'value' => $value))) {
                l(INFO, 'Setting: ' . $attribute . ' inserted successfully');
            }
            else {
                l(ERROR, 'Error inserting setting: ' . $attribute . ' ');
            }
        }
    }
}
