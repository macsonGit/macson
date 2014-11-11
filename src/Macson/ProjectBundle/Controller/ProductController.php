<?php

namespace Macson\ProjectBundle\Controller;
 
use Macson\ProjectBundle\Controller\ProjectBaseController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Drufony\ProductBundle\Model\Product;
 
class ProductController extends ProjectBaseController
{
    public function indexAction($lang,$product,$category)
    
    {
        $this->init();

    	$prod = new Product($product,$lang);

        $vocabulary = 'productCategory';
        $menu= Product::vocabularyList($vocabulary,$lang); //VARIABLE A GUARDAR EN MEMCACHED

        $menuSelected = Product::vocabularyListSelected($menu,$category);

        $menuSelected['selected']=$category;

        $kernel = $this->get('kernel');

        $numImages= array ('im1' => FALSE, 'im2' => FALSE, 'im3' => FALSE );

        $path = $kernel->locateResource('@MacsonProjectBundle/Resources/public/images/Product/Standard/');
            
        $numImages['im1'] = file_exists($path.$product.'_1.jpg');
        $numImages['im2'] = file_exists($path.$product.'_2.jpg');
        $numImages['im3'] = file_exists($path.$product.'_3.jpg');


        if (!empty($prod->productBall) && !empty($menuSelected)){
        	return  $this->render('MacsonProjectBundle:Default:basic.html.twig',array('view'=> 'product' ,'product'=> $prod->productBall, 'menu' => $menuSelected, 'lang' => $lang, 'numImages' => $numImages));
        }
        else{
        	return  $this->render('MacsonProjectBundle:Default:error.html.twig',array());
        }
    }

}

