<?php
namespace Custom\ProjectBundle\Model;

use Drufony\CoreBundle\Model\Utils;
use Drufony\CoreBundle\Model\UserUtils;
use Drufony\CoreBundle\Model\Product;

/**
 * Represents a unique product from the site.
 *
 * It provides methods to ask for a product.
 */

class Importer {


  /**
   * Loads a product object if any of the supplied data matches with a registered
   * product.
   */

  public function __construct() {

	

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

  public static function importer(){

 	$sqlproduct = 'SELECT * FROM productsource';

	$query = db_fetchAll($sqlproduct);


	
	$i=0;

	$path='/var/www/Symfony/src/Custom/ProjectBundle/Resources/public/images/Product/'; //PONER CONSTANTES EN CONFIG
	
	foreach ($query as $item){

	if(($item['stock']<>0 or $item['productSize']=='00_AGOTADO') and file_exists($path.'Original/'.strtolower($item['reference'].'_1.jpg'))){

		
		$i++;
		//var_dump($item);
 
		$nid=-1;
		$id_es=-1;
		$id_en=-1;

		$prod = array();
		//procesamos campos varieties
		//Seteamos todos los campos
		$prod['url']=$item['reference'].'-'.$item['url_ES'];
		$prod['title']=$item['shortDescription_ES'];
		$prod['lang']='es';
		$prod['body']=$item['longDescription_ES'];
		$prod['composition']=$item['composition_ES'];

		$prod['category']=$item['category'];
		$prod['color']=$item['color_ES'];
		$prod['firstPrice']=$item['price'];
		if($item['price']<>$item['priceOffer']){
			$prod['priceSubtotalNoVat']=$item['priceOffer']/(1+DEFAULT_VAT/100);
			$prod['pricePVP']=$item['priceOffer'];
		}
		else{
			$prod['priceSubtotalNoVat']=$item['price']/(1+DEFAULT_VAT/100);			
			$prod['pricePVP']=$item['price'];
		}
		$prod['priceVatPercentage']=3;
		$prod['sku']=$item['reference'].'.'.$item['productSize'];
		$prod['sgu']=$item['reference'];
		$prod['cares']=$item['cares'];
		$prod['type']='product';
		$prod['contentType']='product';
		$prod['uid']=2;

		
 		$sqlCategory = 'SELECT weight FROM categorysource WHERE name=?';

		$query = db_fetchAssoc($sqlCategory,array($item['category']));


		$prod['weight']=$query['weight'];


		$prod['published']=1;
		
		if ($item["statusProduct"]=="00_INACTIVO"){
			$prod['published']=0;
		}		
	
		$prod['brand']=$item["statusProduct"];
		
		if ($item["statusProduct"]==""){
			$prod['brand']="99_NORMAL";
		}
		
		$sqlprodFind="SELECT id,nid FROM product WHERE sgu=? and lang='es'";
		$result_es=db_fetchAssoc($sqlprodFind,array($prod['sgu']));	

		$sqlprodFind="SELECT id,nid FROM product WHERE sgu=? and lang='en'";
		$result_en=db_fetchAssoc($sqlprodFind,array($prod['sgu']));	
	
		//Comprobamos si existe el producto en la tabla product
		if(!empty($result_es) ){

			$prod['nid']=$result_es['nid'];
			$prod['id']=$result_es['id'];
		}

		$prod['varieties'] = array('Size' => $item['productSize']);

		$nid=Utils::saveData($prod);

		$sqlGetPid="SELECT id from product INNER JOIN node ON product.nid=node.nid WHERE product.nid=?";
		$result = db_fetchAssoc($sqlGetPid,array($nid));
		$pid_es=$result['id'];

		$prod['url']=$item['reference'].'-'.$item['url_EN'];
		$prod['title']=$item['shortDescription_EN'];
		$prod['lang']='en';
		$prod['body']=$item['longDescription_EN'];
		$prod['composition']=$item['composition_EN'];
		$prod['color']=$item['color_EN'];

		if(!empty($result_en) ){
			$prod['nid']=$result_en['nid'];
			$prod['id']=$result_en['id'];
		}
		$nid=Utils::saveData($prod);

		$sqlGetPid="SELECT id from product INNER JOIN node ON product.nid=node.nid WHERE product.nid=?";
		$result = db_fetchAssoc($sqlGetPid,array($nid));
		$pid_en=$result['id'];

		$sqlVariety    = "SELECT varietiesByProduct.id As vId,varietyId
				FROM varietiesByProduct
				INNER JOIN variety ON varietiesByProduct.varietyId=variety.id
				WHERE variety.value = ? and varietiesByProduct.productId = ?";

		$result_st = db_fetchAssoc($sqlVariety, array($item['productSize'],$pid_es));
		$sqlStock= "SELECT productId FROM stockByVariety WHERE productId=?";
		$result = db_fetchAssoc($sqlStock, array($result_st['vId']));
		
		if(!empty($result)) {
			$stockID=db_update('stockByVariety',array(
				'stock' => $item['stock']
			),
			array(
				'productId' => $result_st['vId']
			));
		}
		else {
		    	$stockID=db_insert('stockByVariety', array(
				'stock' => $item['stock'],
				'productId' => $result_st['vId']
			));
        	}
		$sqlVariety    = "SELECT varietiesByProduct.id As vId,varietyId
				FROM varietiesByProduct
				INNER JOIN variety ON varietiesByProduct.varietyId=variety.id
				WHERE variety.value = ? and varietiesByProduct.productId = ?";

		$result_st = db_fetchAssoc($sqlVariety, array($item['productSize'],$pid_en));
		$sqlStock= "SELECT productId FROM stockByVariety WHERE productId=?";
		$result = db_fetchAssoc($sqlStock, array($result_st['vId']));
	
		if(!empty($result)) {
			$stockID=db_update('stockByVariety',array(
				'stock' => $item['stock']
			),
			array(
				'productId' => $result_st['vId']
			));
		}
		else {
		    	$stockID=db_insert('stockByVariety', array(
				'stock' => $item['stock'],
				'productId' => $result_st['vId']
			));
        	}
		
		


		//Procesamos las imágenes
		$item['Processed']= TRUE;
		if (!file_exists ($path.'Standard/'.$item['reference'].'_1.jpg')){
			for($i=1;$i<=3;$i++){
		      		if(file_exists ($path.'Original/'.$item['reference'].'_'.$i.'.jpg')){
					$source_image = imagecreatefromjpeg($path.'Original/'.$item['reference'].'_'.$i.'.jpg');
					$source_imagex = imagesx($source_image);
					$source_imagey = imagesy($source_image);

					$imageVersions[0]=array(  //PONER CONSTANTES EN CONFIG
					  'name' => 'Standard',
					  'imx' => '320',
					  'imy' => '411');

					$imageVersions[1]=array(  //PONER CONSTANTES EN CONFIG
					  'name' => 'Small',
					  'imx' => '160',
					  'imy' => '205');

					$imageVersions[2]=array(  //PONER CONSTANTES EN CONFIG
					  'name' => 'Thumb',
					  'imx' => '80',
					  'imy' => '102');

					foreach ($imageVersions as $imageVersion) {

					  $dest_image = imagecreatetruecolor($imageVersion['imx'],$imageVersion['imy']);

					  imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $imageVersion['imx'], 
						$imageVersion['imy'],$source_imagex,$source_imagey);

					  imagejpeg($dest_image,$path.$imageVersion['name'].'/'.$item['reference'].'_'.$i.'.jpg');
					}
				}
			}

		}	 
  	}
	}

  }

  public static function importerUser(){


 	$sqlUser = 'SELECT * FROM customersource';

	$query = db_fetchAll($sqlUser);

	foreach ($query as $user){

		$profile = array();

		$profile['uid'] = UserUtils::createUser($user);

		$profile['name']=$user['firstname'].' '.$user['lastname'];
		
		UserUtils::saveProfile($profile);

 		$sqlAddresses = 'SELECT * FROM addresssource WHERE id_customer=?';

		$queryAddress = db_fetchAll($sqlAddresses,array($user['id_customer']));


		/*foreach ($queryAddress as $address){
			
			$newaddress = array(

				'address' => $address['address1'].' '.$address['address2'],
				'countryId' => $address['id_country'],
				'province' => $address['postcode'],
				'postalCode' => $address['postcode'],
				'city' => $address['city'],
				'nif' => $address['dni'],
				'name'=>$address['firstname'].' '.$address['lastname'],
				'nif' => $address['dni'],
				'phone' => $address['phone'],
				'uid' => $profile['uid'],
	
			);
			
			UserUtils::saveAddress($newaddress);

		
		}*/


	}


  }

}
