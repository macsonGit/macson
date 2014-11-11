<?php

namespace Drufony\CoreBundle\Model;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drufony\CoreBundle\Model\Pool;


class TermPool extends Pool {

  /**
   * Fetches a pool from the database
   * @param string $poolType; The type of the pool.
   * @param int $objectId; The id of the object the terms are related to.
   * @return array keys('id', 'value', 'status'); The information of the pool.
   */

  protected function get($poolType, $tid) {
    $pool = array();
    $pool['items'] = array();
    $params = array();

    $sql = "SELECT objectId, value, status FROM term_pools WHERE tid = ? AND type = ?";
    $params[] = $tid;
    $params[] = $poolType;

    $data = db_executeQuery($sql, $params);

    while($relation = $data->fetch()) {
      $pool['items'][] = (object)array('id' => intval($relation['objectId']), 'value' => intval($relation['value']), 'status' => intval($relation['status']));
    }

    return $pool;
  }

  /**
   * Updates a relation in the database.
   * @param int $id; The id of the term to update in the pool.
   * @return boolean; True if success.
   */

  protected function updateItem($id, $type, $index) {
    $criteria = array(
      'tid' => $id,
      'type' => $type,
      'objectId' => $this->items[$index]->id
    );

    $updateData = array(
      'status' => $this->items[$index]->status,
      'value' => $this->items[$index]->value,
      'changed' => date('Y-m-d H:i:s')
    );

    $result = db_update('term_pools', $updateData, $criteria);

    return $result;
  }

  /**
   * Inserts a relation in the database.
   * @param int $id; The id of the term to insert in the pool.
   * @return boolean; True if success.
   */

  protected function insertRelation($objectId, $value, $status) {
    $insertData = array(
      'objectId' => $objectId,
      'tid' => $this->id,
      'weight' => 0,
      'status' => $status,
      'value' => $value,
      'created' => date('Y-m-d H:i:s'),
      'changed' => date('Y-m-d H:i:s'),
      'type' => $this->type
    );

    $result = db_insert('term_pools', $insertData);

    return $result;
  }

  /**
   * Deletes a relation from the database.
   * @param int $id; Id of the term which relation we want to delete.
   * @return boolean; true if success.
   */

  protected function deleteRelation($id) {
    $insertData = array(
        'tid' => $this->id,
        'type' => $this->type,
        'objectId' => $id
    );

    $saved=db_delete('term_pools', $insertData);

    return $saved;
  }
}
