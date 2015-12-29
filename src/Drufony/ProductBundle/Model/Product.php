<?php

namespace Drufony\ProductBundle\Model;


/**
 * Represents a unique product from the site.
 *
 * It provides methods to ask for a product.
 */
class Product {

  protected $reference;

  /**
   * Loads a product object if any of the supplied data matches with a registered
   * product.
   */

  public function __construct($reference) {

    $this->getProductBall($reference);

 }

  /*
   * PUBLIC METHODS
   */
  /* Getters. */
  public function getReference()                { return $this->reference; }
  /*
  */

  public static function getCategoryBall($category,$vocabulary){



    $final=FALSE;

    $i=0;   
    $sons=array();

    $sqlproduct = 'SELECT * FROM product WHERE category= ? GROUP BY reference'; //OJO DECLARA MAX_PRODUCT_QUERY EN CONSTANTES
    $query = db_fetchAll($sqlproduct, array($category));

    $sqlcatname = 'SELECT * FROM taxonomy_vocabulary INNER JOIN taxonomy_term_data ON taxonomy_vocabulary.vid=taxonomy_term_data.vid INNER JOIN taxonomy_term_hierarchy ON taxonomy_term_data.tid=taxonomy_term_hierarchy.tid WHERE taxonomy_term_data.name =?'; 
    $catsel= db_fetchAssoc($sqlcatname, array($category));    

    $sqlsons = 'SELECT * FROM taxonomy_vocabulary INNER JOIN taxonomy_term_data ON taxonomy_vocabulary.vid=taxonomy_term_data.vid INNER JOIN taxonomy_term_hierarchy ON taxonomy_term_data.tid=taxonomy_term_hierarchy.tid WHERE taxonomy_term_hierarchy.parent =?'; 
    $sons[$i] = db_fetchAll($sqlsons, array($catsel['tid'])); 

    while(!empty($sons[$i]) && $i<5000){
      $i++;
      $sons[$i]=array(); 
      if($i==2){
        $a=$subitem['tid'];
      }     
      foreach ($sons[$i-1] as $subitem) {
        $sonsaux=db_fetchAll($sqlsons, array($subitem['tid']));
        $sons[$i] = array_merge($sonsaux,$sons[$i]);
      }
    }

    foreach ($sons as $sub1item) {
      foreach ($sub1item as $sub2item) {
        $query = array_merge(db_fetchAll($sqlproduct, array($sub2item['name'])),$query);
      }      
    }

    return $query; 
  
  }

  public function getProductBall($reference){

    $sql = 'SELECT * FROM product WHERE reference= ? LIMIT 100';
    $query = db_fetchAll($sql, array($reference));
    $this->productBall = $query;

  }

  public static function vocabularyList($vocabulary){

    $vocabularyListVals=array();

    $sql = 'SELECT * FROM taxonomy_vocabulary 
    INNER JOIN taxonomy_term_data ON taxonomy_vocabulary.vid=taxonomy_term_data.vid 
    INNER JOIN taxonomy_term_hierarchy ON taxonomy_term_data.tid=taxonomy_term_hierarchy.tid 
    WHERE taxonomy_vocabulary.name =?'; 
    $query = db_fetchAll($sql, array($vocabulary));



    foreach ($query As $item){

      $insert = $item;
      $insert['show']= FALSE;    
      
      if(!isset($vocabularyListVals[$item['parent']]['items'])){
         $vocabularyListVals[$item['parent']]=array();
         $vocabularyListVals[$item['parent']]['items']=array();
         $vocabularyListVals[$item['parent']]['shownode']=FALSE;
      }

      array_push($vocabularyListVals[$item['parent']]['items'],$insert); 

    }


    $sql = 'SELECT category FROM  (SELECT DISTINCT category FROM product) AS productcat
    INNER JOIN taxonomy_term_data ON productcat.category=taxonomy_term_data.name 
    INNER JOIN taxonomy_vocabulary ON taxonomy_term_data.vid=taxonomy_vocabulary.vid  
    INNER JOIN taxonomy_term_hierarchy ON taxonomy_term_data.tid=taxonomy_term_hierarchy.tid 
    WHERE taxonomy_vocabulary.name =?'; 
    $querycat = db_fetchAll($sql, array($vocabulary));

    $i=0;

    $level_index=array();
    $level_index[0]=0;

    $previous[0]=0;   


    $item=$vocabularyListVals[0]['items'][0]; 



    $j=0;

    //$vocabularyListVals[0]['items'][0]['shownode']=TRUE;
    //var_dump( $vocabularyListVals[0]['items'][0]);      


    while($j<1000){

      $j++;


      foreach ($querycat as $cat) {
        if($item['name']==$cat['category']){
            $id_parent=$item['parent'];
            $vocabularyListVals[$id_parent]['shownode']=TRUE;
            var_dump('found');
            var_dump($item['description']);            
            var_dump($id_parent);
        }
      }         



      if(isset($vocabularyListVals[$item['tid']])){
          if(isset($vocabularyListVals[$item['tid']]['shownode'])){
            $vocabularyListVals[$item['parent']]['shownode']=TRUE;
            var_dump('parent');
            var_dump($item['description']);
            var_dump($vocabularyListVals[$item['parent']]['shownode']);
          }
          else{
            $vocabularyListVals[$item['parent']]['shownode']=FALSE;
          }
          
          $i++;
          $previous[$i]=$item['tid'];
          $item=$vocabularyListVals[$item['tid']]['items'][0];   
         
          
          $level_index[$i]=0;
      }
      else{
          $level_index[$i]++;
          if(!isset($vocabularyListVals[$item['tid']]['items'][$level_index[$i]])){ 

            if($level_index[0]==count($vocabularyListVals[0]['items'])){
              break;
            }  

          $item=$vocabularyListVals[$previous[$i]]['items'][$level_index[$i]];


            $item=$vocabularyListVals[$previous[$i]]['items'][$level_index[$i]];
  
            if($i>0){ $i--;}


          }
          else{
          $vocabularyListVals[$item['parent']]['shownode']=FALSE;
          //var_dump('nuevo');
          //var_dump($item['description']);
          //var_dump($vocabularyListVals[$item['parent']]['shownode']);             
            $item=$vocabularyListVals[$item['tid']]['items'][$level_index[$i]]; 
          }

      }
    }  
  

    return $vocabularyListVals;
  }

}
