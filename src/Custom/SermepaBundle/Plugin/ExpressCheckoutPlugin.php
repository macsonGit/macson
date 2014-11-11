<?php

namespace Custom\SermepaBundle\Plugin;

use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\Exception\PaymentPendingException;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Util\Number;
use Custom\SermepaBundle\Client\Client;
use Custom\SermepaBundle\Client\Response;

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class ExpressCheckoutPlugin extends AbstractPlugin
{
    /**
     * @var string
     */
    protected $returnUrl;

    /**
     * @var string
     */
    protected $cancelUrl;

    /**
     * @var \JMS\Payment\SermepaBundle\Client\Client
     */
    protected $client;

    /**
     * @param string $returnUrl
     * @param string $cancelUrl
     * @param \JMS\Payment\SermepaBundle\Client\Client $client
     */
    public function __construct($returnUrl, $cancelUrl, Client $client)
    {
        $this->client = $client;
        $this->returnUrl = $returnUrl;
        $this->cancelUrl = $cancelUrl;
    }

    public function approve(FinancialTransactionInterface $transaction, $retry)
    {
        $this->createCheckoutBillingAgreement($transaction, 'Authorization');
    }

    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->createCheckoutBillingAgreement($transaction, 'Sale');
    }

    public function credit(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();
        $approveTransaction = $transaction->getCredit()->getPayment()->getApproveTransaction();

        $parameters = array();
        if (Number::compare($transaction->getRequestedAmount(), $approveTransaction->getProcessedAmount()) !== 0) {
            $parameters['REFUNDTYPE'] = 'Partial';
            $parameters['AMT'] = $this->client->convertAmountToSermepaFormat($transaction->getRequestedAmount());
            $parameters['CURRENCYCODE'] = $transaction->getCredit()->getPaymentInstruction()->getCurrency();
        }

        $response = $this->client->requestRefundTransaction($data->get('authorization_id'), $parameters);

        $this->throwUnlessSuccessResponse($response, $transaction);

        $transaction->setReferenceNumber($response->body->get('REFUNDTRANSACTIONID'));
        $transaction->setProcessedAmount($response->body->get('NETREFUNDAMT'));
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
    }

    public function deposit(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();
        $authorizationId = $transaction->getPayment()->getApproveTransaction()->getReferenceNumber();

        if (Number::compare($transaction->getPayment()->getApprovedAmount(), $transaction->getRequestedAmount()) === 0) {
            $completeType = 'Complete';
        }
        else {
            $completeType = 'NotComplete';
        }

        $response = $this->client->requestDoCapture($authorizationId, $transaction->getRequestedAmount(), $completeType, array(
            'CURRENCYCODE' => $transaction->getPayment()->getPaymentInstruction()->getCurrency(),
        ));
        $this->throwUnlessSuccessResponse($response, $transaction);

        $details = $this->client->requestGetTransactionDetails($authorizationId);
        $this->throwUnlessSuccessResponse($details, $transaction);

        switch ($details->body->get('PAYMENTSTATUS')) {
            case 'Completed':
                break;

            case 'Pending':
                throw new PaymentPendingException('Payment is still pending: '.$response->body->get('PENDINGREASON'));

            default:
                $ex = new FinancialException('PaymentStatus is not completed: '.$response->body->get('PAYMENTSTATUS'));
                $ex->setFinancialTransaction($transaction);
                $transaction->setResponseCode('Failed');
                $transaction->setReasonCode($response->body->get('PAYMENTSTATUS'));

                throw $ex;
        }

        $transaction->setReferenceNumber($authorizationId);
        $transaction->setProcessedAmount($details->body->get('AMT'));
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
    }

    public function reverseApproval(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();

        $response = $this->client->requestDoVoid($data->get('authorization_id'));
        $this->throwUnlessSuccessResponse($response, $transaction);

        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
    }

    public function processes($paymentSystemName)
    {
        return 'sermepa_express_checkout' === $paymentSystemName;
    }

    public function isIndependentCreditSupported()
    {
        return false;
    }

    protected function createCheckoutBillingAgreement(FinancialTransactionInterface $transaction, $paymentAction)
    {
       $data = $transaction->getExtendedData();

      // $data->get('Ds_Signature');
/*
       1. Comprueba Signature

		- si existe:

       			-. Comprueba no error

       				No error -> Valida Resultados

       					     Salva resultados y guarda en memoria.


		- no existe:

			- Crea URL de pago POST. Invoca Curl y renderiza resultados
*/




        // complete the transaction

        /*$response = $this->client->requestDoExpressCheckoutPayment(
            $data->get('express_checkout_token'),
            $transaction->getRequestedAmount(),
            $paymentAction,
            array('PAYMENTREQUEST_0_CURRENCYCODE' => $transaction->getPayment()->getPaymentInstruction()->getCurrency())
        );
        $this->throwUnlessSuccessResponse($response, $transaction);
*/
        switch(substr($response->body->get('Ds_Response'),0,1)) {
            case '0':
                break;

            case '1':
                $transaction->setReferenceNumber($response->body->get('Ds_Response'));
                
                throw new PaymentPendingException('Payment is still pending: '.$response->body->get('Ds_Response'));

            default:
                $ex = new FinancialException('PaymentStatus is not completed: '.$response->body->get('PAYMENTINFO_0_PAYMENTSTATUS'));
                $ex->setFinancialTransaction($transaction);
                $transaction->setResponseCode('Failed');
                $transaction->setReasonCode($response->body->get('PAYMENTINFO_0_PAYMENTSTATUS'));

                throw $ex;
        }

        $transaction->setReferenceNumber($response->body->get('Ds_Order'));
        $transaction->setProcessedAmount($response->body->get('Ds_Amount'));
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
    }

    /**
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     * @param string $paymentAction
     *
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException if user has to authenticate the token
     *
     * @return string
     */
    protected function obtainExpressSignature(FinancialTransactionInterface $transaction, $paymentAction)
    {
        $data = $transaction->getExtendedData();
        if ($data->has('express_checkout_token')) {
            return $data->get('express_checkout_token');
        }

        $opts = $data->has('checkout_params') ? $data->get('checkout_params') : array();
        $opts['PAYMENTREQUEST_0_PAYMENTACTION'] = $paymentAction;
        $opts['PAYMENTREQUEST_0_CURRENCYCODE'] = $transaction->getPayment()->getPaymentInstruction()->getCurrency();

        $response = $this->client->requestSetExpressCheckout(
            $transaction->getRequestedAmount(),
            $this->getReturnUrl($data),
            $this->getCancelUrl($data),
            $opts
        );



        //$this->throwUnlessSuccessResponse($response, $transaction);

        $data->set('express_checkout_token', $response->body->get('TOKEN'));

        $authenticateTokenUrl = $this->client->getAuthenticateExpressCheckoutTokenUrl($response->body->get('TOKEN'));

        $actionRequest = new ActionRequiredException('User must authorize the transaction.');
        $actionRequest->setFinancialTransaction($transaction);
        $actionRequest->setAction(new VisitUrl($authenticateTokenUrl));

        throw $actionRequest;
    }

    /**
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     * @param \JMS\Payment\SermepaBundle\Client\Response $response
     * @return null
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     */
    protected function throwUnlessSuccessResponse(Response $response, FinancialTransactionInterface $transaction)
    {
        if ($response->isSuccess()) {
            return;
        }

        $transaction->setResponseCode($response->body->get('ACK'));
        $transaction->setReasonCode($response->body->get('L_ERRORCODE0'));

        $ex = new FinancialException('Sermepa-Response was not successful: '.$response);
        $ex->setFinancialTransaction($transaction);

        throw $ex;
    }

    protected function getReturnUrl(ExtendedDataInterface $data)
    {
        if ($data->has('return_url')) {
            return $data->get('return_url');
        }
        else if (0 !== strlen($this->returnUrl)) {
            return $this->returnUrl;
        }

        throw new \RuntimeException('You must configure a return url.');
    }

    protected function getCancelUrl(ExtendedDataInterface $data)
    {
        if ($data->has('cancel_url')) {
            return $data->get('cancel_url');
        }
        else if (0 !== strlen($this->cancelUrl)) {
            return $this->cancelUrl;
        }

        throw new \RuntimeException('You must configure a cancel url.');
    }
}
