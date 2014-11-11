<?php

namespace Macson\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('MacsonProjectBundle:Default:index.html.twig', array('name' => $name));
    }
}
