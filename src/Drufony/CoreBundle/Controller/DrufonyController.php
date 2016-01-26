<?php

namespace Drufony\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Form\FormError;
use Drufony\CoreBundle\Form\RegisterFormType;
use Drufony\CoreBundle\Form\LoginFormType;
use Drufony\CoreBundle\Entity\User;
use Drufony\CoreBundle\Model\UserUtils;
use Drufony\CoreBundle\Model\Mailing;
use Drufony\CoreBundle\Model\Profile;

/**
 * This class extends from Controller and provides the base functionality for the project.
 */
class DrufonyController extends Controller
{
    //protected $request;
    protected $baseParams = array();

    /**
     * Inits base data.
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;

        /* These globals are used only in app_drufony. */
        global $db;
        $db = $this->get('database_connection');

        global $lang;
        $lang = $this->getRequest()->attributes->get('lang');

        global $router;
        $router = $this->get('router');

        global $templating;
        $templating = $this->container->get('templating');

        global $mailer;
        $mailer = $this->container->get('mailer');

        global $logger;
        $logger = $this->get('logger');

        global $session;
        $session = $this->get('session');
        $session->start();
	
        $session->set('stepId',$session->getId());



        global $securityContext;
        $securityContext = $this->container->get('security.context');
    }

    protected function _processRegisterForm(Request $request) {
        $termsUrl    = $this->generateUrl('conditions_and_terms');
        $registerForm = $this->createForm(new registerFormType(), array('termsReadOnly' => FALSE, 'termsUrl' => $termsUrl));
        $user         = NULL;
        $uid = null;

        if ($request->getMethod() == 'POST') {
            $registerForm->handleRequest($request);

            if ($registerForm->isValid()) {
                $data = $registerForm->getData();

                if (!UserUtils::getUidByEmail($data['email'])) {
                    $user = new User();
                    $encoder = $this->get('security.encoder_factory')->getEncoder($user);

                    unset($data['termsReadOnly']);
                    unset($data['termsUrl']);
                    unset($data['acceptTerms']);

                    $fullName = null;
                    if(isset($data['fullName'])) {
                        $fullName = $data['fullName'];
                        unset($data['fullName']);
                    }

                    $data['password'] = $encoder->encodePassword($data['password'], $user->getSalt());
                    $data['username'] = $data['email'];
                    $data['roles'] = array(User::ROLE_FOR_NEW_USERS);


                    $uid = User::save($data);
		    //Mailing::sendRegisterEmail($data['email']);

                    if ($uid && !is_null($fullName)) {
                        $profile = new Profile($uid);
                        $profile->__set('name', $fullName);

                        UserUtils::saveProfile($profile);
                    }
                }
                else {
                    $registerForm->get('email')->addError(new FormError(t('This email is already registered')));
                }
            }
        }

        $this->_processLoginLastStep($uid);

        return $registerForm;
    }

    protected function _processLoginForm(Request $request) {
        $user         = NULL;
        $uid         = NULL;
        $rememberme  = FALSE;
        $loginForm = $this->createForm(new loginFormType());

        if ($request->getMethod() == 'POST') {
            

	   $loginForm->handleRequest($request);
            if ($loginForm->isValid()) {
                $data = $loginForm->getData();
                $user = new User();
                $encoder = $this->get('security.encoder_factory')->getEncoder($user);
                $data['password'] = $encoder->encodePassword($data['password'], $user->getSalt());
                $userId = UserUtils::getUidByUsername($data['username']);
                if ($userId) {
                    $user = new User($userId);
                    if ($user->getPassword() == $data['password']) {
                        $uid = $userId;
                        if (!$data['rememberme']) {
                            $rememberme = TRUE;
                        }
                    }
                    else {
                        l(WARNING, 'Log in attempt for user ' . $user->getUsername() . ': bad credentials');
                        $loginForm->addError(new FormError(t('This email or password is invalid')));
                    }

                }
                else {
                    l(WARNING, 'Log in attempt for user ' . $data['username'] . ': user doesnt exist');
                    $loginForm->addError(new FormError(t('This email or password is invalid')));
                }
            }
        }

        $this->_processLoginLastStep($uid, $user, $rememberme);

        return $loginForm;
    }

    protected function _processFBLogin(Request $request) {
        $uid         = NULL;
        $user         = NULL;

        if ($request->query->get('code')) {
            $userData = UserUtils::getUserDataByFacebook($request->query->get('code'));
            if (!empty($userData)) {
                if (empty($userData['uid'])) {
                    //Register user
                    $password = Drupal::user_password();
                    $user = new User();
                    $encoder = $this->get('security.encoder_factory')->getEncoder($user);
                    $password = $encoder->encodePassword($password, $user->getSalt());
                    $userRecord = array(
                        'username' => $userData['email'],
                        'email' => $userData['email'],
                        'password' => $password,
                        'roles' => array(User::ROLE_FOR_NEW_USERS),
                    );
                    $uid = User::save($userRecord);
                }
                else {
                    $uid = $userData['uid'];
                }
            }
        }

        $this->_processLoginLastStep($uid);
    }

    private function _processLoginLastStep($uid, $user = null, $rememberme = false) {

        if ($uid) {

            if (is_null($user) || (is_object($user) && $user->isNull())) {
                $user = new User($uid);
            }
            // User login
            if (!$rememberme) {
                $token = new UsernamePasswordToken($user, $user->getPassword(), 'user', $user->getRoles());
            }
            else {
                $key = $this->container->getParameter('secret');
                $token = new RememberMeToken($user, 'user', $key);
            }
            $this->container->get('security.context')->setToken($token);
            $event = new InteractiveLoginEvent($this->getRequest(),$token);
            $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);

            UserUtils::updateLoginDate($user->getUid());
            l(INFO, 'User ' . $user->getUsername() . ' logged in successfully');
        }

    }

}

