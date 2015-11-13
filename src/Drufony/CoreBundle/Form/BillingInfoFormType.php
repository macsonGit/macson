<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Drufony\CoreBundle\Model\Geo;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

class BillingInfoFormType extends AbstractType
{
    //FIXME: Add selects for country and province according to database
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $countries = Geo::getCountriesName();

        $builder
            ->setMethod('POST')
            ->add('name', 'text', array(
                'label'      => t('Name'),
                'max_length' => 255,
                'data'       => !empty($options['data']['info']['name']) ? $options['data']['info']['name'] : '',
            ));
            if (!isset($options['data']['notNif'])) {
                $builder->add('nif', 'text', array(
                    'label'      => t('NIF'),
                    'max_length' => 12,
                    'data'       => !empty($options['data']['info']['nif']) ? $options['data']['info']['nif'] : '',
                    'required' => false,
                ));
            }
            if (!isset($options['data']['isLoggedUser'])) {
                $builder->add('email', 'email', array(
                    'label'      => t('E-mail address'),
                    'max_length' => 255,
                    'data'       => !empty($options['data']['info']['email']) ? $options['data']['info']['email'] : '',
                ));
            }
            $builder->add('address', 'text', array(
                'label'      => t('Address'),
                'data'       => !empty($options['data']['info']['address']) ? $options['data']['info']['address'] : '',
                'max_length' => 255,
            ))
            ->add('countryId', 'choice', array(
                'label'    => t('Country'),
                'data'     => !empty($options['data']['info']['countryId']) ? $options['data']['info']['countryId'] : '',
                'empty_value' => t('Select a country'),
                'choices'  => $countries,
            ))
            ->add('province', 'text', array(
                'label' => t('Province/Region'),
                'data'       => !empty($options['data']['info']['province']) ? $options['data']['info']['province'] : '',
                'max_length' => 255,
            ))
            ->add('city', 'text', array(
                'label'    => t('City'),
                'data'       => !empty($options['data']['info']['city']) ? $options['data']['info']['city'] : '',
                'max_length' => 255,
            ))
            ->add('postalCode', 'text', array(
                'label'    => t('ZIP/Postal Code'),
                'data'       => !empty($options['data']['info']['postalCode']) ? $options['data']['info']['postalCode'] : null,
                'max_length' => 12,
            ))
            ->add('phone', 'text', array(
                'label'    => t('Telephone'),
                'data'       => !empty($options['data']['info']['phone']) ? $options['data']['info']['phone'] : null,
                'max_length' => 32,
            ))
            ->add('send', 'submit', array(
                'label' => t('Continue'),
            ));
    }

    public function getName() {
        return 'billingInfoForm';
    }
}
