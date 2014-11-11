<?php

namespace Drufony\CoreBundle\Model;

/**
 * TPV Interface.
 * 
 */
interface tpvInterface {
    public function addCardToCustomer($uid, $card, $cardHolder = NULL);
    public function processPayment($amount, $currency);
    public function getStoredUserCards($uid);
}

