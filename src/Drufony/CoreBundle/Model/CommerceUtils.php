<?php
/**
 * This is an static functions container related to Commerce functionalities.
 * It implements dashboard methods as well as the online store.
 *
 * This is an static class.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Symfony\Component\HttpFoundation\Session\Session;
use Drufony\CoreBundle\Model\UserUtils;
use Drufony\CoreBundle\Model\Product;
use Drufony\CoreBundle\Model\Geo;
use Symfony\Component\Form\FormError;


// Class Constants
define('CART_NAME', 'cart');
defined('DEFAULT_VAT')          or define('DEFAULT_VAT',     21);
defined('COUPON')               or define('COUPON',          'coupon');
defined('CHECKOUT_METHOD')      or define('CHECKOUT_METHOD', 'method');
defined('BILLING_INFO')         or define('BILLING_INFO',    'billing');
defined('SHIPPING_INFO')        or define('SHIPPING_INFO',   'shipping');
defined('SHIPPING_METHOD')      or define('SHIPPING_METHOD', 'ship_method');
defined('PAYMENT_METHOD')       or define('PAYMENT_METHOD',  'payment');
defined('ORDER_SAVED')          or define('ORDER_SAVED',  'order');
defined('SERMEPA_IN_PROGRESS')  or define('SERMEPA_IN_PROGRESS', 'sermep_pro');
defined('PAYPAL_IN_PROGRESS')   or define('PAYPAL_IN_PROGRESS', 'paypal_pro');
defined('FAILED')               or define('FAILED',          'failed');




/**
 * Static functions container related to Commerce functionalities.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class CommerceUtils
{
    /**
     * Instances the cart for using in a same HTTP thread request.
     *
     * @var SessionPool
     */
    static private $shoppingCart;

    /**
     * Identifies how many products there are in the cart.
     *
     * @var int
     */
    static private $cartCount;



    /**
     * Iart weight.
     *
     * @var int
     */
    static private $cartWeight;

    /**
     *
     * @return SessionPool
     */
    static private function getInstance() {
        if (!isset(self::$shoppingCart)) {
            $session   = getSession();
            $sessionId = $session->getId();
            self::$shoppingCart = new SessionPool(CART_NAME, $sessionId);
        }

        return self::$shoppingCart;
    }

    /**
     * Adds new products to the Shopping Cart.
     *
     * @param int
     * @param int
     *
     * @return void
     *
     * TODO: Check stock
     */
    static public function addToCart($productId, $value = 1,$varietyId = 0) {
        $cart           = self::getInstance();
        $oldCartCount   = self::getCartItemsCount();
        //$product        = new Product($productId);
	$stock=Product::getStockVariety($varietyId);
        if  ($stock >= $value) {
	    if($cart->seek($varietyId)) {
                $newValue = $value + $cart->current()->value;
                self::updateCart($newValue,$varietyId);
            }
            else{
		$cart->push($varietyId, $value);
                self::$cartCount = $value + $oldCartCount;
	    	Product::updateStockVariety(0-$value,$varietyId);
		self::refreshCartsValues($varietyId,Product::getStockVariety($varietyId));
                l(INFO, 'Variety ' . $varietyId . ' added to cart');
            }
        }


    }


    static public function refreshCartsValues($varietyId, $value) {
	$sql="UPDATE session_pools AS a SET a.value=? WHERE a.status=2 AND a.value>? AND a.objectId=?";
	$sql = db_executeQuery($sql,array($value,$value,$varietyId));

    }


    /**
     * Removes a product from the Shopping Cart.
     *
     * @param int
     *
     * @return void
     */
    static public function removeFromCart($varietyId) {
        $cart         = self::getInstance();
        $oldCartCount = self::getCartItemsCount();

        if ($cart->seek($varietyId)) {
	    $cartItemsToRemove=$cart->current()->value;
            self::$cartCount = $oldCartCount - $cartItemsToRemove;
            $cart->remove();

	    Product::updateStockVariety($cartItemsToRemove,$varietyId);
            l(INFO, 'Variety ' . $varietyId . ' removed from cart successfully');
        }
        else {
            l(ERROR, 'Product id doesn\'t found in cart');
        }
    }

    /**
     * Updates the amount of a product from the Shopping Cart.
     *
     * @param int
     * @param int
     *
     * @return void
     *
     * TODO: Check stock
     */
    static public function updateCart($value,$varietyId) {
        $cart         = self::getInstance();
        $oldCartCount = self::getCartItemsCount();
	l('INFO','dentro de update'.$varietyId);

        if ($cart->seek($varietyId)) {
     
	    $oldValue = $cart->current()->value;
	    $stock=Product::getStockVariety($varietyId);

            if  ($stock + $oldValue>= $value) {
                self::$cartCount = ($value - $oldValue) + $oldCartCount;
                $cart->updateValue($value);
	    	Product::updateStockVariety($oldValue-$value,$varietyId);
		l(INFO, 'Product ' . $varietyId . ' updated in cart successfully');
            }
		self::refreshCartsValues($varietyId,Product::getStockVariety($varietyId));
        }
        else {
            l(ERROR, 'Product id doesn\'t found in cart');
        }

    }

    /**
     * Retrieves all the items from the Shopping Cart.
     *
     * @return array
     */
    static public function getCartItems() {
        $cart     = self::getInstance();
        $products = array();

        $cart->rewind();
        $item = $cart->current();

        if (!is_null($item)) {
            do {
                $product  = new Product($item->id);
                $count    = $item->value;
                $subtotal = $count * $product->__get('priceSubtotalNoVat');
                $productWeight = is_null($product->getWeight()) ? 0 : $product->getWeight();
                $weight   = $item->value * $productWeight;


                $products[] = array(
                    'product'  => $product,
                    'count'    => $count,
                    'subtotal' => $subtotal,
		    'weight'   => $weight,
                );
            } while (($item = $cart->next()) !== null);
        }

        return $products;
    }

    
     static public function getCartItemsAJAX($sessionId=0) {
 
	if ($sessionId==0){
		$cart     = self::getInstance();
	}
	else{
                $cart = new SessionPool(CART_NAME, $sessionId);
	}
        $products = array();

        $cart->rewind();
        $item = $cart->current();

        if (!is_null($item)) {
            do {
    	    	$product  = Product::getProductAJAX($item->id);

                $count    =  $item->value;
                $subtotal = $count * $product['priceSubtotalNoVat'];

		$total = $count * $product['pricePVP'];


		$tax= $total-$subtotal;

                $productWeight = is_null($product['weight']) ? 0 : $product['weight'];
                $weight   = $item->value * $productWeight;
                $products[] = array(
                    'product'  => $product,
                    'count'    => $count,
                    'subtotal' => $subtotal,
		    'status'   => $item->status,
		    'weight'   => $weight,
		    'total' => $total,
		    'tax' => $tax,
                );
            } while (($item = $cart->next()) !== null);
        }

        return $products;
    }

    static public function getCartWeight() {
        if (!isset(self::$cartWeight)) {
            $items = self::getCartItemsAJAX();
            $weight = 0;

            foreach ($items as $product) {
                $weight += $product['weight'];
            }

            self::$cartWeight = $weight;
        }

        return self::$cartWeight;
    }




    /**
     * Retrieves the amount of products from the Shopping Cart.
     *
     * @return int
     */
    static public function getCartItemsCount() {
    
	if (!isset(self::$cartCount)) {
            $items = self::getCartItemsAJAX();
            $count = 0;

            foreach ($items as $product) {
                $count += $product['count'];
            }

            self::$cartCount = $count;
        }

        return self::$cartCount;
    }

    /**
     * Removes all the products from the Shopping Cart.
     *
     * @return void
     */
    static public function emptyCart() {
        $cartItems = self::getCartItemsAJAX();

        foreach ($cartItems as $item) {
            self::removeFromCart($item['product']['varProdId']);
        }
    }

    static public function updateStock($sessionId) {
        $cartItems = self::getCartItemsAJAX($sessionId);

        foreach ($cartItems as $item) {
	    $stockToRemove =$item['product']['value'];
	    $varietyId=$item['product']['varProdId'];
	    $oldValue=Product::getStockVariety($varietyId);
	    if($item['status']==2){
	    	Product::updateStockVariety($oldValue-$stockToRemove,$varietyId);
	    }
        }
    }
    /**
     * Retrieves all the info data about the Shopping Cart.
     *
     * @param int
     *
     * @return array
     */
    static public function getCartInfo($additionalCosts = 0) {
        $cartInfo  = array();
        
	$cartItems = CommerceUtils::getCartItemsAJAX();

        $subtotal = 0;
	$total=0;
	$tax=0;
        foreach ($cartItems as $item) {
            $subtotal += $item['subtotal'];
            $total    += $item['total'];
            $tax      += $item['tax'];
        }

        $cartInfo['shippingFee']        = $additionalCosts;
        $cartInfo['total']              = $total + $cartInfo['shippingFee'];	
        $cartInfo['subtotal']               = $subtotal;
        $cartInfo['discount']               = 0;
        $cartInfo['couponDiscount']         = 0;
        $cartInfo['discountType']           = 0;
        $cartInfo['totalDiscounted']    = $cartInfo['total'];
        $cartInfo['cartItems']              = $cartItems;
        $cartInfo['itemsCount']             = CommerceUtils::getCartItemsCount();
        $cartInfo['subtotalProducts']       = $subtotal;
        $cartInfo['subtotalProductsDisc']   = $subtotal;
        $cartInfo['taxProducts']            = $tax;
	$cartInfo['tax']		    = $tax;
        $cartInfo['subtotalProductsTax']        = $tax;
        $cartInfo['totalBeforeTaxes']   = $cartInfo['subtotal'];
	

        return $cartInfo;
    }

    /**
     * Saves a giving checkout step
     *
     * @param string
     * @param array
     * @param bool * Indicates if we want to insert or update the step
     *
     * @return void
     */
    static public function saveStep($stepString, $data, $update = false, $outSession=false ) {
        self::getInstance();

        $session    = getSession();
        $sessionId  = $session->getId();
	if(!$outSession){
        	$session->set('stepId',$sessionId);
	}
	else{
		$sessionId=$session->get('stepId');
	}

        $insertData = array('step' => $stepString,
                            'sessId' => $sessionId,
                            'data' => serialize($data),
                            );

        if(!$update) {
            db_insert('checkout', $insertData);
        }
        else {
            $updateCriteria = array('sessId' => $sessionId,
                                    'step' => $stepString);

            db_update('checkout', $insertData, $updateCriteria);
        }
    }

    /**
     * Checks if an step exists
     *
     * @param int
     *
     * @return boolean
     */
    static public function existStep($stepString) {
        $session   = getSession();
        $sessionId = $session->getId();

        $sql  = 'SELECT id ';
        $sql .= 'FROM checkout ';
        $sql .= 'WHERE step = ? AND sessId = ?';

        $result = db_executeQuery($sql, array($stepString, $sessionId));

        return ($result->rowCount() > 0);
    }

    /**
     * Retrieves data by given step
     *
     * @param string
     *
     * @return array
     */
    static public function getStepData($stepString) {
        $sessionId = getSession()->get('stepId');

        $sql  = 'SELECT data ';
        $sql .= 'FROM checkout ';
        $sql .= 'WHERE step = ? AND sessId = ?';

        $result = db_fetchColumn($sql, array($stepString, $sessionId));

        return unserialize($result);
    }

    /**
     * Retrieves all the checkout info collected
     *
     * @return array
     */
    static public function getCheckoutProgress() {
        $progress = array('billingInfo'    => array(),
                          'shippingInfo'   => array(),
                          'shippingMethod' => array(),
                          'comments'       => '',
                          'status'         => false);

        if(self::existStep(BILLING_INFO)) {
            $billingInfo                 = self::getStepData(BILLING_INFO);
            $billingInfo['countryName']  = Geo::getCountryNameById($billingInfo['countryId']);
            $billingInfo['provinceName'] = $billingInfo['province'];



            $progress['billingInfo']     = $billingInfo;
            $progress['status']          = true;
        }

        if(self::existStep(SHIPPING_INFO)) {
            $shippingInfo                 = self::getStepData(SHIPPING_INFO);
            $shippingInfo['countryName']  = Geo::getCountryNameById($shippingInfo['countryId']);
            $shippingInfo['provinceName'] = $shippingInfo['province'];


            $progress['shippingInfo']     = $shippingInfo;
            $progress['status']           = true;
        }

        $shippingMethodPrice = 0;
        if(self::existStep(SHIPPING_METHOD)) {
            $shippingMethod             = self::getStepData(SHIPPING_METHOD);
            $progress['shippingMethod'] = self::getShippingInfo($shippingMethod['shipping']);
            $progress['comments']       = $shippingMethod['comments'];
            $progress['status']         = true;


            if (SHIPPING_FEE_ENABLED == SHIPPING_FEE_GENERAL) {
                $shippingMethodPrice        = $progress['shippingMethod']['price'];
            }
            else if (isset($progress['shippingInfo']['countryId'])) {
                $shippingMethodPrice        = self::getShippingCostByCountry($progress['shippingInfo']['countryId'], self::getCartWeight());
            }


        }

        $cartInfo = CommerceUtils::getCartInfo($shippingMethodPrice);
        $progress = $progress + $cartInfo;

        return $progress;
    }

    /**
     * Checks if all needed steps exists
     *
     * @return array
     */
    static public function checkOrderStatus() {
        $message = null;
        $target  = null;
        if (!CommerceUtils::existStep(SHIPPING_INFO)) {

            $message = t('Please complete shipping info before payment');
            $target  = 'drufony_checkout_shipping_info';
        }
        else if (!CommerceUtils::existStep(BILLING_INFO)) {
            $message = t('Please complete billing info before payment');
            $target  = 'drufony_checkout_billing_info';
        }


        else if (!CommerceUtils::existStep(SHIPPING_METHOD)) {
            $message = t('Please complete shipping method before payment');
            $target  = 'drufony_checkout_shipping_method';
        }

        return array($message, $target);
    }

    /**
     * Removes an step from database
     *
     * @param string
     *
     * @return void
     */
    static public function deleteStep($step) {
        $session   = getSession();
        $session->set('stepId',$sessionId);
        $deleteCriteria = array('sessId' => $sessionId,
                                'step' => $step);

        db_delete('checkout', $deleteCriteria);
    }

    /**
     * Removes data collected from checkout process
     *
     * @return void
     */
    static public function emptyCheckout() {
        self::deleteStep(COUPON);
        self::deleteStep(CHECKOUT_METHOD);
        self::deleteStep(SHIPPING_INFO);
        self::deleteStep(BILLING_INFO);
        self::deleteStep(SHIPPING_METHOD);
        self::deleteStep(PAYMENT_METHOD);
        self::deleteStep(FAILED);
        self::deleteStep(SERMEPA_IN_PROGRESS);
        self::deleteStep(ORDER_SAVED);

    }

    /**
     * Retrieves all the shipping fees stored
     *
     * @return array
     */
    static public function getShippingList() {
        $sql  = 'SELECT id, title, description, freeThreshold, price, type ';
        $sql .= 'FROM shipping_fee';

        $result = array();
        if ($queryResult = db_executeQuery($sql)) {
            $result = $queryResult->fetchAll();
        }

        return $result;
    }

    /**
     * Saves a shipping fee in database
     *
     * @param array
     *
     * @return void
     */
    static public function saveShipping($shippingFeeData) {
        $insertData = array('title'         => $shippingFeeData['title'],
                            'description'   => $shippingFeeData['description'],
                            'freeThreshold' => $shippingFeeData['freeThreshold'],
                            'price'         => $shippingFeeData['price']);

        db_insert('shipping_fee', $insertData);
    }

    /**
     * Retrieves the info by shipping fee given
     *
     * @param int
     *
     * @return array
     */
    static public function getShippingInfo($shippingId) {
        $sql  = 'SELECT title, description, freeThreshold, price ';
        $sql .= 'FROM shipping_fee ';
        $sql .= 'WHERE id = ?';

        $shippingPrice = db_fetchAssoc($sql, array($shippingId));

        return $shippingPrice;
    }

    /**
     * Retrieves orders between two dates
     *
     * @param datetime
     * @param datetime
     * @param int
     * @param int
     *
     * @return array; with orders retrieved
     */
    static public function getOrders($startDate, $endDate, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql  = 'SELECT orderId ';
        $sql .= 'FROM `order` ';
        $sql .= 'WHERE orderDate >= ? and orderDate <= ? ';

        $orders = array();
        if ($result = db_executeQuery($sql, array($startDate, $endDate), $page, $itemsPerPage)) {
            while ($row = $result->fetch()) {
                $orders[] = new Order($row['orderId']);
            }
        }

        return $orders;
    }

    /**
     * Saves an order in database
     *
     * @param array
     *
     * @return int
     */
    static public function saveOrder($checkoutData) {
        $session    = getSession();
        $sessionId  = $session->getId();

        $insertData = array('paymentMethod'     => $checkoutData['paymentMethod'],
                            'paymentStatus'     => $checkoutData['paymentStatus'],
                            'discount'          => $checkoutData['discount'],
                            'total'             => $checkoutData['total'],
                            'paymentPlatform'   => $checkoutData['paymentPlataform'],
                            'shippingStatus'    => $checkoutData['shippingStatus'],
                            'billingInfo'       => serialize($checkoutData['billingInfo']),
                            'shippingInfo'      => serialize($checkoutData['shippingInfo']),
                            'subtotal_with_vat' => $checkoutData['subtotal_with_vat'],
                            'shippingValue'     => $checkoutData['shippingValue'],
                            'cardLastDigits'    => $checkoutData['cardLastDigits'],
                            'cardCountry'       => $checkoutData['cardCountry'],
                            'shippingId'        => $checkoutData['shippingId'],
                            'currency'          => $checkoutData['currency'],
                            'sessId'            => $sessionId,
                            'uid'               => $checkoutData['uid'],
                            'comments'          => $checkoutData['comments'],
                            'orderStatus'       => $checkoutData['orderStatus'],
                            'paymentHash'       => $checkoutData['paymentHash'],
                            'lastModification'  => date('Y-m-d H:i:s', strtotime("now")),
			    'invoiceNumber'     => $checkoutData['invoiceNumber'],
			    'ticketNumber'     => $checkoutData['ticketNumber'],
			    'exportZone'     => $checkoutData['exportZone'],
                        );

        if(!array_key_exists('orderId', $checkoutData)) {
            $orderId = db_insert('`order`', $insertData);
        }

        else {
            $updateCritera = array('orderId' => $checkoutData['orderId']);
            db_update('`order`', $insertData, $updateCritera);
            $orderId = $checkoutData['orderId'];
        }

        if (isset($checkoutData['cartItems'])) {
            self::_saveOrderProducts($orderId, $checkoutData['cartItems']);
        }



        self::saveOrderHistory($orderId, $checkoutData['shippingStatus'], $checkoutData['paymentStatus']);

        return $orderId;
    }

    /**
     * Saves order history in database.
     *
     * @param int
     * @param int
     * @param int
     *
     * @return void
     */
    static public function saveOrderHistory($orderId, $shippingStatus, $paymentStatus) {
        $insertData = array('orderId'        => $orderId,
                            'shippingStatus' => $shippingStatus,
                            'paymentStatus'  => $paymentStatus,
                            'operationDate'  => date('Y-m-d H:i:s', strtotime("now")),);

        $orderHistoryId = db_insert('order_history', $insertData);

        return $orderHistoryId;
    }

    /**
     * Removes an order from database.
     *
     * @param int
     *
     * @return void
     */
    static public function removeOrder($orderId) {
        $deleteCriteria = array("orderId" => $orderId);

        db_delete('`order`', $deleteCriteria);
        db_delete('orders_by_product', $deleteCriteria);
    }

    /**
     * Saves order products in database.
     *
     * @param int
     * @param int
     *
     * @return void
     */
    static private function _saveOrderProducts($orderId, $items) {

        db_delete('orders_by_product', array('orderId' => $orderId));

        foreach ($items as $item) {
            $total 	= $item['product']['pricePVP'];
            $total_no_vat     = $item['product']['priceSubtotalNoVat'];

            $insertData = array(
                'orderId'  => $orderId,
		'nid' => $item['product']['nid'],
		'sgu' => $item['product']['sgu'],
		'title_p' => $item['product']['title'],
		'varieties' => $item['product']['size'],
                'quantity' => $item['count'], 
		'total' => $total,
                'currency' => DEFAULT_CURRENCY, 
		'total_vat' => $total-$total_no_vat,
                'subtotal_without_vat' => $total_no_vat, 
		'percentage_vat' => DEFAULT_VAT);

            db_insert('orders_by_product', $insertData);
        }
    }

    /**
     * Retrives the Stripe id by user id given.
     *
     * @param int
     *
     * @return int
     *
     * FIXME: Why this method is developed in CommerceUtils?
     */
    static public function getUserStripeId($uid) {
        $sql  = 'SELECT stripe_id ';
        $sql .= 'FROM stripe_customers ';
        $sql .= 'WHERE uid = ? ';

        $customer_id = db_fetchColumn($sql, array($uid));

        return $customer_id;
    }

    /**
     * Retrieves carts unfinished
     *
     * @param datetime
     * @param int
     *
     * @return array
     */
    static public function getUnfinishedCarts($startDate, $number) {
        $endDate = date('Y-m-d', strtotime($startDate . "+${number} day"));

        $sql  = 'SELECT DISTINCT sessId ';
        $sql .= 'FROM session_pools ';
        $sql .= 'WHERE type = ? AND ';
        $sql .= '(date(created) = date(?) OR date(created) <= date(?)) AND ';
        $sql .= 'date(changed) <= date(?)';

        $queryResult = db_executeQuery($sql, array(CART_NAME, $startDate, $endDate, $endDate));
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves the sales between two dates.
     *
     * @param datetime
     * @param datetime
     *
     * @return array
     */
    static public function getSales($startDate, $endDate) {
       $sql  = 'SELECT count(orderId) numOrders, sum(total) total ';
       $sql .= 'FROM `order` ';
       $sql .= 'WHERE date(orderDate) >= date(?) AND date(orderDate) <= date(?)';

       $queryResult = db_fetchAssoc($sql, array($startDate, $endDate));
       $result      = $queryResult->fetchAll();

       return $result;
    }

    /**
     * Retrieves the total amount of sales between two dates
     *
     * @param string $startDate
     * @param string $endDate
     *
     * @return float
     */
    static public function getSalesTotal($startDate, $endDate) {
       $sql  = 'SELECT sum(total) total ';
       $sql .= 'FROM `order` ';
       $sql .= 'WHERE date(orderDate) >= date(?) AND date(orderDate) <= date(?)';

       $result = db_fetchColumn($sql, array($startDate, $endDate));

       $result = is_null($result) ? 0 : $result;

       return $result;
    }

    /**
     * Retrieves the stock by product given.
     *
     * @param int
     *
     * @return int
     */
    static public function getStock($productId = null) {
        $sql  = 'SELECT stock ';
        $sql .= 'FROM product ';
        $sql .= 'WHERE id = ?';

        $queryResult = db_executeQuery($sql, array($productId));
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves latest products sold.
     *
     * @param int
     * @param int
     *
     * @return array
     */
    static public function getLatestSellers($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql  = 'SELECT product.nid, order.orderDate ';
        $sql .= 'FROM product ';
        $sql .= 'INNER JOIN orders_by_product ';
        $sql .= 'ON product.nid = orders_by_product.nid ';
        $sql .= 'INNER JOIN `order` ';
        $sql .= 'ON `order`.orderId = orders_by_product.orderId ';
        $sql .= 'ORDER BY orderDate DESC';

        $queryResult = db_executeQuery($sql, array(), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves latest users who have made an order.
     *
     * @param int
     * @param int
     *
     * @return array
     */
    static public function getLatestShoppers($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql  = 'SELECT users.uid, users.username, MAX(`order`.orderDate) as orderDate ';
        $sql .= 'FROM users ';
        $sql .= 'INNER JOIN `order` ';
        $sql .= 'ON `order`.uid = users.uid ';
        $sql .= 'GROUP BY users.uid ';
        $sql .= 'ORDER BY orderDate DESC';

        $queryResult = db_executeQuery($sql, array(), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves most sold products.
     *
     * @param int
     * @param int
     *
     * @return array
     */
    static public function getBestSellers($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql  = "SELECT product.nid, product.title, sum(orders_by_product.quantity) as count ";
        $sql .= "FROM product ";
        $sql .= "INNER JOIN orders_by_product ";
        $sql .= "ON orders_by_product.nid = product.nid ";
        $sql .= "GROUP BY product.nid ";
        $sql .= "ORDER BY count DESC";

        $queryResult = db_executeQuery($sql, array(), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves top buyers.
     *
     * @param int
     * @param int
     *
     * @return array
     */
    static public function getBestShoppers($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql  = 'SELECT users.uid, users.username, count(users.uid) numOrders ';
        $sql .= 'FROM users ';
        $sql .= 'INNER JOIN `order` ';
        $sql .= 'ON `order`.uid = users.uid ';
        $sql .= 'GROUP BY users.uid ';
        $sql .= 'ORDER BY numOrders DESC ';

        $queryResult = db_executeQuery($sql, array(), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves latest orders made.
     *
     * @param int
     * @param int
     *
     * @return array
     */
    static public function getLatestSales($page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql  = 'SELECT orderId, orderDate ';
        $sql .= 'FROM `order` ';
        $sql .= 'ORDER BY orderDate DESC';

        $queryResult = db_executeQuery($sql, array(), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves carts started between two dates.
     *
     * @param datetime
     * @param datetime
     * @param int
     * @param int
     *
     * @return array
     */
    static public function getStartedCarts($startDate, $endDate, $page = 0, $itemsPerPage = ITEMS_PER_PAGE){
        $sql  = 'SELECT sessId, min(created) created ';
        $sql .= 'FROM session_pools ';
        $sql .= 'WHERE type = ? AND date(created) >= date(?) AND date(created) <= date(?) ';
        $sql .= 'GROUP BY sessId ';
        $sql .= 'ORDER BY created ASC';

        $queryResult = db_executeQuery($sql, array(CART_NAME, $startDate, $endDate), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves the amount of carts started between two dates.
     *
     * @param datetime
     * @param datetime
     *
     * @return int
     */
    static public function getStartedCartsCount($startDate, $endDate){
        $sql  = 'SELECT COUNT(DISTINCT sessId) AS count ';
        $sql .= 'FROM session_pools ';
        $sql .= 'WHERE type = ? AND date(created) >= date(?) AND date(created) <= date(?) ';

        $result = db_fetchColumn($sql, array(CART_NAME, $startDate, $endDate));

        return $result;
    }

    /**
     * Retrieves finished checkouts (orders) between two dates.
     *
     * @param datetime
     * @param datetime
     * @param int
     * @param int
     *
     * @return array
     */
    static public function getFinishedCheckouts($startDate, $endDate, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql  = 'SELECT orderId, orderDate ';
        $sql .= 'FROM `order` ';
        $sql .= 'WHERE date(orderDate) >= date(?) AND date(orderDate) <= date(?) ';
        $sql .= 'ORDER BY orderDate DESC';

        $queryResult = db_executeQuery($sql, array($startDate, $endDate), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves the amount of finished checkouts (orders) ibetween two dates.
     *
     * @param datetime
     * @param datetime
     *
     * @return int
     */
    static public function getFinishedCheckoutsCount($startDate, $endDate) {
        $sql  = 'SELECT COUNT(orderId) ';
        $sql .= 'FROM `order` ';
        $sql .= 'WHERE date(orderDate) >= date(?) AND date(orderDate) <= date(?) ';

        $result = db_fetchColumn($sql, array($startDate, $endDate));

        return $result;
    }

    /**
     * Retrieves failed checkouts between two dates.
     *
     * @param datetime
     * @param datetime
     * @param int
     * @param int
     *
     * @return array
     */
    static public function getFailedCheckouts($startDate, $endDate, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql  = 'SELECT sessId, max(stepDate) as stepDate ';
        $sql .= 'FROM checkout ';
        $sql .= 'WHERE step = ? AND date(stepDate) >= date(?) AND date(stepDate) <= date(?) ';
        $sql .= 'GROUP BY sessId ';
        $sql .= 'ORDER BY stepDate DESC';

        $queryResult = db_executeQuery($sql, array(FAILED, $startDate, $endDate), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves the amount of failed checkouts between two dates.
     *
     * @param datetime
     * @param datetime
     *
     * @return int
     */
    static public function getFailedCheckoutsCount($startDate, $endDate) {
        $sql  = 'SELECT count(DISTINCT sessId) ';
        $sql .= 'FROM checkout ';
        $sql .= 'WHERE step = ? AND date(stepDate) >= date(?) AND date(stepDate) <= date(?) ';

        $result = db_fetchColumn($sql, array(FAILED, $startDate, $endDate));

        return $result;
    }

    /**
     * Retrieves started checkouts between two dates.
     *
     * @param datetime
     * @param datetime
     * @param int
     * @param int
     *
     * @return array
     */
    static public function getStartedCheckout($startDate, $endDate, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql  = 'SELECT c1.sessId, min(c1.stepDate) as minStepDate ';
        $sql .= 'FROM checkout c1 ';
        $sql .= 'INNER JOIN checkout c2 ';
        $sql .= 'ON c2.step = ? ';
        $sql .= 'WHERE c1.sessId != c2.sessId AND ';
        $sql .= 'date(c1.stepDate) >= date(?) AND date(c1.stepDate) <= date(?) ';
        $sql .= 'GROUP BY c1.sessId ';
        $sql .= 'ORDER BY c1.stepDate DESC';

        $queryResult = db_executeQuery($sql, array(FAILED, $startDate, $endDate), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves the amount of started checkouts between two dates.
     *
     * @param datetime
     * @param datetime
     *
     * @return int
     */
    static public function getStartedCheckoutCount($startDate, $endDate) {
        $sql  = 'SELECT count(DISTINCT c1.sessId) ';
        $sql .= 'FROM checkout c1 ';
        $sql .= 'INNER JOIN checkout c2 ';
        $sql .= 'ON c2.step = ? ';
        $sql .= 'WHERE c1.sessId != c2.sessId AND ';
        $sql .= 'date(c1.stepDate) >= date(?) AND date(c1.stepDate) <= date(?) ';

        $result = db_fetchColumn($sql, array(FAILED, $startDate, $endDate));

        return $result;
    }

    /**
     * Retrieves the total amount in all checkouts
     *
     * @param string $startDate
     * @param string $endDate
     *
     * @return float
     */
    static public function getStartedCheckoutsAmount($startDate, $endDate) {
        $sql  = "SELECT sum(p.priceSubtotalNoVat * sp.value) FROM session_pools sp ";
        $sql .= "INNER JOIN product p ON p.nid = sp.objectId ";
        $sql .= "WHERE sp.type = ? AND date(created) >= date(?) AND date(created) <= date(?)";

        $result = db_fetchColumn($sql, array('cart', $startDate, $endDate));

        return $result;
    }

    /**
     * Retrieves multiuser type coupons.
     *
     * @return array
     */
    static private function _getMultiUserCoupons($page, $itemsPerPage) {
        $sql  = 'SELECT id, code, type, date(startDate) as startDate, date(expirationDate) as expirationDate, isPercentage, value, status, count(orderId) as count ';
        $sql .= 'FROM coupons ';
        $sql .= 'LEFT JOIN `order` ';
        $sql .= 'ON `order`.couponId = coupons.id ';
        $sql .= 'WHERE type = ? ';
        $sql .= 'GROUP BY id';

        $queryResult = db_executeQuery($sql, array(COUPON_MULTIUSER), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Retrieves all coupon codes related with same data.
     *
     * @param array
     *
     * @return array
     */
    static private function _getRelatedCouponsCode($couponData) {
        $sql  = 'SELECT code ';
        $sql .= 'FROM coupons ';
        $sql .= 'WHERE date(startDate) = ? AND date(expirationDate) = ? AND isPercentage = ? AND value = ? AND type = ?';

        $codes = db_fetchAllColumn($sql, array($couponData['startDate'], $couponData['expirationDate'], $couponData['isPercentage'], $couponData['value'], $couponData['type']));

        return $codes;
    }

    /**
     * Retrieves the amount of used coupons.
     *
     * @param array
     *
     * @return int
     */
    static private function _getCouponsUsedByUsers($codes) {
        $arrayCodes = implode("','", $codes);

        $sql  = 'SELECT COUNT(orderId) ';
        $sql .= 'FROM `order` ';
        $sql .= 'INNER JOIN coupons ';
        $sql .= 'ON coupons.id = `order`.couponId ';
        $sql .= "WHERE coupons.code in ('${arrayCodes}')";

        $result = db_fetchColumn($sql, array());

        return $result;
    }

    /**
     * Retrieves unique coupons grouped by same data.
     *
     * @return array
     */
    static private function _getUniqueCoupons($param, $itemsPerPage) {
        $sql  = 'SELECT id, type, date(startDate) as startDate, date(expirationDate) as expirationDate, isPercentage, value, status ';
        $sql .= 'FROM coupons ';
        $sql .= 'WHERE type = ? ';
        $sql .= 'GROUP BY startDate, expirationDate, isPercentage, value';

        $queryResult = db_executeQuery($sql, array(COUPON_UNIQUE), $param, $itemsPerPage);

        $result = array();
        while ($row = $queryResult->fetch()) {
            $row['code']  = self::_getRelatedCouponsCode($row);
            $row['count'] = self::_getCouponsUsedByUsers($row['code']);
            $result[]     = $row;
        }

        //$result = $queryResult->fetchAll();
        return $result;
    }

    /**
     * Retrieves the coupons by type given.
     *
     * @param int
     *
     * @return array
     */
    static public function getCoupons($couponType, $page = 0, $itemsPerPage= ITEMS_PER_PAGE) {
        $result = array();

        if($couponType == COUPON_MULTIUSER) {
            $result = self::_getMultiUserCoupons($page, $itemsPerPage);
        }
        else {
            $result = self::_getUniqueCoupons($page, $itemsPerPage);
        }

        return $result;
    }

    /**
     * Retrieves the coupons by type given.
     *
     * @param int
     *
     * @return array
     */
    static public function getCouponsCount($couponType) {

        if($couponType == COUPON_MULTIUSER) {
            $sql  = 'SELECT COUNT(*) FROM coupons WHERE type = ? ';
        }
        else {
            $sql  = 'SELECT COUNT(*) FROM coupons WHERE type = ? ';
            $sql .= 'GROUP BY startDate, expirationDate, isPercentage, value';
        }

        $count = db_fetchColumn($sql, array($couponType));

        return $count;
    }

    /**
     * Retrieves coupon data by coupon id given.
     *
     * @param int
     *
     * @return array
     */
    static public function getCoupon($couponId) {
        $sql  = 'SELECT id, code, type, date(startDate) as startDate, date(expirationDate) as expirationDate, isPercentage, value, status ';
        $sql .= 'FROM coupons ';
        $sql .= 'WHERE id = ? ';

        $result = db_fetchAssoc($sql, array($couponId));

        return $result;
    }

    /**
     * Retrieves coupon data by coupon code given.
     *
     * @param int
     *
     * @return array
     */
    static public function getCouponByCode($couponCode) {
        $sql  = 'SELECT id, code, type, date(startDate) as startDate, date(expirationDate) as expirationDate, isPercentage, value, status ';
        $sql .= 'FROM coupons ';
        $sql .= 'WHERE code = ? ';

        $result = db_fetchAssoc($sql, array($couponCode));

        return $result;
    }

    /**
     * Retrieves orders with applied coupons.
     *
     * @param int
     * @param int
     * @param int
     *
     * @return array
     */
    static public function getOnSaleOrders($couponId = null, $page = 0, $itemsPerPage = ITEMS_PER_PAGE) {
        $sql  = 'SELECT orderId ';
        $sql .= 'FROM `order` ';
        $sql .= 'WHERE couponId = ? ';

        $queryResult = db_executeQuery($sql, array($couponId), $page, $itemsPerPage);
        $result      = $queryResult->fetchAll();

        return $result;
    }

    /**
     * Changes the status of a coupon by id given.
     *
     * @param int
     * @param int
     * @param int
     *
     * @return void
     */
    static private function changeStatus($couponId, $status, $type) {
        $criteriaUpdate = array('id' => $couponId);
        $updateData     = array('status' => $status);

        db_update('coupons', $updateData, $criteriaUpdate);

        // Update status of coupons with same data
        if (!is_null($type) && $type == COUPON_UNIQUE) {
            $coupon         = self::getCoupon($couponId);
            $updateData     = array('status' => $status);
            $criteriaUpdate = array('type' => $coupon['type'],
                                    'expirationDate' => $coupon['expirationDate'],
                                    'startDate' => $coupon['startDate'],
                                    'value' => $coupon['value'],
                                    'isPercentage' => $coupon['isPercentage']);

            db_update('coupons', $updateData, $criteriaUpdate);
        }
    }

    /**
     * Sets coupon status as enabled.
     *
     * @param int
     * @param int
     *
     * @return void
     */
    static public function enableCoupon($couponId, $type = null) {
        self::changeStatus($couponId, COUPON_ENABLED, $type);
    }

    /**
     * Sets coupon status as disabled.
     *
     * @param int
     * @param int
     *
     * @return void
     */
    static public function disableCoupon($couponId, $type = null) {
        self::changeStatus($couponId, COUPON_DISABLED, $type);
    }

    /**
     * Saves or updates a coupon.
     *
     * @param array
     * @param int
     *
     * @return coupon id
     */
    static public function saveCoupon($couponData, $type = null) {
        $insertData = array('type'           => $couponData['type'],
                            'expirationDate' => $couponData['expirationDate'],
                            'startDate'      => $couponData['startDate'],
                            'isPercentage'   => $couponData['isPercentage'],
                            'value'          => $couponData['value'],
                            'status'         => COUPON_ENABLED,
                        );

        $couponId = null;
        if (!array_key_exists('id', $couponData)) {
            //FIXME: use better code generation
            $insertData['code'] = substr(md5(uniqid(rand(), true)),0,6);
            $couponId           = db_insert('coupons', $insertData);
        }
        else {
            $couponId       = $couponData['id'];
            $updateCriteria = array('id' => $couponId);

            if (!is_null($type) && $type == COUPON_UNIQUE) {
                $coupon         = self::getCoupon($couponId);
                $updateCriteria = array(
                                      'type'           => $coupon['type'],
                                      'expirationDate' => $coupon['expirationDate'],
                                      'startDate'      => $coupon['startDate'],
                                      'value'          => $coupon['value'],
                                      'isPercentage'   => $coupon['isPercentage']);
            }

            db_update('coupons', $insertData, $updateCriteria);
        }

        return $couponId;
    }

    /**
     * Retrieves coupon discount by code given.
     *
     * @param int
     * @param float
     *
     * @return array
     */
    static public function getCouponDiscountByCode($couponCode, $amount) {
        $discount = 0;
        $coupon   = self::getCouponByCode($couponCode);

        if($coupon['isPercentage']) {
            $discount = $amount * ($coupon['value'] / 100);
        }
        else {
            $discount = $coupon['value'];
        }

        return array($discount, $coupon['value'], $coupon['isPercentage']);
    }

    /**
     * Retrieves coupon discount by id given.
     *
     * @param int
     * @param float
     *
     * @return array
     */
    static public function getCouponDiscountById($couponId, $amount) {
        $coupon = self::getCoupon($couponId);

        return self::getCouponDiscountByCode($coupon['code'], $amount);
    }

    /**
     * Retrieves coupon status by coupon code given.
     *
     * @param string
     *
     * @return int
     */
    static public function getCouponStatus($couponCode) {
        $coupon       = self::getCouponByCode($couponCode);
        $couponStatus = COUPON_VALID;

        if (!$coupon) {
            $couponStatus = COUPON_NONEXISTENT;
        }
        else {
            $timesUsed            = self::_getCouponsUsedByUsers(array($couponCode));
            $currentDate          = date('Y-m-d', strtotime("now"));
            $couponStartDate      = date('Y-m-d', strtotime($coupon['startDate']));
            $couponExpirationDate = date('Y-m-d', strtotime($coupon['expirationDate']));

            if ($coupon['type'] == COUPON_UNIQUE && $timesUsed > 0) {
                $couponStatus = COUPON_USED;
            }
            else if ($coupon['status'] == COUPON_DISABLED) {
                $couponStatus = COUPON_DISABLED;
            }
            else{
                if ($currentDate < $couponStartDate) {
                    $couponStatus = COUPON_NONACTIVE;
                }
                else if ($currentDate > $couponExpirationDate) {
                    $couponStatus = COUPON_EXPIRED;
                }
            }
        }

        return $couponStatus;
    }

    /**
     * Retrieves coupon status message depending on status.
     *
     * @param int
     *
     * @return string
     */
    static public function getCouponStatusMessage($couponStatus, $startDate = null){
        $message = '';

        switch ($couponStatus) {
            case COUPON_VALID:
                $message = t('Coupon applied!');
                break;

            case COUPON_NONEXISTENT:
                $message = t('Sorry, the coupon does not exist');
                break;

            case COUPON_DISABLED:
                $message = t('Sorry, the coupon is not enable at this moment');
                break;

            case COUPON_NONACTIVE:
                $message = t('Sorry, the coupon is not active at this moment');

                if (!is_null($startDate)) {
                    $seconds  = strtotime($startDate) - strtotime("now");
                    $days     = round($seconds / 86400);
                    $message .= t(", will be active in ${days} days");
                }
                break;

            case COUPON_USED:
                $message = t('Sorry, this coupon has been already used');
                break;

            case COUPON_EXPIRED:
                $message = t('Sorry, this coupon has expired');
                break;
        }

        return $message;
    }

    /**
     * Retrieves latest order data by user given.
     *
     * @param int
     *
     * @return array
     */
    static public function getLastUserOrder($uid) {
        $sql  = 'SELECT * ';
        $sql .= 'FROM `order` ';
        $sql .= 'WHERE uid = ? ';
        $sql .= 'ORDER BY orderDate DESC ';
        $sql .= 'LIMIT 1';

        $result = db_fetchAssoc($sql, array($uid));

        return $result;
    }

    static public function getOrder($id) {
        $sql  = 'SELECT * ';
        $sql .= 'FROM `order` ';
        $sql .= 'WHERE orderId = ? ';

        $result = db_fetchAssoc($sql, array($id));

        return $result;
    }

    static public function getUserOrders($uid) {
        $sql  = 'SELECT * ';
        $sql .= 'FROM `order` ';
        $sql .= 'WHERE uid = ? ';
        $sql .= 'ORDER BY orderDate DESC ';

        $result = db_fetchAll($sql, array($uid));

        return $result;
    }
    
    static public function getOrderProducts($id) {
        $sql  = 'SELECT * ';
        $sql .= 'FROM orders_by_product AS a '; 
	//$sql .= 'INNER JOIN product AS b ON a.nid=b.nid ';
	//$sql .= 'INNER JOIN url_friendly AS c ON b.id=c.oid ';
        $sql .= 'WHERE a.orderId = ?';

        $result = db_fetchAll($sql, array($id));

        return $result;
    }
    /**
     * Saves a new address in user profile.
     *
     * @param int
     * @param array
     *
     * @return void
     */
    static public function saveUserAddressIfNew($uid, $addressData) {
        $sql  = 'SELECT COUNT(1) ';
        $sql .= 'FROM addresses ';
        $sql .= 'WHERE uid = ? AND address = ? AND countryId = ? ';
        $sql .= 'AND province = ? AND name = ? AND postalCode = ? ';
        $sql .= 'AND city = ? AND phone = ?';

        $fields = array($uid, $addressData['address'], $addressData['countryId'],
                        $addressData['province'], $addressData['name'],                        
			$addressData['postalCode'],$addressData['city'], $addressData['phone']);

        $nif = array_key_exists('nif', $addressData) ? $addressData['nif'] : null;
        if (!is_null($nif)) {
            $sql      .= ' AND nif = ?';
            $fields[] .= $nif;
        }

        $result = db_fetchColumn($sql, $fields);

        if ($result == 0) {
            $addressData['uid'] = $uid;

            unset($addressData['id']);
            unset($addressData['email']);

            UserUtils::saveAddress($addressData);
        }
    }


    /**
     * Saves shipping fees for a country
     *
     * @param string/ integer $countryCode
     * @param array $fees
     *
     * @return void
     */
    static public function saveCountryShippingFees($countryId, $fees) {

        $previousData = self::getCountryShippingFees($countryId);

        $updated = array();
        foreach ($fees as $oneFee) {
            if (!is_null($oneFee['weight']) && !is_null($oneFee['price'])) {
                $oneFee['countryId'] = $countryId;
                if (empty($oneFee['id'])) {
                    db_insert('countryShippingFee', $oneFee);
                }
                else {
                    db_update('countryShippingFee', $oneFee, array('id' => $oneFee['id']));
                    $updated[] = $oneFee['id'];
                }
            }
        }

        //Removes deleted ones
        foreach ($previousData as $onePrevious) {
            if (!in_array($onePrevious['id'], $updated)) {
                db_delete('countryShippingFee', array('id' => $onePrevious['id']));
            }
        }
    }

    /**
     * Get shipping fees for a country
     *
     * @param string $countryCode
     *
     * @return array
     */
    static public function getCountryShippingFees($countryId) {
        $sql = 'SELECT id, weight, price from countryShippingFee WHERE countryId = ?';

        return db_fetchAll($sql, array($countryId));
    }

    /**
     * Callback function the validates form collection in shipping form
     *
     * @param mixed $data
     * @param mixed $context
     * @return void
     */
    static public function validShippingFee($data, $context) {
        $form = $context->getRoot();
        $errorMessage = null;

        foreach ($data as $element) {
            if (!is_null($element['weight']) && $element['weight'] <= 0) {
                $errorMessage = t('Weight must be greater than 0');
            }
            else if ((empty($element['weight']) && !empty($element['price'])) || (!empty($element['weight']) && empty($element['price']))) {
                $errorMessage = t('Si necessary to fill all the fields');
           }
            if (!empty($errorMessage)) {
                $error = new FormError($errorMessage);
                $form->get('shippingFees')->addError($error);
                break;
            }
        }
    }

    /**
     * Giving a country and a weight retrieves the shipping price
     *
     * @param integer $countryId
     * @param integer $weight
     *
     * @return integer: shipping price
     */
    static public function getShippingCostByCountry($countryId, $weight) {

            $sql = 'SELECT weight,price FROM countryShippingFee
                WHERE countryId = ? ORDER BY ABS(weight-?) LIMIT 1';
        $result = db_fetchAssoc($sql, array($countryId, $weight));

        if (is_null($result['weight']) && is_null($result['price'])) {
            $sql = 'SELECT weight,price FROM countryShippingFee
                WHERE countryId = ? ORDER BY ABS(weight-?) LIMIT 1';

            $result = db_fetchAssoc($sql, array($countryId,$weight));

            if (is_null($result['weight']) && is_null($result['price'])) {

                if ($countryId != DEFAULT_SHIPPING_FEE_ID) {
                    $result['price'] = self::getShippingCostByCountry(DEFAULT_SHIPPING_FEE_ID, $weight);
                }

                if (is_null($result['weight']) && is_null($result['price'])) {
                    $result = array('price' => SHIPPING_FEE_DEFAULT_PRICE);
                }
            }
        }

        return $result['price'];
    }

    static public function getShippingPrice($shippingMethod, $shippingInfo) {
        $price = null;

        if (SHIPPING_FEE_ENABLED == SHIPPING_FEE_GENERAL) {
            $shippingValue = CommerceUtils::getShippingInfo($shippingMethod['shipping']);
            $price = $shippingValue['price'];
        }
        else {
            $price = self::getShippingCostByCountry($shippingInfo['countryId'], self::getCartWeight());
        }

        return $price;
    }



    static public function assignTicketNumber () {


	$sql ="SELECT COUNT(*) As invoices FROM `order` WHERE paymentStatus=?";
	
	$result = db_fetchAssoc($sql, array(PAYMENT_STATUS_PAID));

        return $result["invoices"]+SHIFT_TICKET_NUMBER;
    }

    static public function assignInvoiceNumber ($exportZone) {


	$sql ="SELECT COUNT(*) As invoices FROM `order` WHERE paymentStatus=? AND exportZone=?";
	
	$result = db_fetchAssoc($sql, array(PAYMENT_STATUS_PAID, $exportZone));

	$shiftNumber = $exportZone ==  EXPORT_ZONE_EU ? SHIFT_INVOICE_NUMBER : SHIFT_INVOICE_NUMBER_EXPORT;

        return $result["invoices"]+$shiftNumber;
    }

    static public function shippedToExportZone ($shipping) {


	$sql ="SELECT exportZone FROM country WHERE id=?";
	
	$result = db_fetchAssoc($sql, array($shipping['countryId']));

        return $result["exportZone"];
    }
}
