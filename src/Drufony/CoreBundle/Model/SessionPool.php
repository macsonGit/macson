<?php

namespace Drufony\CoreBundle\Model;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drufony\CoreBundle\Model\Pool;


class SessionPool extends Pool {

  /**
   * Fetches a pool from the database
   * @param string $poolType; The type of the pool.
   * @param int $objectId; The id of the object the sessions are related to.
   * @return array keys('objectId', 'objectType', 'items'); The information of the pool.
   */

  protected function get($poolType, $sessId) {
    $pool = array();
    $pool['items'] = array();

    $sql = "SELECT objectId, value, status FROM session_pools WHERE sessId = ? AND type = ?";

    $data = db_executeQuery($sql, array($sessId, $poolType));

    while($relation = $data->fetch()) {
      $pool['items'][] = (object)array('id' => intval($relation['objectId']), 'value' => intval($relation['value']), 'status' => intval($relation['status']));
    }

    return $pool;
  }

  /**
   * Updates a relation in the database.
   * @param int $id; The id of the session to update in the pool.
   * @return boolean; True if success.
   */

  protected function updateItem($id, $type, $index) {
    $criteria = array(
      'sessId' => $id,
      'type' => $type,
      'objectId' => $this->items[$index]->id
    );

    $updateData = array(
      'status' => $this->items[$index]->status,
      'value' => $this->items[$index]->value,
      'changed' => date('Y-m-d H:i:s')
    );

    $result = db_update('session_pools', $updateData, $criteria);

    return $result;
  }

  /**
   * Inserts a relation in the database.
   * @param int $id; The id of the session to insert in the pool.
   * @return boolean; True if success.
   */

  protected function insertRelation($objectId, $value, $status) {
    $insertData = array(
      'objectId' => $objectId,
      'sessId' => $this->id,
      'weight' => 0,
      'status' => $status,
      'value' => $value,
      'created' => date('Y-m-d H:i:s'),
      'changed' => date('Y-m-d H:i:s'),
      'type' => $this->type
    );

    $result = db_insert('session_pools', $insertData);

    return $result;
  }

  /**
   * Deletes a relation from the database.
   * @param int $id; Id of the session which relation we want to delete.
   * @return boolean; true if success.
   */

  protected function deleteRelation($id) {
    $insertData = array(
      'objectId' => $id,
      'sessId' => $this->id,
      'type' => $this->type
    );

    $saved=db_delete('session_pools', $insertData);

    return $saved;
  }
}
