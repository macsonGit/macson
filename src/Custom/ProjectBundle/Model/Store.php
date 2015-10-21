<?php

namespace Custom\ProjectBundle\Model;


/**
 * Represents a unique product from the site.
 *
 * It provides methods to ask for a product.
 */

class Store {

  protected $reference;

  /**
   * Loads a product object if any of the supplied data matches with a registered
   * product.
   */

  public function __construct($reference,$lang) {

 }

//-------------------------------------------------------------------------
//-------------------------------------------------------------------------

  /*
   * PUBLIC METHODS
   */
  /* Getters. */
  public function getReference()                { return $this->reference; }
  /*
  */

  public static function getStoreBall($lang){

    $final=FALSE;

    $sql = 'SELECT * FROM boutiques ORDER BY bou_pais,bou_provincia, bou_poblacion,bou_orden';
    $stores = db_fetchAll($sql, array());

    return $stores; 
  
  }

  public static function getStoreNames(){

    $sql = 'SELECT bou_nombre AS name ,bou_poblacion AS city , bou_direccion AS address FROM boutiques WHERE bou_gencat=1 ORDER BY bou_pais,bou_provincia, bou_poblacion,bou_orden';
    $stores = db_fetchAll($sql, array());

    return $stores; 
  
  }
//-------------------------------------------------------------------------
//-------------------------------------------------------------------------

}
