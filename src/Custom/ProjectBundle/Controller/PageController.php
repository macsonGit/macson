<?php

namespace Custom\ProjectBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Drufony\CoreBundle\Model\UserUtils;
use Drufony\CoreBundle\Model\Page;
use Drufony\CoreBundle\Entity\Comment;
use Drufony\CoreBundle\Controller\DrufonyController;
use Drufony\CoreBundle\Form\CommentFormType;
use Custom\ProjectBundle\Model\Vocabulary;
use Drufony\CoreBundle\Model\CommerceUtils;


class PageController extends DrufonyController
{
    public function indexAction($oid = null, $template = null, $lang,Request $request) {
	//var_dump($oid);


        $user        = $this->getUser();
        $uid         = NULL;
        $rememberme  = FALSE;

	$response = new Response();
        $page = new Page($oid, $lang);
	if($oid==17696){
		$template='home';
	}

            $registerForm = $this->_processRegisterForm($request);

            
                $loginForm = $this->_processLoginForm($request);
            

        $session = getSession();

        $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR,
            $session->get(SecurityContext::AUTHENTICATION_ERROR)
        );
            $this->_processFBLogin($request);
        $comments = $page->getComments();
        if ($page->getCommentStatus() != Comment::COMMENT_STATUS_CLOSED) {
            $commentsForm = array();

            foreach ($comments as $comment) {
                $commentsForm[$comment->getCid()] =  $this->createForm(new CommentFormType(), array(
                    'node' => $page,
                    'destination' => $this->getRequest()->getUri(),
                    'pid' => $comment->getCid(),
                ))->createView();
            }
            $commentsForm['new'] = $this->createForm(new CommentFormType(), array(
                'node' => $page,
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

	$menu=Vocabulary::vocabularyList($lang);
	//$menu= Vocabulary::vocabularyListSelected($menu,0);

	$products=CommerceUtils::getCartItemsAJAX();

        $response->setContent($this->renderView("CustomProjectBundle::${template}.html.twig", array(
            'lang' => $lang,
            'contentData' => $page,
            'widgets'  => $widgets,
            'comments' => $comments,
            'commentsCount' => $page->getCommentsCount(),
            'commentsForm' => isset($commentsForm) ? $commentsForm : null,
            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
	    'menu'=>$menu,
	    'products'=>$products,
	    'user'=>$user,
        )));

        return $response;
    }
}
