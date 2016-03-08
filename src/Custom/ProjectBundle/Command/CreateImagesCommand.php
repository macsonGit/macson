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


class CreateImagesCommand extends ContainerAwareCommand{

    protected function configure()
    {
        $this
            ->setName('importer:images')
            ->setDescription('Create all images from product source')
        ;
    }


  protected function execute(){


	include ("/var/www/Symfony/app/config/customConfig.php");

	date_default_timezone_get('Europe/Madrid');

	
        global $db;
        $db = $this->getContainer()->get('database_connection');

        global $logger;
        $logger = $this->getContainer()->get('logger');

 	$sqlproduct = 'SELECT DISTINCT reference FROM productsource';


	$query = db_fetchAll($sqlproduct);
	
	$i=0;

	$path='/var/www/Symfony/src/Custom/ProjectBundle/Resources/public/images/Product/'; //PONER CONSTANTES EN CONFIG
	
	foreach ($query as $item){

		if(file_exists($path.'Original/'.strtolower($item['reference'].'_1.jpg'))){

			
			
			
			for($i=1;$i<=3;$i++){
				if(file_exists ($path.'Original/'.$item['reference'].'_'.$i.'.jpg')){
			
					var_dump($item['reference'].'_'.$i);
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
					  //imageantialias($dest_image,true); 
					  @imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $imageVersion['imx'], 
						$imageVersion['imy'],$source_imagex,$source_imagey);

					  @imagejpeg($dest_image,$path.$imageVersion['name'].'/'.$item['reference'].'_'.$i.'.jpg',100);
					}
				}

			}	 
		}
	}

  }

}
