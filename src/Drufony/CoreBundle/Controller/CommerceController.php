<?php

namespace Drufony\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Form\FormError;

use Drufony\CoreBundle\Model\Order;
use Drufony\CoreBundle\Model\CommerceUtils;
use Drufony\CoreBundle\Model\Product;
use Drufony\CoreBundle\Model\UserUtils;
use Drufony\CoreBundle\Model\TPV;
use Drufony\CoreBundle\Model\Mailing;
use Drufony\CoreBundle\Model\Drupal;
use Drufony\CoreBundle\Model\Profile;
use Drufony\CoreBundle\Model\PaypalUtils;
use Drufony\CoreBundle\Entity\User;
use Drufony\CoreBundle\Exception\StripeException;
use Drufony\CoreBundle\Form\BillingInfoFormType;
use Drufony\CoreBundle\Form\ShippingMethodFormType;
use Drufony\CoreBundle\Form\PaymentMethodFormType;
use Drufony\CoreBundle\Form\LoginFormType;
use Drufony\CoreBundle\Form\RegisterFormType;
use Drufony\CoreBundle\Form\SelectPaymentMethodFormType;
use Drufony\CoreBundle\Form\SermepaPaymentFormType;

defined('CHECKOUT_METHOD_NAME') or define('CHECKOUT_METHOD_NAME', 'checkoutMethod');
defined('BILLING_INFO_NAME') or define('BILLING_INFO_NAME', 'billingInformation');
defined('SHIPPING_INFO_NAME') or define('SHIPPING_INFO_NAME', 'shippingInformation');
defined('SHIPPING_METHOD_NAME') or define('SHIPPING_METHOD_NAME', 'shippingMethod');
defined('PAYMENT_METHOD_NAME') or define('PAYMENT_METHOD_NAME', 'paymentMethod');
defined('SELECT_PAYMENT_METHOD_NAME') or define('SELECT_PAYMENT_METHOD_NAME', 'selectPaymentMethod');


class CommerceController extends DrufonyController
{

  public function indexAction(Request $request, $lang) {

      $response = new Response();

      $response->setContent($this->renderView('CustomProjectBundle::base-commerce.html.twig',
        array('lang'=> $lang,
              'mainContent' => 'DrufonyCoreBundle::viewCart.html.twig',
        )
      ));

      return $response;
  }
    public function addToCartAction(Request $request, $lang, $product, $value) {
        CommerceUtils::addToCart($product, $value);

        return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
    }

    public function removeFromCartAction(Request $request, $lang, $product) {
        CommerceUtils::removeFromCart($product);
        return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
    }

    public function updateCartAction(Request $request, $lang, $product, $value) {
        if($value > 0) {
            CommerceUtils::updateCart($product, $value);
        }
        else {
            CommerceUtils::removeFromCart($product);
        }
        return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
    }

    public function viewCartAction(Request $request, $lang) {
        $response = new Response();

        $existsCoupon= CommerceUtils::existStep(COUPON);
        $couponCodeSaved = '';
        if($existsCoupon) {
            $couponSaved = CommerceUtils::getStepData(COUPON);
            $couponCodeSaved = $couponSaved['couponCode'];
        }

        $couponCode = $request->query->get('couponCode');
        #If we recieve a coupon
        if(!is_null($couponCode) && !empty($couponCode)) {

            #Check it's a diferent code
            if($couponCode != $couponCodeSaved) {
                $couponStatus = CommerceUtils::getCouponStatus($couponCode);
                $coupon = CommerceUtils::getCouponByCode($couponCode);
                $startDate = ($couponStatus == COUPON_NONACTIVE) ? $coupon['startDate'] : null;
                $message = CommerceUtils::getCouponStatusMessage($couponStatus, $startDate);

                $messageType = INFO;
                #Act according coupon status
                if($couponStatus != COUPON_VALID) {
                    $messageType = ERROR;
                    if($existsCoupon) {
                        $couponCodeSaved = '';
                        CommerceUtils::deleteStep(COUPON);
                    }
                }
                else {
                    $this->get('session')->getFlashBag()->add($messageType, $message);
		    CommerceUtils::saveStep(COUPON, array('couponCode' => $couponCode), $existsCoupon);
                    return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
                }
                $this->get('session')->getFlashBag()->add($messageType, $message);
            }
            #If its the same code stored, go to the next checkout step
            else {
                return $this->redirect($this->generateUrl('drufony_checkout_login', array('lang' => $lang)));
            }
        }
        #If recieve an empty coupon, delete stored one if exists
        else if(!is_null($couponCode) && empty($couponCode)){
            if($existsCoupon) {
                $this->get('session')->getFlashBag()->add(INFO, 'Coupon removed');
                CommerceUtils::deleteStep(COUPON);
                return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
            }
            else {
                return $this->redirect($this->generateUrl('drufony_checkout_login', array('lang' => $lang)));
            }
        }

        $cartInfo = CommerceUtils::getCartInfo();

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'ecommerce' => array( 'label' => 'Home', 'url' => 'commerce_home_path'),
          'shoping-cart' => array( 'label' => 'Shoping cart', 'url' => 'drufony_cart_add'),
        );

        $registerForm = $this->_processRegisterForm($request);
	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
           
        $loginForm = $this->_processLoginForm($request);
	$products=CommerceUtils::getCartItemsAJAX();
        $response->setContent($this->renderView('CustomProjectBundle::base-commerce.html.twig',
                                                array('lang'=>$lang,
                                                    'subtotal' => $cartInfo['subtotalProducts'],
                                                    'discount' => $cartInfo['discount'],
                                                    'couponDiscount' => $cartInfo['couponDiscount'],
                                                    'discountType' => $cartInfo['discountType'],
                                                    'tax' => $cartInfo['taxProducts'],
						    'items' => $cartInfo['cartItems'],
                                                    'itemsCount' => $cartInfo['itemsCount'],
                                                    'total' => $cartInfo['subtotalProducts'],
						    'totalDiscounted' => $cartInfo['totalDiscounted'],
                                                    'edit' => true,
                                                    'couponCode' => $couponCodeSaved,
                                                    'pageTitle' => t('View Cart'),
                                                    'mainContent' => 'DrufonyCoreBundle::viewCart.html.twig',
                                                    'breadCrumb' => $breadCrumb,
						    'products'=>$products,
						     'orders'=>$orders,
            					    'registerForm'  => $registerForm->createView(),
            					    'loginForm'     => $loginForm->createView(),
            					    'isLoginPath'   => FALSE,
                                                  )
                                                ));
        return $response;
    }

    //FIXME: refactor login code accoding to UserController
    public function checkoutLoginAction(Request $request, $lang, $withoutlogin = false) {
        $session    = getSession();
        $session->set('stepId',$session->getId());

        $response = new Response();
        $user = $this->getUser();
        $uid = null;
        $rememberme = FALSE;

        $updateStep = CommerceUtils::existStep(CHECKOUT_METHOD);

        if($withoutlogin == true) {
            $data = array('logged' => false);
	    CommerceUtils::saveStep(CHECKOUT_METHOD, $data, $updateStep);
            return $this->redirect($this->generateUrl('drufony_checkout_shipping_info', array('lang' => $lang)));
        }

        if(CommerceUtils::getCartItemsCount() == 0) {
            return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
        }

        $userLogged = False;
        if(!is_null($user)) {
            $userLogged = True;
            $data = array('logged' => true);
            CommerceUtils::saveStep(CHECKOUT_METHOD, $data, $update = $updateStep);
            return $this->redirect($this->generateUrl('drufony_checkout_load_previous_info', array('lang' => $lang)));
        }

        $loginForm = $this->createForm(new loginFormType());
        if($request->getMethod() == 'POST') {
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
        if ($uid) {
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

            $data = array('logged' => true);

	    CommerceUtils::saveStep(CHECKOUT_METHOD, $data, $updateStep);

            $urlLang = $lang;
            if (!is_null($this->getUser())) {

                $profileLang = $this->getUser()->getLang();
                if (!empty($profileLang)) {
                    $urlLang = $profileLang;
                }
            }

	    if($user->getEmail()=='gencat@macson.es'){
		 return $this->redirect($this->generateUrl('drufony_gencat', array('lang' => $urlLang)));
	    }


            return $this->redirect($this->generateUrl('drufony_checkout_load_previous_info', array('lang' => $urlLang)));



        }

        $checkoutProgress = CommerceUtils::getCheckoutProgress();

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'ecommerce' => array( 'label' => 'Home', 'url' => 'commerce_home_path'),
        );

        $registerForm = $this->_processRegisterForm($request);
           
	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
        $loginForm = $this->_processLoginForm($request);
	$products=CommerceUtils::getCartItemsAJAX();
        $response->setContent($this->renderView('CustomProjectBundle::base-commerce.html.twig', array(
            'lang'        => $lang,
            'fbLoginUrl'  => UserUtils::getFBUrlForLogin(),
            'form'   => $loginForm->createView(),
            'progress'    => $checkoutProgress,
            'mainContent' => 'DrufonyCoreBundle::checkout_default_template.html.twig',
            'checkoutStep' => CHECKOUT_METHOD_NAME,
            'userLogged' => $userLogged,
            'checkoutMethodCompleted'=> CommerceUtils::existStep(CHECKOUT_METHOD),
            'breadCrumb'  => $breadCrumb,
	    'products' => $products,
	    'orders'=>$orders,
            'registerForm'  => $registerForm->createView(),
            'loginForm'     => $loginForm->createView(),
            'isLoginPath'   => FALSE,
        )));

	$products=CommerceUtils::getCartItemsAJAX();
        return $response;
    }

    public function checkoutLoadPreviousInfoAction(Request $request, $lang) {
	    $user = $this->getUser();
	    $userAddresses = array();

	    if(CommerceUtils::getCartItemsCount() == 0) {
		    return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
	    }

	    if(!is_null($user)) {
		    $profile = new Profile($user->getUid());
		    $userAddresses = $profile->getAddresses();
		    $existBilling = false;
		    $existShipping = false;

		    //Load user address
		    if($userAddresses) {
			    //TODO: replace this with default address once its been implemented
			    $latestAddressId = max(array_keys($userAddresses));
			    $latestAddress = $profile->getAddress($latestAddressId);

			    $existBilling = CommerceUtils::existStep(BILLING_INFO);
			    $existShipping = CommerceUtils::existStep(SHIPPING_INFO);
			    $latestAddress['email'] = $user->getEmail();

			    if(!$existBilling) {
				    CommerceUtils::saveStep(BILLING_INFO, $latestAddress);
				    $existBilling = true;
			    }
			    if(!$existShipping) {
				    CommerceUtils::saveStep(SHIPPING_INFO, $latestAddress);
				    $existShipping = true;
			    }
		    }


		    $existCheckoutMethod = CommerceUtils::existStep(CHECKOUT_METHOD);
		    if($existBilling && $existShipping) {
			    if(!$existCheckoutMethod) {
				    return $this->redirect($this->generateUrl('drufony_checkout_login' ,array('lang' => $lang)));
			    }
			    else {
				    return $this->redirect($this->generateUrl('drufony_checkout_shipping_method' ,array('lang' => $lang)));
			    }
		    }
		    else {
			    return $this->redirect($this->generateUrl('drufony_checkout_shipping_info' ,array('lang' => $lang)));
		    }
	    }

	    return $this->redirect($this->generateUrl('drufony_checkout_login' ,array('lang' => $lang)));
    }

    public function checkoutBillingInfoAction(Request $request, $lang) {
        $response = new Response();
        $checkoutMethodCompleted = CommerceUtils::existStep(CHECKOUT_METHOD);

        if(CommerceUtils::getCartItemsCount() == 0) {
            return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
        }
        else if(!$checkoutMethodCompleted) {
            //$this->get('session')->getFlashBag()->add(INFO, t('Please, complete checkout method before continue'));
            return $this->redirect($this->generateUrl('drufony_checkout_login', array('lang' => $lang)));
        }

        $formFields = null;
        if(CommerceUtils::existStep(BILLING_INFO)) {
            $formFields = CommerceUtils::getStepData(BILLING_INFO);
        }

        $existsShipping = CommerceUtils::existStep(SHIPPING_INFO);
        if($existsShipping && !$formFields) {
            $formFields = CommerceUtils::getStepData(SHIPPING_INFO);
        }

        $billingForm = $this->createForm(new BillingInfoFormType(), array('info' => $formFields));
        if($request->getMethod() == 'POST') {
            $billingForm->handleRequest($request);

            if($billingForm->isValid()) {
                $data = $billingForm->getData();

                if(array_key_exists('info', $data)) {
                    unset($data['info']);
                }

                $updateStep = CommerceUtils::existStep(BILLING_INFO);
		CommerceUtils::saveStep(BILLING_INFO, $data, $updateStep);

                l(INFO, 'Billing info saved succesfully');

                return $this->redirect($this->generateUrl('drufony_checkout_shipping_method', array('lang' => $lang)));
            }
        }

        $user = $this->getUser();
        $userAddresses = array();
        $userLogged = False;
        if(!is_null($user)) {
            $profile = new Profile($user->getUid());
            $userAddresses = $profile->getAddresses();
            $userLogged = True;
        }

        $checkoutProgress = CommerceUtils::getCheckoutProgress();

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'ecommerce' => array( 'label' => 'Home', 'url' => 'commerce_home_path'),
        );

	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
        $registerForm = $this->_processRegisterForm($request);
           
        $loginForm = $this->_processLoginForm($request);
	$products=CommerceUtils::getCartItemsAJAX();
        $response->setContent($this->renderView('CustomProjectBundle::base-commerce.html.twig',
                                                array('form'  => $billingForm->createView(),
                                                'lang'        => $lang,
                                                'shipping'    => false,
                                                'addresses'   => $userAddresses,
                                                'progress'    => $checkoutProgress,
                                                'userLogged'  => $userLogged,
                                                'checkoutMethodCompleted'=> $checkoutMethodCompleted,
                                                'existsShipping' => $existsShipping,
                                                'mainContent' => 'DrufonyCoreBundle::checkout_default_template.html.twig',
                                                'checkoutStep' => BILLING_INFO_NAME,
                                                'breadCrumb'  => $breadCrumb,
						'products' => $products,
						'orders'=>$orders,
            					'registerForm'  => $registerForm->createView(),
            					'loginForm'     => $loginForm->createView(),
            					'isLoginPath'   => FALSE,
                                              )
                                            ));
        return $response;
    }

    public function checkoutBillingUserAddressAction(Request $request, $lang, $addressId) {

        if(CommerceUtils::getCartItemsCount() == 0) {
            return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
        }

        $user = $this->getUser();

        $profile = new Profile($user->getUid());
        $addressInfo = $profile->getAddress($addressId);

        $billingInfo = $addressInfo;
        $billingInfo['email'] = $user->getUsername();

        $update = CommerceUtils::existStep(BILLING_INFO);
        CommerceUtils::saveStep(BILLING_INFO, $billingInfo, $update);

        l(INFO, 'Address ' . $addressId . ' as billing info for user ' . $user->getUid() . ' saved succesfully');

        return $this->redirect($this->generateUrl('drufony_checkout_billing_info', array('lang' => $lang)));
    }

    public function checkoutBillingUseShippingAction(Request $request, $lang) {

        if(CommerceUtils::getCartItemsCount() == 0) {
            return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
        }

        $update = CommerceUtils::existStep(BILLING_INFO);
        $shippingInfo = CommerceUtils::getStepData(SHIPPING_INFO);
        CommerceUtils::saveStep(BILLING_INFO, $shippingInfo, $update);

        l(INFO, 'Billing info saved as shipping info succesfully');

        return $this->redirect($this->generateUrl('drufony_checkout_billing_info', array('lang' => $lang)));
    }

    public function checkoutShippingInfoAction(Request $request, $lang) {
        $response = new Response();
        $checkoutMethodCompleted = CommerceUtils::existStep(CHECKOUT_METHOD);

        if(CommerceUtils::getCartItemsCount() == 0) {
            return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
        }
        else if(!$checkoutMethodCompleted) {
            //$this->get('session')->getFlashBag()->add(ERROR, t('Please, complete checkout method before continue'));
            return $this->redirect($this->generateUrl('drufony_checkout_login', array('lang' => $lang)));
        }

        $formFields = null;
        if(CommerceUtils::existStep(SHIPPING_INFO)) {
            $formFields = CommerceUtils::getStepData(SHIPPING_INFO);
        }

        $existsBilling = CommerceUtils::existStep(BILLING_INFO);
        if($existsBilling && !$formFields) {
            $formFields = CommerceUtils::getStepData(BILLING_INFO);
        }

        $shippingForm = $this->createForm(new BillingInfoFormType(), array('info' => $formFields, 'notNif' => true));

        if($request->getMethod() == 'POST') {
            $shippingForm->handleRequest($request);
            if($shippingForm->isValid()) {
                $data = $shippingForm->getData();

                if(array_key_exists('info', $data)) {
                    unset($data['info']);
                }
                unset($data['notNif']);

                $updateStep = CommerceUtils::existStep(SHIPPING_INFO);
                CommerceUtils::saveStep(SHIPPING_INFO, $data, $updateStep);

                l(INFO, 'Shipping info saved successfully');

                return $this->redirect($this->generateUrl('drufony_checkout_billing_info', array('lang' => $lang)));
            }
        }

        $user = $this->getUser();
        $userAddresses = array();
        $userLogged = false;
        if(!is_null($user)) {
            $profile = new Profile($user->getUid());
            $userAddresses = $profile->getAddresses();
            $userLogged = true;
        }

        $checkoutProgress = CommerceUtils::getCheckoutProgress();

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'ecommerce' => array( 'label' => 'Home', 'url' => 'commerce_home_path'),
        );

	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
        $registerForm = $this->_processRegisterForm($request);
           
        $loginForm = $this->_processLoginForm($request);
	$products=CommerceUtils::getCartItemsAJAX();
        $response->setContent($this->renderView('CustomProjectBundle::base-commerce.html.twig',
                                                array('form'  => $shippingForm->createView(),
                                                'lang'        => $lang,
                                                'addresses'   => $userAddresses,
                                                'shipping'    => true,
                                                'progress'    => $checkoutProgress,
                                                'userLogged'  => $userLogged,
                                                'checkoutMethodCompleted' => $checkoutMethodCompleted,
                                                'mainContent' => 'DrufonyCoreBundle::checkout_default_template.html.twig',
                                                'checkoutStep' => SHIPPING_INFO_NAME,
                                                'breadCrumb'  => $breadCrumb,
						'products'=>$products,
						'orders'=>$orders,
            					'registerForm'  => $registerForm->createView(),
            					'loginForm'     => $loginForm->createView(),
            					'isLoginPath'   => FALSE,
                                              )
                                            ));
        return $response;
    }

    public function checkoutShippingUserAddressAction(Request $request, $lang, $addressId) {

        if(CommerceUtils::getCartItemsCount() == 0) {
            return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
        }

        $user = $this->getUser();

        $profile = new Profile($user->getUid());
        $addressInfo = $profile->getAddress($addressId);

        $shippingInfo = $addressInfo;
        $shippingInfo['email'] = $user->getUsername();
        unset($shippingInfo['nif']);

        $update = CommerceUtils::existStep(SHIPPING_INFO);
        CommerceUtils::saveStep(SHIPPING_INFO, $shippingInfo, $update);

        l(INFO, 'Address ' . $addressId . ' as shipping info for user ' . $user->getUid() . ' saved succesfully');

        return $this->redirect($this->generateUrl('drufony_checkout_shipping_info', array('lang' => $lang)));
    }

    public function checkoutShippingMethodAction(Request $request, $lang) {
        $response = new Response();
        $checkoutMethodCompleted = CommerceUtils::existStep(CHECKOUT_METHOD);
        $countryId = null;

        if(CommerceUtils::getCartItemsCount() == 0) {
            return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
        }
        else if (!$checkoutMethodCompleted) {
            //$this->get('session')->getFlashBag()->add(INFO, t('Please, complete checkout method before continue'));
            return $this->redirect($this->generateUrl('drufony_checkout_login', array('lang' => $lang)));
        }
        $info = null;
        if (SHIPPING_FEE_ENABLED == SHIPPING_FEE_BY_COUNTRY && !CommerceUtils::existStep(SHIPPING_INFO)) {
            $this->get('session')->getFlashBag()->add(INFO, t('Please, complete shipping info before continue'));
            return $this->redirect($this->generateUrl('drufony_checkout_shipping_info', array('lang' => $lang)));
        }
        else if (SHIPPING_FEE_ENABLED == SHIPPING_FEE_BY_COUNTRY) {
            $shipping = CommerceUtils::getStepData(SHIPPING_INFO);
            $cartWeight = CommerceUtils::getCartWeight();
            $shippingPrice = CommerceUtils::getShippingCostByCountry($shipping['countryId'], $cartWeight);
            $info = t("@price @symbol for @weight grams", array('@price' => $shippingPrice, '@symbol' => DEFAULT_CURRENCY_SYMBOL, '@weight' => $cartWeight));
        }

        $formFields = null;
        if(CommerceUtils::existStep(SHIPPING_METHOD)) {
            $formFields = CommerceUtils::getStepData(SHIPPING_METHOD);
        }
        $shippingMethodForm = $this->createForm(new ShippingMethodFormType(), array('info' => $formFields, 'countryId' => $countryId));
        if ($request->getMethod = 'POST') {
            $shippingMethodForm->handleRequest($request);
            if ($shippingMethodForm->isValid()) {
                $data = $shippingMethodForm->getData();

                if (array_key_exists('info', $data)) {
                    unset($data['info']);
                }

                $updateStep = CommerceUtils::existStep(SHIPPING_METHOD);
                CommerceUtils::saveStep(SHIPPING_METHOD, $data, $updateStep);


                l(INFO, 'Shipping method saved successfully');
		

		if (!empty($data['discountCoupon']) && !$updateStep    ){
                	return $this->redirect($this->generateUrl('drufony_checkout_shipping_method', array('lang' => $lang)));
		
		}

		else{

                	return $this->redirect($this->generateUrl('drufony_checkout_review_payment', array('lang' => $lang)));

		}

            }
        }

        $userLogged = !is_null($this->getUser());

        $checkoutProgress = CommerceUtils::getCheckoutProgress();

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'ecommerce' => array( 'label' => 'Home', 'url' => 'commerce_home_path'),
        );

	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
        $registerForm = $this->_processRegisterForm($request);
           
        $loginForm = $this->_processLoginForm($request);
	$products=CommerceUtils::getCartItemsAJAX();
        $response->setContent($this->renderView('CustomProjectBundle::base-commerce.html.twig',
                                                array('form' => $shippingMethodForm->createView(),
                                                'lang' => $lang,
                                                'progress'    => $checkoutProgress,
                                                'userLogged'  => $userLogged,
                                                'checkoutMethodCompleted'=> $checkoutMethodCompleted,
                                                'mainContent' => 'DrufonyCoreBundle::checkout_default_template.html.twig',
                                                'checkoutStep' => SHIPPING_METHOD_NAME,
                                                'breadCrumb' => $breadCrumb,
						'info' => $info,
						'products' => $products,
						'orders'=>$orders,
            					'registerForm'  => $registerForm->createView(),
            					'loginForm'     => $loginForm->createView(),
            					'isLoginPath'   => FALSE,
						'shippingPrice' =>$shippingPrice,
                                                )
                                              ));
        return $response;
    }


    //TODO: check if user already has credit card or token
    //FIXME: prepare code to diferent payment methods
    public function checkoutReviewAndPaymentAction(Request $request, $lang) {
        $response = new Response();

	

       $checkoutMethodCompleted = CommerceUtils::existStep(CHECKOUT_METHOD);
        //Check checkouk it's completed
        list($message, $target) = CommerceUtils::checkOrderStatus();
        if(!is_null($message)) {
            $this->get('session')->getFlashBag()->add(ERROR, $message);
            return $this->redirect($this->generateUrl($target, array('lang' => $lang)));
        }
	$paymentMethodForm = $this->createForm(new SelectPaymentMethodFormType());

        if ($request->getMethod() == 'POST') {
            $paymentMethodForm->handleRequest($request);
            if ($paymentMethodForm->isValid()) {
                $data = $paymentMethodForm->getData();
                $user = $this->getUser();
                //Register user if is not registered
                if(is_null($user)) {
                    $shipping = CommerceUtils::getStepData(SHIPPING_INFO);
                    $user = $this->__registerUser($shipping, $lang);
                    if (is_null($user)) {
                        //$this->get('session')->getFlashBag()->add(ERROR, t('This email already exists'));
                        //return $this->redirect($this->generateUrl('drufony_checkout_shipping_info', array('lang' => $lang)));
                    }
                }

                $existPaymentStep = CommerceUtils::existStep(PAYMENT_METHOD);
                $nextAction = null;

               if ($data['method'] == TPV_STRIPE) {
                    CommerceUtils::saveStep(PAYMENT_METHOD, array('payment' => TPV_STRIPE_TYPE, 'name' => TPV_STRIPE), $existPaymentStep);
                    $nextAction = $this->redirect($this->generateUrl('drufony_payment_stripe', array('request' => $request, 'lang' => $lang)));
                }
                else if ($data['method'] == TPV_SERMEPA) {
                    CommerceUtils::saveStep(PAYMENT_METHOD, array('payment' => TPV_SERMEPA_TYPE, 'name' => TPV_SERMEPA), $existPaymentStep);
                    $nextAction = $this->forward('DrufonyCoreBundle:Commerce:sermepaPayment', array('request' => $request, 'lang' => $lang));
                }
                else if ($data['method'] == TPV_PAYPAL) {
                    CommerceUtils::saveStep(PAYMENT_METHOD, array('payment' => TPV_PAYPAL_TYPE, 'name' => TPV_PAYPAL), $existPaymentStep);
                    $nextAction = $this->forward('DrufonyCoreBundle:Commerce:paypalPayment', array('request' => $request, 'lang' => $lang));
                }

                if (!is_null($nextAction)) {
                    $this->__saveOrder();

                    return $nextAction;
                
                }
            }
        }

        $userLogged = !is_null($this->getUser());

        $checkoutProgress = CommerceUtils::getCheckoutProgress();

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'ecommerce' => array( 'label' => 'Home', 'url' => 'commerce_home_path'),
        );

	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
	
	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
	$products=CommerceUtils::getCartItemsAJAX();

        $registerForm = $this->_processRegisterForm($request);
           
        $loginForm = $this->_processLoginForm($request);


        $response->setContent($this->renderView('CustomProjectBundle::base-commerce.html.twig',
                                                array('lang' => $lang,
                                                'progress'    => $checkoutProgress,
                                                'userLogged'  => $userLogged,
                                                'checkoutMethodCompleted'=> $checkoutMethodCompleted,
                                                'form' => $paymentMethodForm->createView(),
                                                'mainContent' => 'DrufonyCoreBundle::checkout_default_template.html.twig',
                                                'checkoutStep' => SELECT_PAYMENT_METHOD_NAME,
                                                'breadCrumb' => $breadCrumb,
						'orders' => $orders,
						'products' => $products,
						'orders'=>$orders,
            					'registerForm'  => $registerForm->createView(),
            					'loginForm'     => $loginForm->createView(),
            					'isLoginPath'   => FALSE,
                                                )
                                              ));

        return $response;
    }
    /************************************/
    /**************SERMEPA***************/
    /************************************/


    public function sermepaPaymentAction(Request $request, $lang) {
        $response = new Response();

        //Check checkouk it's completed
        list($message, $target) = CommerceUtils::checkOrderStatus();
        if(!is_null($message)) {
            $this->get('session')->getFlashBag()->add(ERROR, $message);
            return $this->redirect($this->generateUrl($target, array('lang' => $lang)));
        }
        $billing = CommerceUtils::getStepData(BILLING_INFO);

        $shipping = CommerceUtils::getStepData(SHIPPING_INFO);
        $shippingMethod = CommerceUtils::getStepData(SHIPPING_METHOD);


        $shippingPrice = CommerceUtils::getShippingPrice($shippingMethod,$shipping);

        $cart = CommerceUtils::getCartInfo($shippingPrice);
        $totalPrice = round($cart['total'], 2) * 100;

	$order_number=(string)date('ymdHis');
	$code=base64_decode(SERMEPA_MERCHANT_KEY);
	$bytes = array(0,0,0,0,0,0,0,0); //byte [] IV = {0, 0, 0, 0, 0, 0, 0, 0}
	$iv = implode(array_map("chr", $bytes)); //PHP 4 >= 4.0.2
	$key=mcrypt_encrypt(MCRYPT_3DES,$code,$order_number,MCRYPT_MODE_CBC,$iv);

	$order=CommerceUtils::getLastOrder();
	$orderId=$order['orderId']+1;


        $sermepaForm = $this->createForm(new SermepaPaymentFormType(),
                                        array('amount' => $totalPrice, 'key' => $key,'order'=>$order_number,'orderId'=>$orderId,
                                            'titular' => $billing['name'], 'currency' => DEFAULT_CURRENCY, 'lang' => $lang));

        $existStep = CommerceUtils::existStep(SERMEPA_IN_PROGRESS);
        CommerceUtils::saveStep(SERMEPA_IN_PROGRESS, array('hash'=>$order_number), $existStep);

	$view =  $sermepaForm->createView();	
	
	$render=$this->renderView('DrufonyCoreBundle::checkout_sermepa_payment.html.twig', array('sermepaForm' => $view));
	
        $response->setContent($render);


        return $response;
    }

    public function sermepaPaymentErrorAction(Request $request, $lang) {
        //Check checkouk it's completed
        list($message, $target) = CommerceUtils::checkOrderStatus();
        if(!is_null($message)) {
            $this->get('session')->getFlashBag()->add(ERROR, $message);
            return $this->redirect($this->generateUrl($target, array('lang' => $lang)));
        }

        $existStep = CommerceUtils::existStep(SERMEPA_IN_PROGRESS);
        if (!$existStep) {
            $this->get('session')->getFlashBag()->add(ERROR, t('Select payment method'));
            return $this->redirect($this->generateUrl('drufony_checkout_review_payment', array('lang' => $lang)));
        }
        else {
            CommerceUtils::deleteStep(SERMEPA_IN_PROGRESS);
        }

        $existsFailed = CommerceUtils::existStep(FAILED);
        CommerceUtils::saveStep(FAILED, array(), $existsFailed);

        $this->get('session')->getFlashBag()->add(ERROR, t('The payment was not completed'));

        return $this->redirect($this->generateUrl('drufony_checkout_review_payment', array('lang' => $lang)));
    }

    public function sermepaPaymentSuccessAction(Request $request, $lang, $paymentHash, $orderId) {
        //Check checkouk it's completed
        //TODO: redirect to the proper place
        $this->get('session')->getFlashBag()->add(INFO, t('Thanks for the purchase'));
        return $this->redirect($this->generateUrl('drufony_commerce_your_order', array('lang' => $lang, 'orderId'=>$orderId)));
    }

    public function sermepaPaymentSuccessPostAction(Request $request, $lang, $sesId){

	
        $session    = getSession();
        $session->set('stepId',$sesId);

	
        //Check checkouk it's completed
        if ($request->getMethod() == 'POST') {
		list($message, $target) = CommerceUtils::checkOrderStatus();


		if(!is_null($message)) {
		    $this->get('session')->getFlashBag()->add(ERROR, $message);
		}

		$existStep = CommerceUtils::existStep(SERMEPA_IN_PROGRESS);
		if (!$existStep) {
		    $this->get('session')->getFlashBag()->add(ERROR, t('Select payment method'));
		}

		$data=CommerceUtils::getStepData(SERMEPA_IN_PROGRESS);

		$existPaymentStep = CommerceUtils::existStep(PAYMENT_METHOD);

        	//$parameters_ini = $request->headers->get('Ds_MerchantParameters');
		$parameters_ini = $this->get('request')->request->get('Ds_MerchantParameters');

 
		$parameters_dec=base64_decode($parameters_ini);
		//$paymentHash = $request->headers->get('Ds_Signature');
		$paymentHash = $this->get('request')->request->get('Ds_Signature');
		$code=base64_decode(SERMEPA_MERCHANT_KEY);
		$paymentHashGen=base64_encode(hash_hmac(SERMEPA_HASH_ALGORITHM,$parameters_dec,$code,true));
	
	
		$parameters_array=json_decode($parameters_dec,true);

		$orderNumber=$parameters_array['Ds_Order'];
		$paymentResult=(int)$parameters_array['Ds_Response'];

		l(INFO,"orderNumber:".$orderNumber);
		l(INFO,"paymentResult:".$paymentResult);
		l(INFO,"paymentHash:".$paymentHash);
		l(INFO,"paymentHashGen:".$paymentHashGen);
		l(INFO,"hash:".$data['hash']);
		
		if($orderNumber!=$data['hash'] || $paymentResult>99){
		    $this->get('session')->getFlashBag()->add(ERROR, t('Not coincident hash or wrong transaction'));
		}
		else{	
			CommerceUtils::saveStep(PAYMENT_METHOD, array('cardLastDigits' => null, 'payment' => TPV_SERMEPA_TYPE, 'hash' => $paymentHash, 'name' => TPV_SERMEPA), $existPaymentStep, true);

			$user = $this->getUser();


			$this->__saveOrder(PAYMENT_STATUS_PAID);
			l(INFO, 'Payment processed successfully');
			return('Processed');
		}	

		return('Not confirmed');
	}
	return('Solo POST');

    }
    /************************************/
    /***************PAYPAL***************/
    /************************************/

    public function paypalPaymentAction(Request $request, $lang) {
        //Check checkouk it's completed
        list($message, $target) = CommerceUtils::checkOrderStatus();
        if(!is_null($message)) {
            $this->get('session')->getFlashBag()->add(ERROR, $message);
            return $this->redirect($this->generateUrl($target, array('lang' => $lang)));
         }
 
        $billing = CommerceUtils::getStepData(BILLING_INFO);
        $shipping = CommerceUtils::getStepData(SHIPPING_INFO);
        $shippingMethod = CommerceUtils::getStepData(SHIPPING_METHOD);

        $shippingPrice = CommerceUtils::getShippingPrice($shippingMethod,$shipping);

        $cart = CommerceUtils::getCartInfo($shippingPrice);

        try {
            $paymentInfo = PaypalUtils::createPayment($cart, $shipping);
        } catch (\Exception $e) {
           $this->get('session')->getFlashBag()
               ->add(ERROR, t('Problems trying to reach Paypal, try again later or use antoher payment method'));
           return $this->redirect($this->generateUrl('drufony_checkout_review_payment', array('lang' => $lang)));


        }
        $existStep = CommerceUtils::existStep(PAYPAL_IN_PROGRESS);
        CommerceUtils::saveStep(PAYPAL_IN_PROGRESS, array('hash' => $paymentInfo->id), $existStep);

        return $this->redirect($paymentInfo->redirectUrl);
    }

    public function paypalPaymentErrorAction(Request $request, $lang) {
        //Check checkouk it's completed
        list($message, $target) = CommerceUtils::checkOrderStatus();
        if(!is_null($message)) {
            $this->get('session')->getFlashBag()->add(ERROR, $message);
            return $this->redirect($this->generateUrl($target, array('lang' => $lang)));
        }

        $existStep = CommerceUtils::existStep(PAYPAL_IN_PROGRESS);
        if (!$existStep) {
            $this->get('session')->getFlashBag()->add(ERROR, t('Select payment method'));
            return $this->redirect($this->generateUrl('drufony_checkout_review_payment', array('lang' => $lang)));
        }
        else {
            CommerceUtils::deleteStep(PAYPAL_IN_PROGRESS);
        }

        $existsFailed = CommerceUtils::existStep(FAILED);
        CommerceUtils::saveStep(FAILED, array(), $existsFailed);

        $this->get('session')->getFlashBag()->add(ERROR, t('The payment was not completed'));

        return $this->redirect($this->generateUrl('drufony_checkout_review_payment', array('lang' => $lang)));
    }

    public function paypalPaymentSuccessAction(Request $request, $lang) {
        //Check checkouk it's completed
        list($message, $target) = CommerceUtils::checkOrderStatus();
        if(!is_null($message)) {
            $this->get('session')->getFlashBag()->add(ERROR, $message);
            return $this->redirect($this->generateUrl($target, array('lang' => $lang)));
        }

        $existStep = CommerceUtils::existStep(PAYPAL_IN_PROGRESS);
        if (!$existStep) {
            $this->get('session')->getFlashBag()->add(ERROR, t('Select payment method'));
            return $this->redirect($this->generateUrl('drufony_checkout_review_payment', array('lang' => $lang)));
        }

        $paypal_data = CommerceUtils::getStepData(PAYPAL_IN_PROGRESS);

        $paymentStatus = $request->query->get('success');

        $payerId = $request->query->get('PayerID');

        $paymentResult = PaypalUtils::executePayment($paypal_data['hash'], $payerId);

        $existPaymentStep = CommerceUtils::existStep(PAYMENT_METHOD);
        CommerceUtils::saveStep(PAYMENT_METHOD, array('cardLastDigits' => null, 'payment' => TPV_PAYPAL_TYPE, 'hash' => $paypal_data['hash'], 'name' => TPV_PAYPAL), $existPaymentStep);

        $user = $this->getUser();

        l(INFO, 'Payment processed successfully');

        $orderId=$this->__saveOrder(PAYMENT_STATUS_PAID);

        //TODO: redirect to the proper place
        return $this->redirect($this->generateUrl('drufony_commerce_your_order', array('lang' => $lang,'orderId'=>$orderId-1)));
        $this->get('session')->getFlashBag()->add(INFO, t('Thanks for the purchase'));
    }


    /************************************/
    /***************STRIPE***************/
    /************************************/



    public function stripePaymentAction(Request $request, $lang) {
        $response = new Response();
        $checkoutMethodCompleted = CommerceUtils::existStep(CHECKOUT_METHOD);
        if(CommerceUtils::getCartItemsCount() == 0) {
            return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
        }
        else if(!$checkoutMethodCompleted) {
            //$this->get('session')->getFlashBag()->add(INFO, t('Please, complete checkout method before continue'));
            return $this->redirect($this->generateUrl('drufony_checkout_login', array('lang' => $lang)));
        }
        //Check checkouk it's completed
        list($message, $target) = CommerceUtils::checkOrderStatus();
        if(!is_null($message)) {
            $this->get('session')->getFlashBag()->add(ERROR, $message);
            return $this->redirect($this->generateUrl($target, array('lang' => $lang)));
        }


        $userLogged = !is_null($this->getUser());

        $formFields = null;
        $existPaymentStep = CommerceUtils::existStep(PAYMENT_METHOD);
        if($existPaymentStep) {
            $formFields = CommerceUtils::getStepData(PAYMENT_METHOD);
        }

        $userCards = array();
        //If user is logged, retrieve its cards
        if($userLogged) {
            $tpv = TPV::getInstance();
            list($customer, $userCards) = $tpv->getStoredUserCards($this->getUser()->getUid());
            if(count($userCards)) {
                $formFields['storedCards'] = $userCards;
                $formFields['customer'] = $customer;
            }
        }

        $paymentMethodForm = $this->createForm(new PaymentMethodFormType(), array('info' => $formFields));

        if($request->getMethod() == 'POST') {
            $paymentMethodForm->handleRequest($request);
            if($paymentMethodForm->isValid()) {

                //Check checkouk it's completed
                list($message, $target) = CommerceUtils::checkOrderStatus();
                if(!is_null($message)) {
                    $this->get('session')->getFlashBag()->add(ERROR, $message);
                    return $this->redirect($this->generateUrl($target, array('lang' => $lang)));
                }

                //Check coupons again
                if($coupon = CommerceUtils::getStepData(COUPON)) {
                    $couponStatus = CommerceUtils::getCouponStatus($coupon['couponCode']);
                    $message = CommerceUtils::getCouponStatusMessage($couponStatus);

                    #Act according coupon status
                    if($couponStatus != COUPON_VALID) {
                        $this->get('session')->getFlashBag()->add(ERROR, $message);
                        return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
                    }
                }

                $data = $paymentMethodForm->getData();

                if(array_key_exists('info', $data)) {
                    unset($data['info']);
                }
                $data['name'] = TPV_STRIPE;

                CommerceUtils::saveStep(PAYMENT_METHOD, $data, $existPaymentStep);

                l(INFO, 'Payment info saved successfully');

                return $this->redirect($this->generateUrl('drufony_order_submit', array('lang' => $lang)));
            }
        }

        $checkoutProgress = CommerceUtils::getCheckoutProgress();

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'ecommerce' => array( 'label' => 'Home', 'url' => 'commerce_home_path'),
        );

	$orders ='';
	if(!empty($user)){
		$orders = CommerceUtils::getUserOrders($user->getUid());
	}
        $registerForm = $this->_processRegisterForm($request);
           
        $loginForm = $this->_processLoginForm($request);
	$products=CommerceUtils::getCartItemsAJAX();
        $response->setContent($this->renderView('CustomProjectBundle::base-commerce.html.twig',
                                                array('form'              => $paymentMethodForm->createView(),
                                                      'stripe_public_key' => STRIPE_PUBLIC_KEY,
                                                      'lang'              => $lang,
                                                      'progress'          => $checkoutProgress,
                                                      'userLogged'        => $userLogged,
                                                      'checkoutMethodCompleted'=> $checkoutMethodCompleted,
                                                      'mainContent'       => 'DrufonyCoreBundle::checkout_default_template.html.twig',
                                                      'checkoutStep'      => PAYMENT_METHOD_NAME,
                                                      'breadCrumb'        => $breadCrumb,
						      'products'	  => $products,
						      'orders'=>$orders,
            					      'registerForm'  => $registerForm->createView(),
            					      'loginForm'     => $loginForm->createView(),
            					      'isLoginPath'   => FALSE,
                                                      )
                                                  ));
        return $response;
    }

    public function submitOrderAction(Request $request, $lang) {

        if(CommerceUtils::getCartItemsCount() == 0) {
            return $this->redirect($this->generateUrl('drufony_cart_view', array('lang' => $lang)));
        }

        //Check checkouk it's completed
        list($message, $target) = CommerceUtils::checkOrderStatus();
        if(!is_null($message)) {
            $this->get('session')->getFlashBag()->add(ERROR, $message);
            return $this->redirect($this->generateUrl($target, array('lang' => $lang)));
        }


        $user = $this->getUser();

        $uid = $user->getUid();

        $shippingMethod = CommerceUtils::getStepData(SHIPPING_METHOD);
        $payment = CommerceUtils::getStepData(PAYMENT_METHOD);

	
        $uid = $user->getUid();

        $shippingMethod = CommerceUtils::getStepData(SHIPPING_METHOD);
        $payment = CommerceUtils::getStepData(PAYMENT_METHOD);

        $shippingPrice = CommerceUtils::getShippingPrice($shippingMethod,$shipping);

        $cart = CommerceUtils::getCartInfo($shippingPrice);


        $tpv = TPV::getInstance();

        try {
            if(!$payment['selectedPrevious']) {
                list($customer, $card) = $tpv->addCardToCustomer($user->getUid(), $payment['token'], $payment['cardHoldername']);
            }
            else {
                $card = $payment['token'];
                $customer = CommerceUtils::getUserStripeId($uid);
            }

            $totalInCents = round($cart['totalDiscounted'], 2) * 100;
            $paymentId = $tpv->processPayment($totalInCents, DEFAULT_CURRENCY, $customer, $card);

            $payment['hash'] = $paymentId;
            CommerceUtils::saveStep(PAYMENT_METHOD, $payment, true);

        }
        catch(StripeException $e) {
            $existsFailed = CommerceUtils::existStep(FAILED);
            CommerceUtils::saveStep(FAILED, array(), $existsFailed);

            l(ERROR, 'Error processing payment code: ' . $e->getMessage());
            $this->get('session')->getFlashBag()->add(ERROR, $e->getMessage());
            return $this->redirect($this->generateUrl('drufony_payment_stripe', array('lang' => $lang)));
        
	}

        l(INFO, 'Payment processed successfully');
	$orderId = $this->__saveOrder(PAYMENT_STATUS_PAID);

	

        //TODO: redirect to the proper place
        $this->get('session')->getflashbag()->add(info, t('thanks for the purchase'));
        return $this->redirect($this->generateUrl('drufony_commerce_your_order', array('lang' => $lang, 'orderId'=>$orderId)));
    }

    private function __registerUser($userData, $lang) {
        if (!UserUtils::getUidByEmail($userData['email'])) {
            $newUser = new User();
            $encoder = $this->get('security.encoder_factory')->getEncoder($newUser);
            $data['password'] = $encoder->encodePassword(Drupal::user_password(10), $newUser->getSalt());
            $data['username'] = $userData['email'];
            $data['email'] = $userData['email'];
            $data['roles'] = array(User::ROLE_FOR_NEW_USERS);

            $uid = User::save($data);
            $user = new User($uid);
            $token = new UsernamePasswordToken($user, $user->getPassword(), 'user', $user->getRoles());

            $this->container->get('security.context')->setToken($token);
            $event = new InteractiveLoginEvent($this->getRequest(),$token);
            $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
            UserUtils::updateLoginDate($user->getUid());
            l(INFO, 'User ' . $user->getUsername() . ' registered and logged in successfully');

            $this->get('session')->getFlashBag()->add(
                INFO,
                t('Your email has been registered, please check your email inbox')
            );
            Mailing::sendForgotPassword($userData['email']);
        }
        else {
            return null;
        }

        return $user;
    }


private function __saveOrder($paymentStatus = PAYMENT_STATUS_PENDING) {

        $user = $this->getUser();


        $billing = CommerceUtils::getStepData(BILLING_INFO);
        $shipping = CommerceUtils::getStepData(SHIPPING_INFO);
        $shippingMethod = CommerceUtils::getStepData(SHIPPING_METHOD);
        $payment = CommerceUtils::getStepData(PAYMENT_METHOD);
	$exportZone = CommerceUtils::shippedToExportZone($shipping);

        $shippingPrice = CommerceUtils::getShippingPrice($shippingMethod,$shipping);

        $cart = CommerceUtils::getCartInfo($shippingPrice);	


	if (is_null($user)){

		$uid = UserUtils::getUidByEmail2($shipping['email']);
	}
	else{
        	$uid = $user->getUid();
	}
	

        $checkoutData = array('uid' => $uid, 'paymentMethod' => $payment['payment'],
	    'paymentStatus' => $paymentStatus, 'discount' => $cart['discount'],
            'total' => $cart['total'], 'paymentPlataform' => $payment['payment'],
            'shippingStatus' => ORDER_STATUS_NEW, 'billingInfo' => $billing,
            'comments' => $shippingMethod['comments'], 'shippingInfo' => $shipping,
            'subtotal_with_vat' => $cart['totalBeforeTaxes'], 'shippingValue' => $shippingPrice,
            'shippingId' => $shippingMethod['shipping'], 'currency' => DEFAULT_CURRENCY,
	    'cardLastDigits' => isset($payment['cardLastDigits']) ? $payment['cardLastDigits'] : null,
            'cardCountry' => '', 'orderStatus' => ORDER_STATUS_NEW,
            'cartItems' => $cart['cartItems'],
            'paymentHash' => isset($payment['hash']) ? $payment['hash'] : null,
	    'invoiceNumber' => $paymentStatus == PAYMENT_STATUS_PAID ? CommerceUtils::assignInvoiceNumber($exportZone):0,
	    'ticketNumber' => $paymentStatus == PAYMENT_STATUS_PAID ? CommerceUtils::assignTicketNumber():0,
            'exportZone' => $exportZone,
        );

        $existOrderStep = CommerceUtils::existStep(ORDER_SAVED);
        if ($existOrderStep) {
            $orderData = CommerceUtils::getStepData(ORDER_SAVED);
            if (!empty($orderData['orderId'])) {
                $checkoutData['orderId'] = $orderData['orderId'];
            }
        }


        $orderId = CommerceUtils::saveOrder($checkoutData);



        CommerceUtils::saveStep(ORDER_SAVED, array('orderId' => $orderId), $existOrderStep);

        $coupon = CommerceUtils::getStepData(COUPON);
        if($coupon) {
            $order = new Order($orderId);
            $couponData = CommerceUtils::getCouponByCode($coupon['couponCode']);
            $order->applyCoupon($couponData['id']);
            l(INFO, 'Coupon applied, successfully');
        }

        if ($paymentStatus == PAYMENT_STATUS_PAID) {


	

            CommerceUtils::saveUserAddressIfNew($uid, $billing);
            CommerceUtils::saveUserAddressIfNew($uid, $shipping);

 
	    CommerceUtils::updateStock();
            CommerceUtils::emptyCart();


             l(INFO, 'All cart products removed');

             CommerceUtils::emptyCheckout();

             l(INFO, 'All checkout steps removed');
            
	     Mailing::sendUserOrderCompletedEmail($shipping['email'], $orderId, $payment['name']);
             Mailing::sendManagementOrderCompletedEmail(COMMERCE_MANAGEMENT_EMAIL, $shipping['email'], $orderId, $payment['name'], $payment['hash']);
        }

        return $orderId;


    }




}
