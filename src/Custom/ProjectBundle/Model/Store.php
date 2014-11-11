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

    $sql = 'SELECT * FROM boutiques';
    $stores = db_fetchAll($sql, array());

    return $stores; 
  
  }

//-------------------------------------------------------------------------
//-------------------------------------------------------------------------

}
