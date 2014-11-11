<?php

namespace Drufony\CoreBundle\Model;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drufony\CoreBundle\Model\Pool;

/**
 * NodePool class implements the set of relations that nodes can have
 * to other objects such as users, taxonomies, vocabulary or other nodes.
*/

class NodePool extends Pool {

  /**
   * Fetches a pool from the database
   * @param string $poolType; The type of the pool.
   * @param int $id; The id of the object the nodes are related to.
   * @return array keys('id', 'objectType', 'items'); The information of the pool.
   */

  protected function get($poolType, $nid) {
    $pool = array();
    $pool['items'] = array();

    $sql = "SELECT objectId, value, status FROM node_pools WHERE nid = ? AND type = ?";

    $data = db_executeQuery($sql, array($nid, $poolType));

    while($relation = $data->fetch()) {
      $pool['items'][] = (object)array('id' => intval($relation['objectId']), 'value' => intval($relation['value']), 'status' => intval($relation['status']));
    }

    return $pool;
  }

  /**
   * Updates a relation in the database.
   * @param int $id; The id of the node to update in the pool.
   * @return boolean; True if success.
   */

  protected function updateItem($id, $type, $index) {
    $criteria = array(
      'nid' => $id,
      'type' => $type,
      'objectId' => $this->items[$index]->id
    );

    $updateData = array(
      'status' => $this->items[$index]->status,
      'value' => $this->items[$index]->value,
      'changed' => date('Y-m-d H:i:s')
    );

    $result = db_update('node_pools', $updateData, $criteria);

    return $result;
  }

  /**
   * Inserts a relation in the database.
   * @param int $id; The id of the node to insert in the pool.
   * @param int $status; The status of the node to insert in the pool.
   * @return boolean; True if success.
   */

  protected function insertRelation($objectId, $value, $status) {
    $insertData = array(
      'objectId' => $objectId,
      'nid' => $this->id,
      'weight' => 0,
      'status' => $status,
      'value' => $value,
      'created' => date('Y-m-d H:i:s'),
      'changed' => date('Y-m-d H:i:s'),
      'type' => $this->type
    );

    $result = db_insert('node_pools', $insertData);

    return $result;
  }

  /**
   * Deletes a relation from the database.
   * @param int $id; Id of the node which relation we want to delete.
   * @return boolean; true if success.
   */

  protected function deleteRelation($id) {
    $insertData = array(
        'nid' => $this->id,
        'type' => $this->type,
        'objectId' => $id
    );

    $saved=db_delete('node_pools', $insertData);

    return $saved;
  }
}
