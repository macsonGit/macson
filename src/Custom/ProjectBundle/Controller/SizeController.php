<?php

namespace Custom\ProjectBundle\Controller;

use Custom\ProjectBundle\Model\Vocabulary; 
use Custom\ProjectBundle\Model\Store; 
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



 
class SizeController extends DrufonyController
{
    public function indexAction($lang,Request $request)
    {
    
        $response = new Response();
        $session     = getSession();
        $user        = $this->getUser();
	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
        $uid         = NULL;
        $rememberme  = FALSE;
                              
       $widgets = array(
            'social' => array(
                'facebookShare' => TRUE,
                'twitterShare'  => TRUE,
                'googleShare'   => TRUE,
                'facebookLike'  => TRUE,
                'googleLike'    => TRUE,
            )
        );


        $registerForm = $this->_processRegisterForm($request);

        $loginForm = $this->_processLoginForm($request);
            
        $this->_processFBLogin($request);

	//-------------------VARABLE MENU

        
	if ($menu = $this->get('cache')->fetch('menu'.$lang)) {
	} 
	else {
        	$menu= Vocabulary::vocabularyList($lang); //VARIABLE A GUARDAR EN MEMCACHED
    		$this->get('cache')->save('menu'.$lang, $menu);
	}



        $menu['selected']=0;

	$products=CommerceUtils::getCartItemsAJAX();

	$title = constant("TITLE_SIZE_".strtoupper($lang)) ;	

        $response->setContent($this->renderView("CustomProjectBundle::size.html.twig", array(
            'lang' => $lang,
            'widget' => $widgets,
            'menu' => $menu,
            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
	    'products'=>$products,
            'orders'    	=> $orders,
	    'title' => $title,
	    'user'=>$user,

        )));
        return $response;        


    
    }
}


