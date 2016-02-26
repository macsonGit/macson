<?php
namespace Custom\ProjectBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drufony\CoreBundle\Model\Utils;
use Drufony\CoreBundle\Model\UserUtils;
use Drufony\CoreBundle\Model\Product;


class ImporterCommand extends ContainerAwareCommand{

    protected function configure()
    {
        $this
            ->setName('importer:products')
            ->setDescription('Import all products from product source')
        ;
    }


  protected function execute(){


	include ("/var/www/Symfony/app/config/customConfig.php");

	date_default_timezone_get('Europe/Madrid');

	
        global $db;
        $db = $this->getContainer()->get('database_connection');

        global $logger;
        $logger = $this->getContainer()->get('logger');

 	$sqlproduct = 'SELECT * FROM productsource';


	$query = db_fetchAll($sqlproduct);
	
	$i=0;

	$path='/var/www/Symfony/src/Custom/ProjectBundle/Resources/public/images/Product/'; //PONER CONSTANTES EN CONFIG
	
	foreach ($query as $item){

		if(file_exists($path.'Original/'.strtolower($item['reference'].'_1.jpg'))){

			
			$i++;
			//var_dump($item);
	 
			$nid=-1;
			$id_es=-1;
			$id_en=-1;

			$prod = array();
			//procesamos campos varieties
			//Seteamos todos los campos
			$prod['url']=$item['url_ES']."/".$item['reference'];;
			$prod['title']=$item['shortDescription_ES'];
			$prod['lang']='es';
			$prod['body']=$item['longDescription_ES'];
			$prod['composition']=$item['composition_ES'];

			$prod['category']=$item['category'];
			$prod['color']=$item['color_ES'];
			$prod['firstPrice']=$item['price'];
			if($item['price']<>$item['priceOffer']){
				$prod['priceSubtotalNoVat']=round($item['priceOffer']/(1+DEFAULT_VAT/100),2);
				$prod['pricePVP']=$item['priceOffer'];
			}
			else{
				$prod['priceSubtotalNoVat']=round($item['price']/(1+DEFAULT_VAT/100),2);			
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

			$prod['url']=$item['url_EN']."/".$item['reference'];
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
			//if (!file_exists ($path.'Standard/'.$item['reference'].'_1.jpg')){
				for($i=1;$i<=3;$i++){
					if(file_exists ($path.'Original/'.$item['reference'].'_'.$i.'.jpg')){
						$source_image = @imagecreatefromjpeg($path.'Original/'.$item['reference'].'_'.$i.'.jpg');
						$source_imagex = @imagesx($source_image);
						$source_imagey = @imagesy($source_image);

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

						  $dest_image = @imagecreatetruecolor($imageVersion['imx'],$imageVersion['imy']);
						  imageantialias($dest_image,true); 
						  @imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $imageVersion['imx'], 
							$imageVersion['imy'],$source_imagex,$source_imagey);

						  @imagejpeg($dest_image,$path.$imageVersion['name'].'/'.$item['reference'].'_'.$i.'.jpg',100);
						}
					}
				}

			//}	 
		}
	}

  }

}
