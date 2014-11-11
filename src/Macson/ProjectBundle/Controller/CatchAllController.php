<?php

namespace Macson\ProjectBundle\Controller;
 
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
 
class CatchAllController extends Controller
{
    public function indexAction()
    {
    	$esteControlador='CathAll';
        return  $this->render('MacsonProjectBundle:Default:basico.html.twig',array('esteControlador'=> $esteControlador));
    }
}

