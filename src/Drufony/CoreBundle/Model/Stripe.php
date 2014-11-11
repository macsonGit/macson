<?php

namespace Drufony\CoreBundle\Model;

use Drufony\CoreBundle\Exception\StripeException;
require_once('../vendor/stripe/lib/Stripe.php');

class Stripe implements tpvInterface {

    /**
     * Checks if a user has a customer object representing him on stripe for this project
     * @param string $param
     *   the field to search the customer
     * @param mixed $value
     *   the value to match the previous field
     * @return \Stripe_Customer
     *   the stripe customer object if it exsits. FALSE otherwise
     */
    private function _getCustomerByParam($param, $value) {
        $customer = FALSE;
        $sql = '
            SELECT *
              FROM stripe_customers
             WHERE '. $param .' = ?
             LIMIT 1
        ';

        $stmt = db_executeQuery($sql, array($value));
        $customerData = $stmt->fetch();
        $stripeId = $customerData['stripe_id'];
        // ONLY ATTEMPT TO RETRIEVE THE CUSTOMER IF IT EXISTS IN OUR DATABASE
        if ($stripeId) {
            // SET OUR PRIVATE API KEY
            \Stripe::setApiKey(STRIPE_PRIVATE_KEY);
            // RETRIEVE THE CUSTOMER BY STRIPE API
            try {
                $customer = \Stripe_Customer::retrieve($customerData['stripe_id']);
            }catch(\Exception $e) {
                throw new StripeException($e);
            }
            // IF THE CUSTOMER IS DELETED, RETURN FALSE
            if(!$customer ||  $customer->deleted) {
                $customer = FALSE;
                $this->deleteCustomer($customerData['uid']);
            } else {
                $customer->uid = $customerData['uid'];
            }
        }
        return $customer;
    }

    /**
     * Checks if a user has a customer object representing him on stripe for this project
     * @param integer $uid
     *   the identifier of the user
     * @return mixed
     *   the stripe customer object if it exsits on both DB and Stripe. FALSE otherwise
     */
    public function getCustomerByUid($uid) {
        return $this->_getCustomerByParam('uid', $uid);
    }

    /**
     * Creates a Stripe user in our database and in Stripe
     * @param array $uid
     *   data of the user
     * @return  \Stripe_Customer
     *  the stripe customer object or FALSE.
     */
    static public function createCustomer($uid) {
        $customer = FALSE;
        \Stripe::setApiKey(STRIPE_PRIVATE_KEY);

        // Customer Data
        $customer_data = array(
            'description' => 'Id:'. $uid,
        );

        // Create the customer on stripe
        try {
            $customer = \Stripe_Customer::create($customer_data);
        }catch(\Exception $e) {
            throw new StripeException($e);
        }

        if($customer) {
            // If its ok, create it on our db
            db_insert('stripe_customers', array(
                'uid'       => $uid,
                'stripe_id' => $customer->id,
                'created'   => date('Y-m-d H:i:s', $customer->created),
            ));
        }
        return $customer;
    }

    /**
     * Deletes a customer from the database
     * @param integer $uid
     *   the user identifier
     */
    public function deleteCustomer($uid) {
        db_delete('stripe_customers', array('uid' => $uid));
    }

    /**
     * Adds a card to a customer. The customer is associated to a User.
     * @param integer $uid
     *   the User unique identifier
     * @param string $card
     *   a Stripe card identifier (token).
     * @param string $cardHolder
     *   card holder data.
     * @return \Stripe_Card
     */
    public function addCardToCustomer($uid, $card, $cardHolder = NULL) {
        $cardObject = FALSE;

        // Recover the customer associated to the given uid
        // or create it if its a new customer
        $customer = $this->getCustomerByUid($uid);
        if ($customer === FALSE) {
            $customer = $this->createCustomer($uid);
        }

        if ($customer !== FALSE) {
            // FIXME: is this what we want to do?
            $this->customer = $customer;
            try {
                \Stripe::setApiKey(STRIPE_PRIVATE_KEY);
                $actualCard = \Stripe_Token::retrieve($card);
                if ($actualCard) {
                    // The customer might already have this card attached to him.
                    // Stripe does not prevent duplicated cards on a customer.
                    if ($customer->cards->count > 0) {
                        // Get the actual card fingerprint.
                        $fingerPrint = $actualCard->card->fingerprint;
                        // Get the cards of this customer and search for a matching fingerprint
                        // Note: 100 is the maximum number of cards that can be retrieven with a single request.
                        $customerCards = $customer->cards->all(array('count' => 100));
                        foreach($customerCards['data'] as $custCard) {
                            if ($custCard['fingerprint'] == $fingerPrint) {
                                $cardObject = $custCard;
                                break;
                            }
                        }
                    }

                    // If the customer does not have this card
                    if(!$cardObject) {
                        // Create a new card in Stripe for this customer.
                        $cardObject = $customer->cards->create(array('card' => $card));
                    }

                    // If cardHolder was passed and its different than
                    // the one associated to the card, update it and save.
                    if($cardHolder && $cardObject->name != $cardHolder) {
                        $cardObject->name = $cardHolder;
                        $cardObject->save();
                    }
                }
            }catch (\Exception $e) {
                throw new StripeException($e);
            }
        }
        return array($customer->id, $cardObject->id);
    }

    /**
     * Process a new payment
     * @param integer $amount
     *   A positive integer in the smallest currency unit (e.g 100 cents to charge â $1.00,
     *   or 1 to charge Â¥1 [a 0-decimal currency]) representing how much to charge the card.
     *   The minimum amount is  $0.50 (or equivalent in charge currency).
     * @param string $currency
     *   3-letter ISO 4217 code for currency.
     * @param string $card
     *   Stripe card token
     * @param string $customer
     *   Stripe customer token
     *
     * @return \Stripe_Charge
     *   Stripe Charge Object or FALSE.
     * @throws StripeException if something fails
     */
    public function processPayment($amount, $currency, $customer = NULL, $card = NULL) {

        // non zero-decimal currencies MUST be charged in cents.
/*        $currency = strtolower($currency);
        $zeroDecimalCurrencies = array_map('strtolower', unserialize(ZERO_DECIMAL_CURRENCIES));
        if(!in_array($currency, $zeroDecimalCurrencies)) {
            $amount *= 100;
        }
*/

        $charge = FALSE;
        // FIXME: maybe show a currency symbol?
        $description = t('%project: Payment of %currency%amount', array('%Project' => PROJECT_NAME, '%currency' => $currency));
        try {
            // Charge a customer with a card.
            \Stripe::setApiKey(STRIPE_PRIVATE_KEY);
            $chargeData = array(
                'amount'      => $amount,
                'currency'    => strtolower($currency),
                'card'        => $card,
                'customer'    => $customer,
                'description' => $description,
            );

            $charge = \Stripe_Charge::create($chargeData);
        } catch (\Exception $e) {
            throw new StripeException($e);
        }

        return $charge;
    }

    /**
     * Gets all stored cards of a user
     * @param integer $uid
     *   the user identifier
     * @return array
     *   an array with all the cards this user has in Stripe
     */
    public function getStoredCards($uid)
    {
        $cards = array();
        $customer = $this->getCustomerByUid($uid);
        if ($customer && $customer->cards->count > 0) {
            // Note: 100 is the maximum number of cards that can be retrieven with a single request
            $cards = $customer::retrieve($customer->id)->cards->all(array('count' => 100));
        }
        return $cards;
    }

    public function getStoredUserCards($uid) {
        $stripeCards = $this->getStoredCards($uid);

        $customer = null;
        $cards = array();
        if(count($stripeCards) > 0) {
            foreach($stripeCards->__toArray()['data'] as $oneCard) {
                $cards[$oneCard->id] = $oneCard->last4;
                $customer = $oneCard->customer;
            }
        }

        return array($customer, $cards);
    }
}

