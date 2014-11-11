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
        $firm = $options['data']['amount'] . date('ymdHis') . SERMEPA_MERCHANT_CODE . $currency . SERMEPA_MERCHANT_TRANSACTION_TYPE . SERMEPA_MERCHANT_KEY;
        $firmHash = strtoupper(sha1($firm));

        $router = getRouter();

        $builder
            ->setMethod('POST')
            ->setAction(SERMEPA_URL)
            ->add('Ds_Merchant_Amount', 'hidden', array(
                'data' => $options['data']['amount'],
            ))
            ->add('Ds_Merchant_Currency', 'hidden', array(
                'data' => $currency,
            ))
            ->add('Ds_Merchant_Order', 'hidden', array(
                'data' => date('ymdHis'),
            ))
            ->add('Ds_Merchant_Titular', 'hidden', array(
                'data' => $options['data']['titular'],
            ))
            ->add('Ds_Merchant_MerchantCode', 'hidden', array(
                'data' => SERMEPA_MERCHANT_CODE,
            ))
            ->add('Ds_Merchant_MerchantName', 'hidden', array(
                'data' => SERMEPA_MERCHANT_NAME,
            ))
            ->add('Ds_Merchant_Terminal', 'hidden', array(
                'data' => SERMEPA_MERCHANT_TERMINAL,
            ))
            ->add('Ds_Merchant_TransactionType', 'hidden', array(
                'data' => SERMEPA_MERCHANT_TRANSACTION_TYPE,
            ))
            ->add('Ds_Merchant_MerchantURL', 'hidden', array(
                'data' => null,
            ))
            ->add('Ds_Merchant_UrlOK', 'hidden', array(
                'data' => $router->generate('drufony_payment_sermepa_success', array('lang' => $lang, 'paymentHash' => $firmHash), true),
            ))
            ->add('Ds_Merchant_UrlKO', 'hidden', array(
                'data' => $router->generate('drufony_payment_sermepa_error', array('lang' => $lang), true),
            ))
            ->add('Ds_Merchant_MerchantSignature', 'hidden', array(
                'data' => $firmHash,
            ))
            ->add('Ds_Merchant_ConsumerLanguage', 'hidden', array(
                'data' => array_key_exists($lang, $sermepaLanguages) ? $sermepaLanguages[$lang] : $sermepaLanguages['default'],
            ));
    }

    public function getName() {
        return null;
    }
}

