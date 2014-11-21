<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Drufony\CoreBundle\Model\Geo;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

class PayInfoFormType extends AbstractType
{
    //FIXME: Add selects for country and province according to database
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $countries = Geo::getCountriesName();

        $builder
            ->setMethod('POST')
            ->add('name_shipping', 'text', array(
                'label'      => t('Name or Company'),
                'max_length' => 255,
                'data'       => !empty($options['shipping']['data']['info']['name']) ? $options['shipping']['data']['info']['name'] : '',
            ));
            if (!isset($options['shipping']['data']['notNif'])) {
                $builder->add('nif_shipping', 'text', array(
                    'label'      => t('NIF'),
                    'max_length' => 12,
                    'data'       => !empty($options['shipping']['data']['info']['nif']) ? $options['shipping']['data']['info']['nif'] : '',
                    'required' => false,
                ));
            }
            if (!isset($options['shipping']['data']['isLoggedUser'])) {
                $builder->add('email_shipping', 'email', array(
                    'label'      => t('E-mail address'),
                    'max_length' => 255,
                    'data'       => !empty($options['shipping']['data']['info']['email']) ? $options['shipping']['data']['info']['email'] : '',
                ));
            }
            if (!isset($options['shipping']['data']['notNif'])) {
                $builder->add('nif_billing', 'text', array(
                    'label'      => t('NIF'),
                    'max_length' => 12,
                    'data'       => !empty($options['shipping']['data']['info']['nif']) ? $options['shipping']['data']['info']['nif'] : '',
                    'required' => false,
                ));
            }
            if (!isset($options['shipping']['data']['isLoggedUser'])) {
                $builder->add('email_billing', 'email', array(
                    'label'      => t('E-mail address'),
                    'max_length' => 255,
                    'data'       => !empty($options['shipping']['data']['info']['email']) ? $options['shipping']['data']['info']['email'] : '',
                ));
            }
            $builder->add('address_shipping', 'text', array(
                'label'      => t('Address'),
                'data'       => !empty($options['shipping']['data']['info']['address']) ? $options['shipping']['data']['info']['address'] : '',
                'max_length' => 255,
            ))
            ->add('countryId_shipping', 'choice', array(
                'label'    => t('Country'),
                'data'     => !empty($options['shipping']['data']['info']['countryId']) ? $options['shippung']['data']['info']['countryId'] : '',
                'empty_value' => t('Select a country'),
                'choices'  => $countries,
            ))
            ->add('province_shipping', 'text', array(
                'label' => t('Province/Region'),
                'data'       => !empty($options['shipping']['data']['info']['province']) ? $options['shipping']['data']['info']['province'] : '',
                'max_length' => 255,
            ))
            ->add('city_shipping', 'text', array(
                'label'    => t('City'),
                'data'       => !empty($options['shipping']['data']['info']['city']) ? $options['shipping']['data']['info']['city'] : '',
                'max_length' => 255,
            ))
            ->add('postalCode_shipping', 'text', array(
                'label'    => t('ZIP/Postal Code'),
                'data'       => !empty($options['shipping']['data']['info']['postalCode']) ? $options['shipping']['data']['info']['postalCode'] : null,
                'max_length' => 12,
            ))
            ->add('phone_shipping', 'text', array(
                'label'    => t('Telephone'),
                'data'       => !empty($options['shipping']['data']['info']['phone']) ? $options['shipping']['data']['info']['phone'] : null,
                'max_length' => 32,
            ))
            ->add('name_billing', 'text', array(
                'label'      => t('Name or Company'),
                'max_length' => 255,
                'data'       => !empty($options['billing']['data']['info']['name']) ? $options['billing']['data']['info']['name'] : '',i
	    ))
            ->add('address_billing', 'text', array(
                'label'      => t('Address'),
                'data'       => !empty($options['billing']['data']['info']['address']) ? $options['billing']['data']['info']['address'] : '',
                'max_length' => 255,
            ))
            ->add('countryId_billing', 'choice', array(
                'label'    => t('Country'),
                'data'     => !empty($options['billing']['data']['info']['countryId']) ? $options['billing']['data']['info']['countryId'] : '',
                'empty_value' => t('Select a country'),
                'choices'  => $countries,
            ))
            ->add('province_billing', 'text', array(
                'label' => t('Province/Region'),
                'data'       => !empty($options['billing']['data']['info']['province']) ? $options['billing']['data']['info']['province'] : '',
                'max_length' => 255,
            ))
            ->add('city_billing', 'text', array(
                'label'    => t('City'),
                'data'       => !empty($options['billing']['data']['info']['city']) ? $options['billing']['data']['info']['city'] : '',
                'max_length' => 255,
            ))
            ->add('postalCode_billing', 'text', array(
                'label'    => t('ZIP/Postal Code'),
                'data'       => !empty($options['billing']['data']['info']['postalCode']) ? $options['billing']['data']['info']['postalCode'] : null,
                'max_length' => 12,
            ))
            ->add('phone_billing', 'text', array(
                'label'    => t('Telephone'),
                'data'       => !empty($options['billing']['data']['info']['phone']) ? $options['billing']['data']['info']['phone'] : null,
                'max_length' => 32,
            ))
            ->add('send', 'submit', array(
                'label' => t('Continue'),
            ));


            if (SHIPPING_FEE_ENABLED == SHIPPING_FEE_GENERAL) {
                $builder->add('shipping_method', 'choice', array(
                    'label' => t('Choices'),
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => $choices,
                    'data' => (!empty($options['shipping_method']['data']['info']['shipping'])) ? $options['shipping_method']['data']['info']['shipping'] : $default,
                ));
            }
            else {
                $builder->add('shipping_method', 'hidden', array(
                    'data' => null
                ));
            }
            $builder
		->add('comments', 'ckeditor', array(
                'label' => t('Comments'),
                'data' => (!empty($options['shipping_method']['data']['info']['comments'])) ? $options['shipping_method']['data']['info']['comments'] : '',
                'required' => false,
            ));

        $choices = array_combine(unserialize(TPV_ENABLED), unserialize(TPV_ENABLED));

        $builder
            ->add('method', 'choice', array(
                'choices' => $options['pay_method_choices'],
                'expanded' => true,
                'required' => true,
            ))
            ->add('submit', 'submit', array(
                'label' => t('Continue'),
            ));


    }

    public function getName() {
        return 'payInfoForm';
    }
}
