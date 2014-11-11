<?php

namespace Drufony\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drufony\CoreBundle\Model\ContentUtils;
use Drufony\CoreBundle\Form\ContactFormType;

class ContactController extends DrufonyController
{
    public function contactFormAction(Request $request, $lang) {
        $response = new Response();

        if($request->getMethod() == 'POST'){
            $success = ContentUtils::processContactForm($this, DEFAULT_EMAIL_ADDRESS, $request);

            if($success) {
                return $this->redirect($this->generateUrl('drufony_home_url', array('lang' => $lang)));
            }
        }
        $form = ContentUtils::getContactForm($this);
        $response->setContent($this->renderView('DrufonyCoreBundle::contactForm.html.twig',
                              array('lang' => $lang,
                                    'form' => $form->createView())));

        return $response;
    }
}
