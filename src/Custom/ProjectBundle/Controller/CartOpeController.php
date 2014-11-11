<?php
namespace Custom\ProjectBundle\Controller;
 
use Custom\ProjectBundle\Model\Vocabulary; 
use Macson\ProjectBundle\Controller\ProjectBaseController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Drufony\ProductBundle\Model\Product;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drufony\CoreBundle\Controller\DrufonyController;
use Drufony\CoreBundle\Form\CommentFormType;
use Drufony\CoreBundle\Entity\Comment;
use Drufony\CoreBundle\Model\UserUtils;
use Drufony\CoreBundle\Model\Drupal;
use Drufony\CoreBundle\Model\Locale;
use Drufony\CoreBundle\Model\Profile;
use Drufony\CoreBundle\Model\Mailing;
use Drufony\CoreBundle\Model\Utils;
use Drufony\CoreBundle\Model\Geo;
use Drufony\CoreBundle\Form\RegisterFormType;
use Drufony\CoreBundle\Form\LoginFormType;
use Drufony\CoreBundle\Form\ProfileFormType;
use Drufony\CoreBundle\Form\AccountFormType;
use Drufony\CoreBundle\Form\ForgotPasswordFormType;
use Drufony\CoreBundle\Form\BillingInfoFormType;
use Drufony\CoreBundle\Model\CommerceUtils;


 
class CartOpeController extends DrufonyController
{
    public function indexAction($ope,$pid,$vid)
    {

	//$a=$a;


	
	switch ($ope) {
    	
		case 'add':
        		CommerceUtils::addToCart($pid,1,$vid);
			l('INFO','despues de add');
        		break;
    		case 'remove':
			CommerceUtils::removeFromCart($vid);
        		break;
	}

	
	$cart=CommerceUtils::getCartItemsAJAX();

	

	
	$response = json_encode($cart);

	//$response = '{"campo1" ,"hola"}';
        return new Response($response, 200, array(
            'Content-Type' => 'application/json'
        ));

    }
}



