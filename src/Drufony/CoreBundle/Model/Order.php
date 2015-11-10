<?php

namespace Drufony\CoreBundle\Model;

defined('DEFAULT_VAT') or define('DEFAULT_VAT', 21);
/**
 * Represent a purchase of the user
 */
class Order
{
    private $orderId;
    private $orderDate;
    private $paymentMethod;
    private $paymentStatus;
    private $discount;
    private $total;
    //private $dispute;
    private $paymentPlatform;
    private $shippingStatus;
    private $billingInfo;
    private $shippingInfo;
    private $subtotal_with_vat;
    private $shippingValue;
    //private $refund;
    private $cardLastDigits;
    private $cardCountry;
    private $shippingId;
    private $shippingData;
    private $products;
    private $currency;
    private $orderStatus;
    private $comments;
    private $couponId;
    private $invoiceNumber;

    /**
     * Getters methods
     */
    public function getOrderId() { return $this->orderId; }
    public function getOrderDate() { return $this->orderDate; }
    public function getPaymentMethod() { return $this->paymentMethod; }
    public function getPaymentStatus() { return $this->paymentStatus; }
    public function getDiscount() { return $this->discount; }
    public function getTotal() { return $this->total; }
    //public function getDispute() { return $this->dispute; }
    public function getPaymentPlatform() { return $this->paymentPlatform; }
    public function getShippingStatus() { return $this->shippingStatus; }
    public function getBillingInfo() { return $this->billingInfo; }
    public function getShippingInfo() { return $this->shippingInfo; }
    public function getSubtotalWithVat() { return $this->subtotal_with_vat; }
    public function getShippingValue() { return $this->shippingValue; }
    //public function getRefund() { return $this->refund; }
    public function getCardLastDigits() { return $this->cardLastDigits; }
    public function getCardCountry() { return $this->cardCountry; }
    public function getCurrency() { return $this->currency; }
    //public function getShippingData() { return $this->shippingData; }
    public function getOrderStatus() { return $this->orderStatus; }
    public function getComments() { return $this->comments; }
    public function getCouponId() { return $this->couponId; }
    public function getInvoiceNumber() { return $this->invoiceNumber; }

    /**
     * Construct a order object
     *
     * @param int $orderId; order id in the database
     */
    public function __construct($orderId) {
        $this->_getOrderFromDB($orderId);
    }

    /**
     * Retrieve all the products of a an order
     *
     * @return array; with all the producst data
     */
    public function getProducts() {
        if(!$this->products) {
            $this->products = $this->_getProducstFromDB();
        }
        return $this->products;
    }

    /**
     * Retrieves the products from db for this order
     *
     * @return array
     */
    private function _getProducstFromDB() {
        $products = array();

        $sql = 'SELECT nid, quantity, varieties, percentage_vat, total, currency, total_vat, subtotal_without_vat ';
        $sql .= 'FROM orders_by_product ';
        $sql .= 'WHERE orderId = ?';

        if($result = db_executeQuery($sql, array($this->orderId))) {
            while($row = $result->fetch()) {
                $products[] = $row;
            }
        }

        return $products;
    }

    static public function getProductsFromDB($orderId) {
        $products = array();

        $sql = 'SELECT quantity, varieties,title, sgu, pricePVP ';
        $sql .= 'FROM orders_by_product INNER JOIN product ON product.nid = orders_by_product.nid ';
        $sql .= 'WHERE orderId = ?';

        if($result = db_executeQuery($sql, array($orderId))) {
            while($row = $result->fetch()) {
                $products[] = $row;
            }
        }

        return $products;
    }
    /**
     * Relate a coupon with the order
     *
     * @param mixed $couponId
     * @return void
     */
    public function applyCoupon($couponId) {
        list($discount, $couponDiscount, $discountType) = CommerceUtils::getCouponDiscountById($couponId, $this->total);
        $updateData = array('couponId' => $couponId, 'discount' => $discount);
        $updateCriteria = array('orderId' => $this->getOrderId());

        db_update('`order`', $updateData, $updateCriteria);
    }

    /**
     * Retrieve the shipping data for the current order
     *
     * @return array
     */
    public function getShippingData() {
        if(!$this->shippingData) {
            $this->shippingData = $this->_getShippingDataFromDB();
        }
        return $this->shippingData;
    }

    /**
     * Retrievces the shipping data for the current order from database
     *
     * @return array
     */
    private function _getShippingDataFromDB(){
        $sql = 'SELECT title, description, freeThreshold, price ';
        $sql .= 'FROM shipping_fee ';
        $sql .= 'WHERE id = ?';

        $shippingInfo = null;
        if($result = db_executeQuery($sql, array($this->shippingId))){
            $shippingInfo = $result->fetch();
        }
        return $shippingInfo;
    }

    /**
     * Retrieve order from db, throw exception if it does not exist
     *
     * @param int $orderId; order id in database
     */
    private function _getOrderFromDB($orderId){
        $sql = 'SELECT * ';
        $sql .= 'FROM `order` ';
        $sql .= 'WHERE orderId = ?';

        if($result = db_executeQuery($sql, array($orderId))) {
            if(!empty($result)) {
                $order = $result->fetch();
                $this->orderId = $order['orderId'];
                $this->orderDate = $order['orderDate'];
                $this->paymentMethod = $order['paymentMethod'];
                $this->paymentStatus = $order['paymentStatus'];
                $this->discount = $order['discount'];
                $this->total = $order['total'];
                //$this->dispute = $order['dispute'];
                $this->paymentPlatform = $order['paymentPlatform'];
                $this->shippingStatus = $order['shippingStatus'];
                $this->billingInfo = unserialize($order['billingInfo']);
                $this->shippingInfo = unserialize($order['shippingInfo']);
                $this->shippingId = $order['shippingId'];
                $this->subtotal_with_vat = $order['subtotal_with_vat'];
                $this->shippingValue = $order['shippingValue'];
                //$this->refund = $order['refund'];
                $this->cardLastDigits = $order['cardLastDigits'];
                $this->cardCountry = $order['cardCountry'];
                $this->shippingId = $order['shippingId'];
                $this->comments = $order['comments'];
                $this->orderStatus = $order['orderStatus'];
                $this->couponId = $order['couponId'];
                $this->invoiceNumber = $order['invoiceNumber'];
            }
            else {
                throw \Exception("Order $orderId does not exist");
            }
        }
    }
    static public function getOrderInfo($orderId){
        $sql = 'SELECT * ';
        $sql .= 'FROM `order` ';
        $sql .= 'WHERE orderId = ?';

        if($result = db_executeQuery($sql, array($orderId))) {
            if(!empty($result)) {
                $order = $result->fetch();
		return $order;
            }
            else {
                throw \Exception("Order $orderId does not exist");
            }
        }
    }
}
