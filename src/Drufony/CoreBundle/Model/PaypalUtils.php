<?php

/*
 * Copyright (C) 2014 Crononauta
 * http://crononauta.com
 *
 * Licensed under the Drufony License, Version 1.0 (the "License");
 * you may not use this file except in compliance with the License.
 * More information about this license in the LICENSE.txt file.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Drufony\CoreBundle\Model;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\ShippingAddress;

use Drufony\CoreBundle\Model\Geo;

class PaypalUtils
{
    static public function getApitContext() {

        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                PAYPAL_CLIENT_ID,
                PAYPAL_CLIENT_SECRET
            )
        );

        $apiContext->setConfig(
            array(
                'mode' => PAYPAL_MODE,
                'http.ConnectionTimeOut' => 30,
                //'log.LogEnabled' => true,
                //'log.FileName' => '../PayPal.log',
                //'log.LogLevel' => 'FINE'
            )
        );

        return $apiContext;
    }

    static public function createPayment($cartInfo, $shAddress = null) {
        $apiContext = self::getApitContext();
        $router = getRouter();

        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        $items = array();
        foreach ($cartInfo['cartItems'] as $oneItem) {
            $price = self::__getNumberFormat($oneItem['product']['priceSubtotalNoVat']);
            $paypalItem = new Item();
            $paypalItem->setName($oneItem['product']['title'])
                ->setCurrency(DEFAULT_CURRENCY)
                ->setQuantity($oneItem['count'])
                ->setPrice($price);
            $items[] = $paypalItem;
        }

        if (!empty($cartInfo['discount'])) {
            $discount = self::__getNumberFormat(-1 * $cartInfo['discount']);
            $discountItem = new Item();
            $discountItem->setName(t("Discount"))
                ->setCurrency(DEFAULT_CURRENCY)
                ->setQuantity(1)
                ->setPrice($discount);
            $items[] = $discountItem;
        }

        $itemList = new ItemList();
        $itemList->setItems($items);

        if (!is_null($shAddress)) {
            $shippingAddress = new ShippingAddress();
            $countryCode = Geo::getCountryCodebyId($shAddress['countryId']);

            $shippingAddress->setCity($shAddress['city']);
            $shippingAddress->setCountryCode($countryCode);
            $shippingAddress->setPostalCode($shAddress['postalCode']);
            $shippingAddress->setLine1($shAddress['address']);
            $shippingAddress->setState($shAddress['province']);
            $shippingAddress->setRecipientName($shAddress['name']);

           $itemList->setShippingAddress($shippingAddress);
        }

        $subtotal = self::__getNumberFormat($cartInfo['subtotalProductsDisc']);
        $tax = self::__getNumberFormat($cartInfo['tax']);
        $shipping = self::__getNumberFormat($cartInfo['shippingFee']);
        $details = new Details();
        $details->setShipping($shipping)
            ->setTax($tax)
            ->setSubtotal($subtotal);

        $totalAmount = self::__getNumberFormat($cartInfo['totalDiscounted']);
        $amount = new Amount();
        $amount->setCurrency(DEFAULT_CURRENCY)
            ->setDetails($details)
            ->setTotal($totalAmount);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            //FIXME: set proper description
            ->setDescription(t("Payment of @amount €", array('@amount' => $totalAmount)));

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($router->generate('drufony_payment_paypal_success', array('lang' => getLang()), true))
            ->setCancelUrl($router->generate('drufony_payment_paypal_error', array('lang' => getLang()), true));

        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));

        try {
            $payment->create($apiContext);
        } catch (PayPal\Exception\PPConnectionException $ex) {
            l(ERROR, "Exception: " . $ex->getMessage());
            throw new \Exception($ex->getMessage);
        }

        foreach($payment->getLinks() as $link) {
            if($link->getRel() == 'approval_url') {
                $redirectUrl = $link->getHref();
                break;
            }
        }

        $paymentInfo = new \stdClass();
        $paymentInfo->redirectUrl = $redirectUrl;
        $paymentInfo->id = $payment->getId();

        return $paymentInfo;
    }

    static public function executePayment($paymentId, $payerId) {
        $apiContext = self::getApitContext();
        $payment = Payment::get($paymentId, $apiContext);

        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        $result = $payment->execute($execution,  $apiContext);

        return $result;
    }

    static private function __getNumberFormat($number) {
        $formated = number_format((float)$number, 2, '.', '');

        return $formated;
    }
}
