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

class SelectPaymentMethodFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $choices = array_combine(unserialize(TPV_ENABLED), unserialize(TPV_ENABLED));

        $builder
            ->setMethod('POST')
            //FIXME: adds new payment methods according to a constant
            ->add('method', 'choice', array(
                'choices' => $choices,
                'expanded' => true,
                'required' => true,
            ))
            ->add('submit', 'submit', array(
                'label' => t('Continue'),
            ));
    }

    public function getName() {
        return "selectPaymentMethodForm";
    }
}
