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
use Drufony\CoreBundle\Model\Geo;

class CountryListFormType extends AbstractType
{
    //FIXME: Add selects for country and province according to database
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $countries = Geo::getCountriesName();

        $countries[DEFAULT_SHIPPING_FEE_ID] = t('Default shipping costs');
        ksort($countries);

        $builder
            ->setMethod('POST')
            ->add('countryId', 'choice', array(
                'label'    => t('Countries'),
                'data'     => !empty($options['data']['info']['countryId']) ? $options['data']['info']['countryId'] : '',
                'empty_value' => t('Select a country'),
                'choices'  => $countries,
            ))
            ->add('send', 'submit', array(
                'label' => t('Manage shipping costs'),
            ));
    }

    public function getName() {
        return 'countryListForm';
    }
}
