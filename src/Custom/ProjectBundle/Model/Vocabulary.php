<?php

namespace Custom\ProjectBundle\Model;


/**
 * Represents a unique product from the site.
 *
 * It provides methods to ask for a product.
 */

class Vocabulary {

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

  public static function getCategoryBall($category,$lang,$type='NORMAL'){

    $final=FALSE;

    $i=0;   
    $sons=array();



   $sqlproduct = 'SELECT *  FROM 
		((product INNER JOIN url_friendly ON product.id=url_friendly.oid)
		INNER JOIN varietiesByProduct ON product.id=varietiesByProduct.productId)
		INNER JOIN variety ON varietiesByProduct.varietyId=variety.id 
		WHERE (product.category=? AND product.lang=? AND product.brand NOT LIKE "%INACTIVO" AND product.brand NOT LIKE "%OUTLET" AND product.brand NOT LIKE "%MUJER" and product.brand NOT LIKE "%GENERALITAT") AND url_friendly.expirationDate IS NULL 
		GROUP BY product.sku';

   
    $query = db_fetchAll($sqlproduct, array($category,$lang));

   if ($type =='GENERALITAT'){
	
   	$sqlproduct = 'SELECT *  FROM 
		((product INNER JOIN url_friendly ON product.id=url_friendly.oid)
		INNER JOIN varietiesByProduct ON product.id=varietiesByProduct.productId)
		INNER JOIN variety ON varietiesByProduct.varietyId=variety.id 
		WHERE (product.category=? AND product.lang=? AND product.brand NOT LIKE "%INACTIVO" AND product.brand NOT LIKE "%OUTLET" AND product.brand NOT LIKE "%MUJER") AND url_friendly.expirationDate IS NULL  
		GROUP BY product.sku';
    	$query = db_fetchAll($sqlproduct, array($category,$lang));
   }

   if ($type =='OUTLET'){

	   $sqlproduct = 'SELECT *  FROM 
			((product INNER JOIN url_friendly ON product.id=url_friendly.oid)
			INNER JOIN varietiesByProduct ON product.id=varietiesByProduct.productId)
			INNER JOIN variety ON varietiesByProduct.varietyId=variety.id 
			WHERE (product.category=? AND product.lang=? AND product.brand NOT LIKE "%INACTIVO"  AND product.brand LIKE "%OUTLET") AND url_friendly.expirationDate IS NULL 
		 
			GROUP BY product.sku';
    	$query = db_fetchAll($sqlproduct, array($category,$lang));
   }

   if ($type =='MUJER'){

	   $sqlproduct = 'SELECT *  FROM 
			((product INNER JOIN url_friendly ON product.id=url_friendly.oid)
			INNER JOIN varietiesByProduct ON product.id=varietiesByProduct.productId)
			INNER JOIN variety ON varietiesByProduct.varietyId=variety.id 
			WHERE (product.category=? AND product.lang=? AND product.brand NOT LIKE "%INACTIVO" AND product.brand LIKE "%MUJER") AND url_friendly.expirationDate IS NULL 
		 
			GROUP BY product.sku';
   	 $query = db_fetchAll($sqlproduct, array($category,$lang));
   }

   if ($type =='NOVEDAD'){

	   $sqlproduct = 'SELECT *  FROM 
			((product INNER JOIN url_friendly ON product.id=url_friendly.oid)
			INNER JOIN varietiesByProduct ON product.id=varietiesByProduct.productId)
			INNER JOIN variety ON varietiesByProduct.varietyId=variety.id 
			WHERE (product.lang=? AND product.brand NOT LIKE "%INACTIVO" AND product.brand LIKE "%NOVEDAD") AND url_friendly.expirationDate IS NULL  
			GROUP BY product.sku';
	    $query = db_fetchAll($sqlproduct, array($lang));
   }





    $sqlcatname = 'SELECT * FROM categorysource WHERE name=?'; 

    $catsel= db_fetchAssoc($sqlcatname, array($category));    

    $sqlsons = 'SELECT * FROM categorysource WHERE parent =?'; 

    $sons[$i] = db_fetchAll($sqlsons, array($catsel['entityID'])); 

    while(!empty($sons[$i]) && $i<500){
      $i++;
      $sons[$i]=array(); 
      foreach ($sons[$i-1] as $subitem) {
	$sonsaux=db_fetchAll($sqlsons, array($subitem['entityID']));  
        $sons[$i] = array_merge($sonsaux,$sons[$i]);     
      }
    }

    foreach ($sons as $sub1item) {
      foreach ($sub1item as $sub2item) {
	$aux = db_fetchAll($sqlproduct,array($sub2item['name'],$lang));
        $query = array_merge($aux,$query);
      }      
    }

    usort($query, function($a, $b) {
	return strcmp($b["brand"], $a["brand"]);
    });  
    
    return $query; 
  
  }


//-------------------------------------------------------------------------
//-------------------------------------------------------------------------




public static function getAllCategories($lang){

    $sql= 'SELECT DISTINCT url_'.$lang.' as url, name  FROM categorysource INNER JOIN product ON product.category=categorysource.name WHERE product.brand NOT LIKE "%INACTIVO"';

    $query = db_fetchAll($sql, array());

    return $query;

}


//-------------------------------------------------------------------------
//-------------------------------------------------------------------------



  public static function vocabularyList($lang,$type = 'NORMAL'){



    $sql= 'SELECT *, name_'.$lang.' AS namecat, url_'.$lang.' AS url FROM categorysource WHERE parent=? ';

    $node['next'] =0;
    $node['parent']='root';
    $node['entityID']=1;
    $node['show']=FALSE;
    $node['description']='';    
    $node['sons']=array();
    $node['name']='ini';
    $node['show']=TRUE;
    $node['selected']=FALSE;
    $parent=$node['entityID'];
    $query = db_fetchAll($sql, array($parent));

    $fin=FALSE;

    $j=0;

    while($j<1000){

      $j++;

      while(!empty($query)){

        $node['sons']=$query;
        $previous=$node;   
        $node=$query[0]; 
        $node['next'] =0;        
        $node['parent']=$previous;
      
	$category=$node['name'];
      
      $sqlcat = 'SELECT category FROM product WHERE category=? AND product.brand NOT LIKE "%INACTIVO" AND brand NOT LIKE "%OUTLET" AND brand NOT LIKE "%NOVEDAD" AND brand NOT LIKE "%MUJER" and brand NOT LIKE "%GENERALITAT"';
    
 
       if ($type == 'OUTLET'){
      	$sqlcat = 'SELECT category FROM product WHERE category=? AND product.brand NOT LIKE "%INACTIVO" AND brand LIKE "%OUTLET" AND brand NOT LIKE "%NOVEDAD" AND brand NOT LIKE "%MUJER"';
      }
     
       if ($type == 'MUJER'){
      	$sqlcat = 'SELECT category FROM product WHERE category=? AND product.brand NOT LIKE "%INACTIVO" AND brand LIKE "%MUJER" AND brand NOT LIKE "%NOVEDAD"';
      }

      $querycat = db_fetchAll($sqlcat, array($category));

      if (empty($querycat)){

        $node['show']=FALSE;

      }
      else{

        $node['show']=TRUE;

      }
        //$node['show']=FALSE;
        $node['selected']=FALSE;

        $parent=$node['entityID'];

        $query = db_fetchAll($sql, array($parent)); 

      }

      $category=$node['name'];
      
      $sqlcat = 'SELECT category FROM product WHERE category=? AND product.brand NOT LIKE "%INACTIVO" AND brand NOT LIKE "%OUTLET" AND brand NOT LIKE "%NOVEDAD" AND brand NOT LIKE "%MUJER"';
     
       if ($type == 'OUTLET'){
      	$sqlcat = 'SELECT category FROM product WHERE category=? AND product.brand NOT LIKE "%INACTIVO" AND brand LIKE "%OUTLET" AND brand NOT LIKE "%NOVEDAD" AND brand NOT LIKE "%MUJER"';
      }
     
       if ($type == 'MUJER'){
      	$sqlcat = 'SELECT category FROM product WHERE category=? AND product.brand NOT LIKE "%INACTIVO" AND brand LIKE "%MUJER" AND brand NOT LIKE "%NOVEDAD"';
      }

      $querycat = db_fetchAll($sqlcat, array($category));

      if (empty($querycat)){

        $node['show']=FALSE;

      }
      else{

        $node['show']=TRUE;

      }

      $previous=$node; 
      $node=$node['parent'];
      if($node['show']===FALSE){
          $node['show']=$previous['show'];
      } 
 
      $node['sons'][$node['next']]=$previous; 
      $node['next']=$node['next']+1;
      
      while(!isset($node['sons'][$node['next']])){ 

        if($node['parent']=='root'){

            $fin=TRUE;

            break;            

        }

        $previous=$node; 
        $node=$node['parent'];
        if($node['show']===FALSE){
          $node['show']=$previous['show'];
	}        
        $node['sons'][$node['next']]=$previous;
        $node['next']=$node['next']+1;
      }

      if($fin){
        break;
      }

      $previous=$node;
      $node=$node['sons'][$node['next']];
      $node['parent']=$previous;
      $node['selected']=FALSE;       

      $category=$node['name'];
      
      $sqlcat = 'SELECT category FROM product WHERE category=? AND product.brand NOT LIKE "%INACTIVO" AND brand NOT LIKE "%OUTLET" AND brand NOT LIKE "%NOVEDAD" AND brand NOT LIKE "%MUJER"';
     
       if ($type == 'OUTLET'){
      	$sqlcat = 'SELECT category FROM product WHERE category=? AND product.brand NOT LIKE "%INACTIVO" AND brand LIKE "%OUTLET" AND brand NOT LIKE "%NOVEDAD" AND brand NOT LIKE "%MUJER"';
      }
     
       if ($type == 'MUJER'){
      	$sqlcat = 'SELECT category FROM product WHERE category=? AND product.brand NOT LIKE "%INACTIVO" AND brand LIKE "%MUJER" AND brand NOT LIKE "%NOVEDAD"';
      }

      $querycat = db_fetchAll($sqlcat, array($category));

      if (empty($querycat)){

        $node['show']=FALSE;

      }
      else{

        $node['show']=TRUE;

      }
      $node['next'] =0;  
      $parent=$node['entityID'];
      $query = db_fetchAll($sql, array($parent));
      
      $node['sons']='';
      
    }    
//var_dump($node['sons'][0]);
//$a=$a;
    return $node;
  }
 public static function vocabularyListSelected($node,$selected){
    
    $j=0;

    $fin=FALSE;


    $node['next']=0;
    $node['selected']=FALSE;


    while($j<2000){
    
      $j++;

      while (isset($node['sons'][0])){
        $previous=$node;
        $node=$node['sons'][0];
        $node['next']=0;
        $node['parent']=$previous;   
        $node['selected']=FALSE;
        if($node['name']==$selected){
          $node['selected']=TRUE;
        }

      } 
  
        $previous=$node;
        $node['parent']='';
        $node=$previous['parent']; 
    
      if(!$node['selected']){
        $node['selected']=$previous['selected'];
      }


      $node['sons'][$node['next']]=$previous; 
      $node['sons'][$node['next']]['parent']['sons']='';
      $node['next']=$node['next']+1;

      
      
      while (!isset($node['sons'][$node['next']])){
      


        if($node['parent']=='root'){
          $fin=TRUE;
          break;
        }           


        $previous=$node;
        $node=$previous['parent']; 

          if(!$node['selected']){
            $node['selected']=$previous['selected'];
          }             

        
        $node['sons'][$node['next']]=$previous;
	$node['sons'][$node['next']]['parent']['sons']='';
        $node['next']=$node['next']+1;
        
      }
      if($fin){
        break;
      }
      $previous=$node;
	$node['sons'][$node['next']]['parent']['sons']='';
      $node=$node['sons'][$node['next']];
      $node['parent']=$previous;
      $node['next'] =0; 
      $node['selected']=FALSE;
      if($node['name']==$selected){
        $node['selected']=TRUE;
      }         
      
    //  var_dump('lateral');
    //  var_dump($node);       

    }
   //var_dump($node['sons'][0]); 
    //$a=$a;
    return $node;

  }
}
