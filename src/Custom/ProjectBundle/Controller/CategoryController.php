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



 
class CategoryController extends DrufonyController
{
    public function indexAction($lang,$category,$categorynames,Request $request)
    {
    
        $response = new Response();
        $session     = getSession();
        $user        = $this->getUser();
        $uid         = NULL;
        $rememberme  = FALSE;
	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
                              
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

	//-------------------VARABLE MENU LIST

	if ($menuList = $this->get('cache')->fetch('menuList'.$lang.'-'.$category)) {
	} 
	else {
		$menuList = Vocabulary::vocabularyListSelected($menu,$category);
    		$this->get('cache')->save('menuList'.$lang.'-'.$category, $menuList);
	}

        $menuList['selected']=$category;


	//-------------------VARABLE CATEGORYBALL


	if ($categoryBall = $this->get('cache')->fetch('categoryBall'.$lang.'-'.$category)) {
	} 
	else {
		$categoryBall = Vocabulary::getCategoryBall($category,$lang);
    		$this->get('cache')->save('categoryBall'.$lang.'-'.$category, $categoryBall);
	}


	$products=CommerceUtils::getCartItemsAJAX();

        $response->setContent($this->renderView("CustomProjectBundle::category.html.twig", array(
            'lang' => $lang,
            'widget' => $widgets,
            'menu' => $menuList,
            'ball' => $categoryBall,
            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
	    'products'=>$products,
            'orders'    	=> $orders,
	    'user'=>$user,

        )));
        return $response;        


    
    }

    public function outletcategoryAction($lang,$category,$categorynames,Request $request)
    {
    
        $response = new Response();
        $session     = getSession();
        $user        = $this->getUser();
        $uid         = NULL;
        $rememberme  = FALSE;
	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
                              
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

        
	if ($menu = $this->get('cache')->fetch('menuOutlet'.$lang)) {
	} 
	else {
        	$menu= Vocabulary::vocabularyList($lang, 'OUTLET'); //VARIABLE A GUARDAR EN MEMCACHED
    		$this->get('cache')->save('menuOutlet'.$lang, $menu);
	}

	//-------------------VARABLE MENU LIST

	if ($menuList = $this->get('cache')->fetch('menuListOutlet'.$lang.'-'.$category)) {
	} 
	else {
		$menuList = Vocabulary::vocabularyListSelected($menu,$category);
    		$this->get('cache')->save('menuListOutlet'.$lang.'-'.$category, $menuList);
	}

        $menuList['selected']=$category;


	//-------------------VARABLE CATEGORYBALL


	if ($categoryBall = $this->get('cache')->fetch('categoryBallOutlet'.$lang.'-'.$category)) {
	} 
	else {
		$categoryBall = Vocabulary::getCategoryBall($category,$lang,'OUTLET');
    		$this->get('cache')->save('categoryBallOutlet'.$lang.'-'.$category, $categoryBall);
	}


	$products=CommerceUtils::getCartItemsAJAX();

        $response->setContent($this->renderView("CustomProjectBundle::category.html.twig", array(
            'lang' => $lang,
            'widget' => $widgets,
            'menu' => $menuList,
            'ball' => $categoryBall,
            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
	    'products'=>$products,
	    'user'=>$user,
	    'orders'=>$orders,
	    'outlet'=>true,

        )));
        return $response;        


    
    }




    public function outletAction($lang,Request $request)
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

        
	if ($menu = $this->get('cache')->fetch('menuOutlet'.$lang)) {
	} 
	else {
        	$menu= Vocabulary::vocabularyList($lang,'OUTLET'); //VARIABLE A GUARDAR EN MEMCACHED
    		$this->get('cache')->save('menuOutlet'.$lang, $menu);
	}

	//-------------------VARABLE MENU LIST


	$menuList=$menu;


	//-------------------VARABLE CATEGORYBALL

	$products=CommerceUtils::getCartItemsAJAX();

        $response->setContent($this->renderView("CustomProjectBundle::outlet.html.twig", array(
            'lang' => $lang,
            'widget' => $widgets,
            'menu' => $menuList,
            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
	    'products'=>$products,
	    'user'=>$user,
            'orders'    	=> $orders,
	    'outlet'=>true,

        )));
        return $response;        


    
    }



    public function newsAction($lang,Request $request)
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

	//-------------------VARABLE MENU LIST


	if ($categoryBall = $this->get('cache')->fetch('categoryBallNews'.$lang)) {
	} 
	else {
		$categoryBall = Vocabulary::getCategoryBall(1,$lang,'NOVEDAD');
    		$this->get('cache')->save('categoryBallNews'.$lang, $categoryBall);
	}

	$menuList=$menu;

	//-------------------VARABLE CATEGORYBALL

	$products=CommerceUtils::getCartItemsAJAX();

        $response->setContent($this->renderView("CustomProjectBundle::category.html.twig", array(
            'lang' => $lang,
            'widget' => $widgets,
            'menu' => $menuList,
            'ball' => $categoryBall,
            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
	    'products'=>$products,
	    'user'=>$user,
	    'novedad'=>true,

        )));
        return $response;        


    
    }



    public function shoponlineAction($lang,Request $request)
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

	//-------------------VARABLE MENU LIST


	$menuList=$menu;

	//-------------------VARABLE CATEGORYBALL

	$products=CommerceUtils::getCartItemsAJAX();

        $response->setContent($this->renderView("CustomProjectBundle::shoponline.html.twig", array(
            'lang' => $lang,
            'widget' => $widgets,
            'menu' => $menuList,
            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
	    'products'=>$products,
            'orders'    	=> $orders,
	    'user'=>$user,

        )));
        return $response;        


    
    }
}


