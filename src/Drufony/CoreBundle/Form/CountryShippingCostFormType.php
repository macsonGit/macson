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
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Drufony\CoreBundle\Model\Geo;
use Drufony\CoreBundle\Form\ShippingWeightFeeFormType;
use Symfony\Component\Validator\Constraints\Callback;

class CountryShippingCostFormType extends AbstractType
{
    //FIXME: Add selects for country and province according to database
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $countries = Geo::getCountriesName();

        $builder
            ->setMethod('POST')
            ->add('default', 'text', array(
                'label' => t('Default price (' . DEFAULT_CURRENCY_SYMBOL . ')'),
                'empty_data' => 0,
                'constraints' => array(
                    new GreaterThanOrEqual(
                        array(
                            'value' => 0,
                            'message' => t('This value should be greater than {{ compared_value }}'),
                        )
                )),
            ))
            ->add('default_id', 'hidden', array())
            ->add('shippingFees', 'collection', array(
                'type' => new ShippingWeightFeeFormType(),
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'prototype_name' => '__shippingfee__',
                'attr' => array('data-prototype-name' => '__shippingfee__'),
                'label' => t('Shipping fees'),
                'options' => array(
                    'label' => ' ',
                    'required' => false,
                ),
                'required' => false,
                'constraints' => array(
                    new Callback(array(
                        'methods' => array(array('Drufony\CoreBundle\Model\CommerceUtils', 'validShippingFee')),
                    ))
                ),
            ))
            ->add('addShippingFee', 'button', array('label' => t('+ Add shipping fee'), 'attr' => array('class' => 'btn add-shipping-fee-btn', 'data-target' => 'countryShippingCostForm_shippingFees')))
            ->add('send', 'submit', array(
                'label' => t('Save'),
            ));
    }

    public function getName() {
        return 'countryShippingCostForm';
    }
}
