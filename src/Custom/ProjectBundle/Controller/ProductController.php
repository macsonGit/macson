<?php

namespace Custom\ProjectBundle\Controller;

use Custom\ProjectBundle\Model\Vocabulary;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drufony\CoreBundle\Model\Product;
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


class ProductController extends DrufonyController
{
    public function indexAction($oid = null, $template = null, $lang,Request $request) {
        $response = new Response();
        $session     = getSession();
        $user        = $this->getUser();
        $uid         = NULL;
        $rememberme  = FALSE;

	$category=0;

        $product = new Product($oid, $lang);


	//$request= new Request();

        $comments = $product->getComments();
        if ($product->getCommentStatus() != Comment::COMMENT_STATUS_CLOSED) {
            $commentsForm = array();
            foreach ($comments as $comment) {
                $commentsForm[$comment->getCid()] =  $this->createForm(new CommentFormType(), array(
                    'node' => $product,
                    'destination' => $this->getRequest()->getUri(),
                    'pid' => $comment->getCid(),
                ))->createView();
            }
            $commentsForm['new'] = $this->createForm(new CommentFormType(), array(
                'node' => $product,
                'destination' => $this->getRequest()->getUri()
            ))->createView();
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


	if(strpos(!$product->getBrand(),'OUTLET')){
		if ($menu = $this->get('cache')->fetch('menu'.$lang)) {
		} 
		else {
        		$menu= Vocabulary::vocabularyList($lang); //VARIABLE A GUARDAR EN MEMCACHED
    			$this->get('cache')->save('menu'.$lang, $menu);
		}
		if ($menuList = $this->get('cache')->fetch('menuList'.$lang.'-'.$category)) {
		} 
		else {
			$menuList = Vocabulary::vocabularyListSelected($menu,$category);
    			$this->get('cache')->save('menuList'.$lang.'-'.$category, $menuList);
		}
	}
	else{
       


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


	}

	//-------------------VARABLE MENU LIST


        $menuList['selected']=$category;


        $numImages= array ('im1' => FALSE, 'im2' => FALSE, 'im3' => FALSE );

	$kernel = $this->get('kernel');

        $path = $kernel->locateResource('@CustomProjectBundle/Resources/public/images/Product/Standard/');
        $numImages['im1'] = file_exists($path.$product->__get('sgu').'_1.jpg');
        $numImages['im2'] = file_exists($path.$product->__get('sgu').'_2.jpg');
        $numImages['im3'] = file_exists($path.$product->__get('sgu').'_3.jpg');
	
	$products=CommerceUtils::getCartItemsAJAX();

	$response->setContent($this->renderView("CustomProjectBundle::${template}.html.twig", array(
            'lang' => $lang,
            'contentData' => $product,
            'widget' => $widgets,
            'comments' => $comments,
            'commentsCount' => $product->getCommentsCount(),
            'commentsForm' => isset($commentsForm) ? $commentsForm : null,
            'menu' => $menuList,
            'numImages' => $numImages,
            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            //'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
            'isLoginPath'   => FALSE,
 	    'products'=>$products,
	    'user'=>$user,
        )));
        return $response;
    }
}
