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



 
class StoreController extends DrufonyController
{
    public function indexAction($lang,Request $request)
    {
    
        $response = new Response();
        $session     = getSession();
        $user        = $this->getUser();
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

        $menu = Vocabulary::vocabularyListSelected($menu,0);

        $storeBall = Store::getStoreBall($lang);

        $menu['selected']=0;

	$products=CommerceUtils::getCartItemsAJAX();

        $response->setContent($this->renderView("CustomProjectBundle::store.html.twig", array(
            'lang' => $lang,
            'widget' => $widgets,
            'menu' => $menu,
            'ball' => $storeBall,
            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
	    'products'=>$products,
	    'user'=>$user,

        )));
        return $response;        


    
    }
}


