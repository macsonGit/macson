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

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\CommerceUtils;
use Symfony\Component\Validator\Constraints\File as FileConstraint;


class SermepaPaymentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $sermepaCurrencies = unserialize(SERMEPA_CURRENCY_EQUIVALENCE);
        $sermepaLanguages = unserialize(SERMEPA_LANGUAGE_EQUIVALENCE);

        $lang = $options['data']['lang'];
        $currency = $options['data']['currency'];

        $currency = array_key_exists($currency, $sermepaCurrencies) ? $sermepaCurrencies[$currency] : $sermepaCurrencies['default'];


	$key=$options['data']['key'];
	$order_number=$options['data']['order'];
        
	$hash= urlencode($key); 	

	$router = getRouter();


        $params = base64_encode(json_encode(array(
                'DS_MERCHANT_AMOUNT' =>(string)$options['data']['amount'] ,
                'DS_MERCHANT_ORDER'=> $order_number,
                'DS_MERCHANT_MERCHANTCODE' => SERMEPA_MERCHANT_CODE,
                'DS_MERCHANT_CURRENCY' => (string)$currency,
                'DS_MERCHANT_TRANSACTIONTYPE'=>(string)SERMEPA_MERCHANT_TRANSACTION_TYPE,
                'DS_MERCHANT_TERMINAL'=>(string)SERMEPA_MERCHANT_TERMINAL,
                'DS_MERCHANT_MERCHANTURL'=>SERMEPA_POST_URL,
                'DS_MERCHANT_URLOK'=>$router->generate('drufony_payment_sermepa_success', array('lang' => $lang, 'paymentHash' =>$order_number ), true),
                'DS_MERCHANT_URLKO'=> $router->generate('drufony_payment_sermepa_error', array('lang' => $lang), true),
        )));




	$signature=base64_encode(hash_hmac(SERMEPA_HASH_ALGORITHM,$params,$key,true));

        $builder
            ->setMethod('POST')
            ->setAction(SERMEPA_URL)
	    ->add('Ds_SignatureVersion','hidden',array(
		'data'=>'HMAC_SHA256_V1',
	    ))
	    ->add('Ds_MerchantParameters','hidden',array(
		'data'=>$params,
	    ))
	    ->add('Ds_Signature','hidden',array(
		'data'=>$signature,
	    ));
    }

    public function getName() {
        return null;
    }
}

