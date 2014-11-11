<?php

namespace Macson\ProjectBundle\Controller;

use Drufony\CoreBundle\Controller\DrufonyController;
use Macson\ProjectBundle\Controller\ProjectBaseController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Drufony\ProductBundle\Model\Product;

class HomeController extends DrufonyController {
	public function indexAction() {
		$this->setContainer();
		return $this->redirect($this->generateUrl('macson_homepage_lang',array('lang' => $lang)), 301);
	}
}


