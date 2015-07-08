<?php

namespace Drufony\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drufony\CoreBundle\Model\ContentUtils;
use Drufony\CoreBundle\Form\ContactFormType;
use Drufony\CoreBundle\Model\CommerceUtils;

use Drufony\CoreBundle\Entity\User;
use Drufony\CoreBundle\Model\UserUtils;


class ContactController extends DrufonyController
{
    public function contactFormAction(Request $request, $lang, $contactType) {
        $response = new Response();

        $user        = $this->getUser();
	$orders ='';
        if($request->getMethod() == 'POST'){

            $contactForm = new ContactFormType();
            $form        = $this->createForm($contactForm, array());

            $form->handleRequest($request);

            $formData = $form->getData();
            
            if($formData['contactType'] == 'customer'){
            	$success = ContentUtils::processContactForm($this, COMUNICACION_EMAIL_ADDRESS, $request,$lang);
	    }
            if($formData['contactType'] == 'hr'){
            	$success = ContentUtils::processContactForm($this, HR_EMAIL_ADDRESS, $request,$lang);
	    }
            if($formData['contactType'] == 'franchises'){
            	$success = ContentUtils::processContactForm($this, FRANCHISES_EMAIL_ADDRESS, $request,$lang);
	    }
            if($formData['contactType'] == 'uniforms'){
            	$success = ContentUtils::processContactForm($this, UNIFORMS_EMAIL_ADDRESS, $request,$lang);
	    }
 
            $success = ContentUtils::processContactForm($this, DEFAULT_EMAIL_ADDRESS, $request,$lang);

            if($success) {
                return $this->redirect($this->generateUrl('drufony_home_url', array('lang' => $lang)));
            }


        }
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
        $registerForm = $this->_processRegisterForm($request);

        $loginForm = $this->_processLoginForm($request);
            
        $this->_processFBLogin($request);
        $form = ContentUtils::getContactForm($this);
	$products=CommerceUtils::getCartItemsAJAX();
        $response->setContent($this->renderView('DrufonyCoreBundle::contactForm.html.twig',
                              array('lang' => $lang,
                                    'form' => $form->createView(),
            			    'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            			    'registerForm'  => $registerForm->createView(),
            			    'loginForm'     => $loginForm->createView(),
				    'contactType'   => $contactType,
            			    'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
	    			    'products'	    =>$products,
            			    'orders'        => $orders,
			)));
				
        return $response;
    }
}
