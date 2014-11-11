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

        $menu= Vocabulary::vocabularyList($lang); //VARIABLE A GUARDAR EN MEMCACHED

        $menu = Vocabulary::vocabularyListSelected($menu,$category);

        $categoryBall = Vocabulary::getCategoryBall($category,$lang);

        $menu['selected']=$category;

	$products=CommerceUtils::getCartItemsAJAX();

        $response->setContent($this->renderView("CustomProjectBundle::category.html.twig", array(
            'lang' => $lang,
            'widget' => $widgets,
            'menu' => $menu,
            'ball' => $categoryBall,
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


