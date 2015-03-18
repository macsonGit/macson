<?php

namespace Drufony\CoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Drufony\CoreBundle\Entity\User;
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

class UserController extends DrufonyController
{
    function loginAction(Request $request, $lang) {
        $response = new Response();
        $session = getSession();

        $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR,
            $session->get(SecurityContext::AUTHENTICATION_ERROR)
        );
        $response->setContent($this->renderView('DrufonyCoreBundle::login.html.twig', array(
            'lastUsername' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
            'lang'          => $lang,
        )));

        return $response;
    }

    function loginRegisterAction(Request $request, $lang) {
        $response    = new Response();
        $session     = getSession();
        $user        = $this->getUser();
        $uid         = NULL;
	$rememberme  = FALSE;

        if (is_null($user)) {

            $registerForm = $this->_processRegisterForm($request);

            if (is_null($this->getUser())) {
                $loginForm = $this->_processLoginForm($request);
            }

            $this->_processFBLogin($request);

            if (!is_null($this->getUser())) {

                //Redirect to url which return 401 before, or homepage if user has entered in login
                //directly
                $urlLang = $lang;
                $profileLang = $this->getUser()->getLang();
                if (!empty($profileLang)) {
                    $urlLang = $profileLang;
                }

		$targetUrl = getSession()->get('_security.frontend.target_path', $this->generateUrl(PATH_AFTER_LOGIN, array('lang' => $urlLang)));

                return $this->redirect($targetUrl);
            }

            $response->setContent($this->renderView('DrufonyCoreBundle::base-registration.html.twig', array(
                'lang'          => $lang,
                'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
                'registerForm'  => $registerForm->createView(),
                'loginForm'     => $loginForm->createView(),
                'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
                'mainContent'   => 'DrufonyCoreBundle::login-register.html.twig',
            )));

            return $response;
        }
        else {
            return $this->redirect($this->generateUrl('drufony_profile_edit', array('lang' => $lang)));
        }
    }

    function listUsersAction(Request $request, $lang) {

        $response = new Response();
        $session = getSession();
        $user = $this->getUser();

        $tableRows = UserUtils::getUsersInfo();

        /* Set cols to show in table */
        $tableCols = array(
          'user' => array('name' => 'username', 'label' => t('User Name')),
          'email' => array('name' => 'email', 'label' => t('Email') ),
          'roles' => array('name' => 'role', 'label' => t('Roles') ),
        );

        $tableActions = array(
          'edit'    => array('label' => t('Edit user'), 'link' => 'drufony_profile_edit',
                             'op' => 'edit', 'icon' => 'fa fa-edit', 'id' => 'uid'),
          'delete'  => array('label' => t('Delete user'), 'link' => 'drufony_users_delete',
                             'op' => 'delete', 'icon' => 'fa fa-trash-o', 'id' => 'uid'),
        );

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_url'),
          'users' => array( 'label' => 'Users', 'url' => 'drufony_create_path'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig', array(
                'lang'          => $lang,
                'top-menu-bar'  => 'top-menu-bar.html.twig',
                'left'          => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'      => 'users',
                'dashboard'     => 'DrufonyCoreBundle::content_list_table.html.twig',
                'title'         => t('Users'),
                'titleSection'  => 'Users',
                'actionButtons' => '',
                'tableRows'     => $tableRows,
                'tableCols'     => $tableCols,
                'tableActions'  => $tableActions,
                'breadCrumb'    => $breadCrumb,
            )));

        return $response;
    }


    function newsletterAction(Request $request, $lang) {

        $response = new Response();
        $session = getSession();
        $user = $this->getUser();

        $tableRows = array(
            0 => array(
                'id'            => 1,
                'newsletter'    => 'newsletter1',
                'send'          => 1000,
                'open'          => '70%',
                'bounce'        => '16%',
            ),
            1 => array(
                'id'            => 2,
                'newsletter'    => 'newsletter2',
                'send'          => 1000,
                'open'          => '20%',
                'bounce'        => '26%',
            ),
            2 => array(
                'id'            => 3,
                'newsletter'    => 'newsletter3',
                'send'          => 1000,
                'open'          => '30%',
                'bounce'        => '66%',
            )
        );

        /* Set cols to show in table */
        $tableCols = array(
          'type' => array('name' => 'newsletter', 'label' => t('Newsletter'), 'link' => 'drufony_newsletter_list'),
          'user' => array('name' => 'send', 'label' => t('Send to') ),
          'open' => array('name' => 'open', 'label' => t('% Open') ),
          'bounce' => array('name' => 'bounce', 'label' => t('% Bounce') ),
        );

        $tableNewsletter = $this->renderView('DrufonyCoreBundle::defaultTable.html.twig', array(
            'tableRows'     => $tableRows,
            'tableCols'     => $tableCols,
            'lang'          => $lang,
            'tableClass'    => 'dynamicTable tableTools table table-striped checkboxs'
        ));

        $tableRows = array(
            0 => array(
                'id'                => 1,
                'list'              => '#22800',
                'num_suscribers'    => 1000,
                'bounce_rate'       => '16%',
            ),
            1 => array(
                'id'                => 2,
                'list'              => '#12800',
                'num_suscribers'    => 100,
                'bounce_rate'       => '16%',
            ),
            2 => array(
                'id'                => 3,
                'list'              => '#21800',
                'num_suscribers'    => 10,
                'bounce_rate'       => '16%',
            ),
            3 => array(
                'id'                => 4,
                'list'              => '#23800',
                'num_suscribers'    => 220,
                'bounce_rate'       => '36%',
            ),
        );

        /* Set cols to show in table */
        $tableCols = array(
          'type' => array('name' => 'list', 'label' => t('List'), 'link' => 'drufony_newsletter_list'),
          'user' => array('name' => 'num_suscribers', 'label' => t('Nº Suscribers') ),
          'open' => array('name' => 'bounce_rate', 'label' => t('% Bounce Rate') ),
        );

        $tableSuscribers = $this->renderView('DrufonyCoreBundle::defaultTable.html.twig', array(
            'tableRows'     => $tableRows,
            'tableCols'     => $tableCols,
            'lang'          => $lang,
            'tableClass'    => 'table'
        ));

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_url'),
          'newsletter' => array( 'label' => 'Newsletter', 'url' => 'drufony_newsletter_list'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig', array(
                'lang'              => $lang,
                'top-menu-bar'      => 'top-menu-bar.html.twig',
                'left'              => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'          => 'Newsletter',
                'dashboard'         => 'DrufonyCoreBundle::newsletter.html.twig',
                'title'             => t('Newsletter'),
                'tableNewsletter'   => $tableNewsletter,
                'tableSuscribers'   => $tableSuscribers,
                'breadCrumb'        => $breadCrumb,
                'viewRange'         => 'pending'
            )));

        return $response;
    }

    function profileAction(Request $request, $lang, $id = null) {
        $response = new Response();

            $user = $this->getUser();


        $profile = new Profile($user->getUid());
        $languages = Locale::getAllLanguages();

        $profileForm = $this->createForm(new ProfileFormType(), $profile);
        if ($request->getMethod() == 'POST') {
            $profileForm->handleRequest($request);

            if ($profileForm->isValid()) {
                $profileData = $profileForm->getData();


                UserUtils::saveProfile($profileData);

                $this->get('session')->getFlashBag()->add(
                    INFO,
                    t('Your changes in profile were saved!')
                );

                return $this->redirect($this->generateUrl('drufony_profile_edit', array('lang' => $lang, 'id' => $id)));
            }
            else {
                $this->get('session')->getFlashBag()->add(
                        ERROR,
                        t('Sorry, but your form was not processed! Please correct the following errors and submit the form again!')
                );
            }
        }
	$products=CommerceUtils::getCartItemsAJAX();
        $response->setContent($this->renderView('DrufonyCoreBundle::base-registration.html.twig', array(
            'lang'              => $lang,
            'languages'         => $languages,
            'user'              => $user,
            'id'                => $id,
            'profileForm'       => $profileForm->createView(),
            'itemConfigMenu'    => 'profile',
            'mainContent'       => 'DrufonyCoreBundle::profile.html.twig',
 	    'products'=>$products,
        )));

        return $response;
    }

    function yourOrderAction (Request $request, $lang){

	$response = new Response();
        $session     = getSession();
        $user        = $this->getUser();
        $rememberme  = FALSE;
	
            $user = $this->getUser();
	
	$orders = CommerceUtils::getUserOrders($user->getUid());

	$products=CommerceUtils::getCartItemsAJAX();

	$orderProducts=array();
            
	$registerForm = $this->_processRegisterForm($request);
            
        $loginForm = $this->_processLoginForm($request);

        $this->_processFBLogin($request);

	if(isset($id)){
		$orderProducts = CommerceUtils::getOrderProducts($id);
	}

        $response->setContent($this->renderView('DrufonyCoreBundle::base-registration.html.twig', array(
            'lang'              => $lang,
            'user'              => $user,
            'itemConfigMenu'    => 'yourOrder',
            'mainContent'       => 'DrufonyCoreBundle::yourOrder.html.twig',
 	    'products'=>$products,
 	    'orders'=>$orders,
 	    'orderProducts'=>$orderProducts,            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => FALSE 
        )));

        return $response;

    }

    function orderHistoryAction (Request $request, $lang ,$id=null){
	
	$response = new Response();
	
            $user = $this->getUser();
	
	$orders = CommerceUtils::getUserOrders($user->getUid());

	$products=CommerceUtils::getCartItemsAJAX();

	$orderProducts=array();

            $registerForm = $this->_processRegisterForm($request);

            
           $loginForm = $this->_processLoginForm($request);
            

            $this->_processFBLogin($request);
	if(isset($id)){
		$orderProducts = CommerceUtils::getOrderProducts($id);
		$order = CommerceUtils::getOrder($id);
	}

        $response->setContent($this->renderView('DrufonyCoreBundle::base-registration.html.twig', array(
            'lang'              => $lang,
            'user'              => $user,
            'id'                => $id,
            'itemConfigMenu'    => 'orders',
            'orders'    	=> $orders,
            'order'    		=> $order,
            'mainContent'       => 'DrufonyCoreBundle::orderList.html.twig',
            'fbLoginUrl'    => UserUtils::getFBUrlForLogin(),
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => $request->attributes->get('_route') == 'drufony_login' ? TRUE : FALSE,
 	    'products'=>$products,
 	    'orderProducts'=>$orderProducts,
        )));

        return $response;

    }

    public function createInvoiceAction($id,$lang)
    {
    	$facade = $this->get('ps_pdf.facade');
    	$response = new Response();
	$orderProducts = CommerceUtils::getOrderProducts($id);

	$order= CommerceUtils::getOrder($id);
	
	$billingInfo = unserialize($order['billingInfo']);

    	$this->render('DrufonyCoreBundle::invoice.pdf.twig', array(
		"id" 		=> $id,
		"orderProducts" => $orderProducts,	
		"order"		=> $order,
		"billingInfo"   => $billingInfo,
		"lang"		=> $lang
	), $response);

    	$xml = $response->getContent();


    	$content = $facade->render($xml);



    	return new Response($content, 200, array('content-type' => 'application/pdf'));
    }   


    function accountAction (Request $request, $lang, $id) {
        $response = new Response();

        $user = null;
        $isAdmin = false;
        if (is_null($id)) {
            $user = $this->getUser();
        }
        else if ($this->get('security.context')->isGranted(User::ROLE_ADMIN)) {
            $user = new User($id);
            $isAdmin = true;
        }
        else {
            throw new AccessDeniedException();
        }

        $languages = Locale::getAllLanguages();

        $accountForm = $this->createForm(new AccountFormType(), array('user' => $user, 'isAdmin' => $isAdmin));
        if ($request->getMethod() == 'POST') {
            $accountForm->handleRequest($request);
            if ($accountForm->isValid()) {
                $accountData = $accountForm->getData();
                //Check current password
                $encoder = $this->get('security.encoder_factory')->getEncoder($user);
                $encodedNewPassword = $encoder->encodePassword($accountData['newPassword'], $user->getSalt());
                $userRecord = array(
                    'uid' => $user->getUid(),
                    'password' => $encodedNewPassword,
                );
                $uid   = User::save($userRecord);
                if (!$isAdmin) {
                    //Load new user and login with this account
                    $user  = new User($uid);
                    $key   = $this->container->getParameter('secret');
                    $token = new UsernamePasswordToken($user, $user->getPassword(), 'user', $user->getRoles());
                    $this->container->get('security.context')->setToken($token);
                    $event = new InteractiveLoginEvent($this->getRequest(),$token);
                    $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
                    $this->get('session')->getFlashBag()->add(
                        INFO,
                        t('Your changes in account were saved!')
                    );
                }
            }
        }

	$products=CommerceUtils::getCartItemsAJAX();
        $response->setContent($this->renderView('DrufonyCoreBundle::base-registration.html.twig', array(
            'lang'              => $lang,
            'languages'         => $languages,
            'user'              => $user,
            'accountForm'       => $accountForm->createView(),
            'itemConfigMenu'    => 'account',
            'id'                => $id,
            'mainContent'       => 'DrufonyCoreBundle::account-edit.html.twig',
 	    'products'=>$products,
        )));

        return $response;
    }

    function deleteAccountAction (Request $request, $lang, $id) {
        $response = new Response();
        $user = $this->getUser();

        //Check if user is owner of this uid or current user is admin
        if ($user->getUid() == $id || $this->get('security.context')->isGranted(User::ROLE_ADMIN)) {
            UserUtils::deleteUser($id);
            $this->get('session')->getFlashBag()->add(
                INFO,
                t('This account has been removed')
            );
            $pathDestination = $request->query->get('destination');
            if (!is_null($pathDestination)) {
                return $this->redirect($this->generateUrl($pathDestination, array('lang' => $lang)));
            }
            else {
                return $this->redirect($this->generateUrl('drufony_users_list', array('lang' => $lang)));
            }
        }
        else {
            throw new AccessDeniedException();
        }
    }

    function requestRecoveryPasswordAction(Request $request, $lang) {
        $response = new Response();
        if (is_null($this->getUser())) {
            $forgotForm = $this->createForm(new ForgotPasswordFormType());
            if ($request->getMethod() == 'POST') {
                $forgotForm->handleRequest($request);
                if ($forgotForm->isValid()) {
                    $forgotData = $forgotForm->getData();
                    //Get user by email
                    if (UserUtils::getUidByEmail($forgotData['email'])) {
                        Mailing::sendForgotPassword($forgotData['email']);
                        //Inform user
                        $this->get('session')->getFlashBag()->add(
                            INFO,
                            t('Further instructions have been sent by mail, please check your email inbox')
                        );
                        return $this->redirect($this->generateUrl('drufony_home_url', array('lang' => $lang)));
                    }
                    else {
                        $forgotForm->get('email')->addError(new FormError(t('This email is not registered')));
                    }
                }
            }
            $response->setContent($this->renderView('DrufonyCoreBundle::forgotPassword.html.twig', array(
                'lang' => $lang,
                'forgotForm' => $forgotForm->createView(),
            )));
            return $response;
        }
        else {
            return $this->redirect($this->generateUrl('drufony_home_url', array('lang' => $lang)));
        }
    }

    public function recoveryPasswordAction(Request $request, $lang, $uid, $timestamp, $hash) {
        $response = new Response();
        $hashToValidate = sha1($uid . $timestamp . DRUFONY_SALT);
        if ($hashToValidate == $hash && !UserUtils::isForgotTokenUsed($hash, $uid)) {
            $user = new User($uid);
            $key   = $this->container->getParameter('secret');
            $token = new UsernamePasswordToken($user, $user->getPassword(), 'user', $user->getRoles());
            $this->container->get('security.context')->setToken($token);
            $event = new InteractiveLoginEvent($this->getRequest(),$token);
            $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
            //Show form to change password without current password
            $accountForm = $this->createForm(new AccountFormType(), array('user' => $user, 'isRecoveryPassword' => TRUE));
            if ($request->getMethod() == 'POST') {
                $accountForm->handleRequest($request);
                if ($accountForm->isValid()) {
                    $accountData = $accountForm->getData();
                    UserUtils::markAsUsedForgotToken($hash, $uid);
                    $encoder = $this->get('security.encoder_factory')->getEncoder($user);
                    $encodedNewPassword = $encoder->encodePassword($accountData['newPassword'], $user->getSalt());
                    $userRecord = array(
                        'uid' => $user->getUid(),
                        'password' => $encodedNewPassword,
                    );
                    $uid   = User::save($userRecord);
                    //Load new user and login with this account
                    $user  = new User($uid);
                    $key   = $this->container->getParameter('secret');
                    $token = new UsernamePasswordToken($user, $user->getPassword(), 'user', $user->getRoles());
                    $this->container->get('security.context')->setToken($token);
                    $event = new InteractiveLoginEvent($this->getRequest(),$token);
                    $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
                    return $this->redirect($this->generateUrl('drufony_profile_edit', array('lang' => $lang)));
                }
            }
            $response->setContent($this->renderView('DrufonyCoreBundle::changePassword.html.twig', array(
                'lang' => $lang,
                'accountForm' => $accountForm->createView(),
            )));
        }
        else {
            throw $this->createNotFoundException(t('Not found'));
        }

        return $response;
    }

    function profileAddressesAction(Request $request, $lang, $addressId = NULL, $id = null) {
        $response = new Response();

        $user = null;
        if (is_null($id)) {
            $user = $this->getUser();
        }
        else if ($this->get('security.context')->isGranted(User::ROLE_ADMIN)) {
            $user = new User($id);
        }
        else {
            throw new AccessDeniedException();
        }

        $languages = Locale::getAllLanguages();
        $addressId = $request->query->get('addressId');
        $profile = new Profile($user->getUid());
        $addresses = $profile->getAddresses();
        if ($addressId == NULL) {
            $addressLoaded = reset($addresses);
        }
        elseif ($addressId == 0) {
            $addressLoaded = array();
        }
        else {
            $addressLoaded = $profile->getAddress($addressId);
            if (empty($addressLoaded) && !empty($addresses)) {
                $addressLoaded = reset($addresses);
            }
        }
        $addressForm = $this->createForm(new BillingInfoFormType(), array('info' => $addressLoaded, 'isLoggedUser' => TRUE));
        if ($request->getMethod() == 'POST') {
            $addressForm->handleRequest($request);
            if ($addressForm->isValid()) {
                $addressData = $addressForm->getData();
                $addressData['uid'] = $user->getUid();
                if ($addressId) {
                    $addressData['id'] = $addressId;
                }
                unset($addressData['info']);
                unset($addressData['isLoggedUser']);
                $aid = UserUtils::saveAddress($addressData);
                $this->get('session')->getFlashBag()->add(
                    INFO,
                    t('Changes in address have been saved!')
                );

                return $this->redirect($this->generateUrl('drufony_profile_address_edit', array('lang'      => $lang,
                                                                                                'addressId' => ($addressId) ? $addressId : $aid,
                                                                                                'id'        => $id)));
            }
        }


	$products=CommerceUtils::getCartItemsAJAX();
        $response->setContent($this->renderView('DrufonyCoreBundle::base-registration.html.twig', array(
            'lang'              => $lang,
            'languages'         => $languages,
            'user'              => $user,
            'addresses'         => $addresses,
            'addressForm'       => $addressForm->createView(),
            'itemConfigMenu'    => 'address',
            'id'                => $id,
            'mainContent'       => 'DrufonyCoreBundle::addresses-edit.html.twig',
 	    'products'=>$products,
        )));

        return $response;
    }


    function profileAddressesDeleteAction(Request $request, $lang, $id = null) {

	UserUtils::removeAddress($id);

	var_dump($id);

	//$a=$a;

    	$response = $this->redirect($this->generateUrl('drufony_profile_address_edit', array(
        	'lang'  => $lang
    	)));

    // ... further modify the response or return it directly

    	return $response;


    }


    //TODO: place this function somewhere else
    public function provincesByCountryAction(Request $request) {
        $countryId = $request->request->get('countryId');
        $provinces = Geo::getProvincesNameByCountry($countryId);

        if(empty($provinces)) {
            $provinces = Geo::getRegionsNameByCountry($countryId);
        }

        $html = '';
        if(!empty($provinces)) {
            foreach($provinces as $id => $name) {
                $html .= "<option value='${id}'>${name}</option>";
            }
        }
        else {
            $html .= "<option value='0'>" . t("Not province/region found") . "</option>";
        }

        return new Response($html);
    }

    public function userPublicViewAction(Request $request, $uid) {
        $response = new Response();

        $currentUser = $this->getUser();

        $user = User::load($uid);
        if ($user->isNull()) {
            throw $this->createNotFoundException(t('Not found'));
        }

        $response->setContent($this->renderView('DrufonyCoreBundle::user-view.html.twig', array(
            'user'  => $user,
            'currentUser' => $currentUser,
            'isFollowing' => is_null($currentUser) ? FALSE : UserUtils::isFollowing($currentUser->getUid(), $user->getUid()),
        )));

        return $response;
    }

    public function followAction(Request $request, $action, $uid) {
        $response = new Response();

        $currentUser = $this->getUser();
        $user = User::load($uid);
        if ($user->isNull() || is_null($currentUser)) {
            $response->setContent(json_encode(array(
                'status' => 'error',
            )));
        }
        else {
            if ($action == 'add') {
                UserUtils::addFollowing($currentUser->getUid(), $user->getUid());
            }
            else {
                UserUtils::removeFollowing($currentUser->getUid(), $user->getUid());
            }
            $response->setContent(json_encode(array(
                'status' => 'ok',
            )));
        }
        $response->headers->set('Content-type', 'application/json');

        return $response;
    }
}
