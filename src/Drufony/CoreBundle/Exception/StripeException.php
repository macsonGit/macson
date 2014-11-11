<?php
/**
 * It defines StripeException Exception Class, which includes:
 *
 *     Constructor.
 *     Several methods to set card error message, and code/error class attributes.
 */

namespace Drufony\CoreBundle\Exception;

/**
 * Custom Exception class, for Stripe related errors
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class StripeException extends \Exception {

    /**
     * Message which will be used to raise the Exception.
     *
     * @var string
     */
    protected $message = "";

    /**
     * This is the code which identifies the Exception.
     *
     * @var int
     */
    protected $code = 0;

    /**
     * Which level is used by default to log messages.
     *
     * @var mixed
     */
    protected $level = ERROR;

    /**
     * StripeException constructor
     *
     * @param \Exception $stripeException
     * @param array $extraParams
     *
     * @return void
     */
    public function __construct(\Exception $stripeException, $extraParams = array())
    {
        // Set custom exception code and message
        $this->_setCodeAndMessage($stripeException);

        // If we receive an STRIPE CARD ERROR, try to get more details
        if($this->code == STRIPE_CARD_DECLINED_ERROR && $stripeException->getCode()) {
            $this->level = WARNING;
            $this->_setCardErrorMessage($stripeException->getCode());
        }

        // Log the exception
        l($this->level, $this->message);

        // TODO: If we receive an user error, maybe we want to notice the administrator about this issue.
        //if($this->level === WARNING && MAIL_ON_STRIPE_CARD_USER_ERROR === 1) {
        //    $data = array();
        //    sendMail(STRIPE_ERROR_ADDRESS, t('Stripe user error'), DEFAULT_EMAIL_ADDRESS, 
        //        'emails/stripe/stripe-card-error', 'text/raw', $data);
        //}
        parent::__construct($this->message, $this->code, $stripeException);
    }

    /**
     * Sets code and message by known Exception
     *
     * @param \Exception $stripeException
     *
     * @return void
     */
    private function _setCodeAndMessage($stripeException) {
        $exceptionClass = get_class($stripeException);

        switch ($exceptionClass) {
            case 'Stripe_CardError':
                // The card was declined.
                $this->code = STRIPE_CARD_DECLINED_ERROR;
                break;

            case 'Stripe_ApiConnectionError':
                // Maybe a network connection issue. Perhaps you need to try again.
                $this->code = STRIPE_NETWORK_ERROR;
                $this->message = $stripeException->getMessage();
                break;

            case 'Stripe_ApiError':
                $this->code = STRIPE_API_ERROR;
                $this->message = $stripeException->getMessage();
                break;

            case 'Stripe_AuthenticationError':
                // Bad api key provided.
                $this->code = STRIPE_INVALID_KEY_ERROR;
                $this->message = $stripeException->getMessage();
                break;

            case 'Stripe_InvalidRequestError':
                // Malformed request. Coding error.
                $this->code = STRIPE_INVALID_REQUEST_ERROR;
                $this->message = $stripeException->getMessage();
                break;

            case 'Stripe_Error':
                // Stripe php Api not found, or similar. Coding error.
                $this->code = STRIPE_GENERIC_ERROR;
                $this->message = $stripeException->getMessage();
                break;

            default:
                // Something happens unrelated to stripe.
                $this->code = STRIPE_GENERIC_ERROR;
                $this->message = $stripeException->getMessage();
        }
    }

    /**
     * Sets the exception message by given code.
     *
     * @param int $code
     *
     * @return void
     */
    private function _setCardErrorMessage($code) {
        switch ($code) {
            case 'incorrect_number':
                $this->message = t('The card number is wrong.');
                break;
            case 'invalid_number':
                $this->message = t('The card number is not a valid credit card number.');
                break;
            case 'invalid_expiry_month':
                $this->message = t('The card\'s expiration month is wrong.');
                break;
            case 'invalid_expiry_year':
                $this->message = t('The card\'s expiration year is wrong.');
                break;
            case 'invalid_cvc':
                $this->message = t('The card\'s security code is wrong.');
                break;
            case 'expired_card':
                $this->message = t('The card has expired.');
                break;
            case 'incorrect_cvc':
                $this->message = t('The card\'s security code is wrong.');
                break;
            case 'incorrect_zip':
                $this->message = t('The card\'s zip code failed validation.');
                break;
            case 'card_declined':
                $this->message = t('The card was declined.');
                break;
            case 'processing_error':
                $this->message = t('An error occurred while processing the card. Please contact with the administrator.');
                break;
            default:
                // Default error message (i.e. customer does not have that card)
                $this->message = t('An error ocurred. Please try again later or contact with the administrator..');
                $this->level = ERROR;
        }
    }
}

