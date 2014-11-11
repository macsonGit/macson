<?php

namespace Macson\ProjectBundle\Controller;
 
use Drufony\CoreBundle\Controller\DrufonyController;
use Macson\ProjectBundle\Controller\ProjectBaseController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Drufony\ProductBundle\Model\Product;
use Drufony\GeoBundle\Model\Geo;
 
class HomeLangController extends DrufonyController
{
    public function indexAction($lang)
    {
        $this->setContainer();
        $vocabulary = 'productcategory';
        $menu= Product::vocabularyList($vocabulary,$lang); //VARIABLE A GUARDAR EN MEMCACHED
        $menuSelected = Product::vocabularyListSelected($menu,1);
    	$categoryBall = Product::getCategoryBall(1,$vocabulary,$lang);
        $menuSelected['selected']='1';
        return  $this->render('MacsonProjectBundle:Default:basic.html.twig',array('view'=> 'home','menu' => $menuSelected, 'lang' => $lang));
    }
}

