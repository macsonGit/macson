<?php

namespace Custom\ProjectBundle\Controller;

use Custom\ProjectBundle\Model\Importer;
use Symfony\Component\HttpFoundation\Response;
use Drufony\CoreBundle\Model\Product;
use Drufony\CoreBundle\Controller\DrufonyController;
use Drufony\CoreBundle\Form\CommentFormType;
use Drufony\CoreBundle\Entity\Comment;

class ImporterController extends DrufonyController
{
    public function indexAction() {
        $response = new Response();
        
	Importer::importer();
	
	return $response;
    }
    public function userAction() {
        $response = new Response();
        
	Importer::importerUser();
	
	return $response;
    }
}
