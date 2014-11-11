<?php
/**
 * Implements all the functions which help us to manage tasks in Drufony.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Drufony\CoreBundle\Entity\User;

/**
 * It defines all the static methods which allows to manage tasks in Drufony.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Task
{
    const STATUS_NEW      = 1;
    const STATUS_WORKING  = 2;
    const STATUS_FEEDBACK = 3;
    const STATUS_DONE     = 4;
    const STATUS_REJECTED = 5;

    const LEVEL_TASK    = 1;
    const LEVEL_NOTICE  = 2;
    const LEVEL_WARNING = 3;
    const LEVEL_ERROR   = 4;

    static public function getAllStatus() {
        return array(
            self::STATUS_NEW       => t('New'),
            self::STATUS_WORKING   => t('Working'),
            self::STATUS_FEEDBACK  => t('Feedback'),
            self::STATUS_DONE      => t('Done'),
            self::STATUS_REJECTED => t('Rejected')
        );
    }

    static public function getAllUserTaskable() {
        $sql = "SELECT u.uid, u.username
            FROM users u
            INNER JOIN users_roles ur ON u.uid = ur.uid
            INNER JOIN role r ON r.rid = ur.rid
            WHERE r.name IN (?)";
        $results = db_executeQuery($sql, array(implode(', ', self::getRolesTaskable())));
        $users = array();
        while ($row = $results->fetch()) {
            $users[$row['uid']] = ucfirst($row['username']);
        }

        return $users;
    }

    static public function getAllLevels() {
        return array(
            self::LEVEL_TASK    => t('Task'),
            self::LEVEL_NOTICE  => t('Notice'),
            self::LEVEL_WARNING => t('Warning'),
            self::LEVEL_ERROR   => t('Error'),
        );
    }

    static public function save($task) {
        $record = array(
            'title'       => $task['title'],
            'description' => $task['description'],
            'uid'         => $task['assigned'],
            'status'      => $task['status'],
            'level'       => $task['level'],
        );
        if (empty($task['id'])) {
            $id = db_insert('tasks', $record);
        }
        else {
            $id = $task['id'];
            db_update('tasks', $record, array('id' => $task['id']));
        }

        return $id;
    }

    static public function getAll($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql = "SELECT t.*, u.username AS userName FROM tasks t LEFT JOIN users u ON u.uid = t.uid";
        $results = db_executeQuery($sql, array(), $page, $itemsPerPage);
        $tasks = array();
        $statuses = self::getAllStatus();
        $levels = self::getAllLevels();
        while ($row = $results->fetch()) {
            $row['statusName'] = isset($statuses[$row['status']]) ? $statuses[$row['status']] : '-';
            $row['levelName']  = isset($levels[$row['level']]) ? $levels[$row['level']] : '-';
            $row['contentType'] = 'task';
            $tasks[] = $row;
        }

        return $tasks;
    }

    static public function getAllCount() {
        $sql = "SELECT COUNT(*) FROM tasks t LEFT JOIN users u ON u.uid = t.uid";
        $count = db_fetchColumn($sql);

        return $count;
    }

    static public function load($id) {
        $sql = "SELECT t.*, u.username AS userName
            FROM tasks t
            LEFT JOIN users u ON u.uid = t.uid
            WHERE t.id = ?";
        $result = db_executeQuery($sql, array($id));
        $task = $result->fetch();
        if (!empty($task)) {
            $statuses = self::getAllStatus();
            $levels = self::getAllLevels();
            $task['statusName'] = isset($statuses[$task['status']]) ? $statuses[$task['status']] : '-';
            $task['levelName']  = isset($levels[$task['level']]) ? $levels[$task['level']] : '-';
        }

        return $task;

    }

    static public function delete($id) {
        db_delete('tasks', array('id' => $id));
    }

    /**
     * Returns rid which can be assigned tasks
     */
    static private function getRolesTaskable() {
        return array(User::ROLE_ADMIN);
    }
}
