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
use Symfony\Component\Validator\Constraints\GreaterThan;

class ShippingWeightFeeFormType extends AbstractType
{
    //FIXME: Add selects for country and province according to database
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->setMethod('POST')
            ->add('weight', 'text', array(
                'label' => t('Up to (Grams)'),
                'required' => false,
                'constraints' => array(
                    new GreaterThanOrEqual(
                        array(
                            'value' => 0,
                            'message' => t('This value should be greater than {{ compared_value }}'),
                        )
                )),
            ))
            ->add('price', 'text', array(
                'label' => t('Price (' . DEFAULT_CURRENCY_SYMBOL . ')'),
                'required' => false,
                'constraints' => array(
                    new GreaterThan(
                        array(
                            'value' => 0,
                            'message' => t('This value should be greater than {{ compared_value }}'),
                        )
                )),
            ))
            ->add('id', 'hidden', array());
    }

    public function getName() {
        return 'shippingWeightFeeForm';
    }
}
