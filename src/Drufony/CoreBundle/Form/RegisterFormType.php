<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegisterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->setMethod('POST')
            ->add('email', 'email', array(
                'label' => t('Email'),
                'max_length' => 255,
            ))
            ->add('password', 'password', array(
                'label' => t('Password'),
                'max_length' => 255,
            ));
            if (isset($options['data']['termsReadOnly']) && $options['data']['termsReadOnly']) {
                $builder->add('acceptTerms', 'hidden', array(
                    'label' => t('I accept <a href="@terms" target="_blank">Terms and conditions</a>', array('@terms' => $options['data']['termsUrl'])),
                    'data' => TRUE,
                ));
            }
            else {
                $builder->add('acceptTerms', 'checkbox', array(
                    'label' => t('I accept <a href="@terms" target="_blank">Terms and conditions</a>', array('@terms' => $options['data']['termsUrl'])),
                    'value' => TRUE,
                ));
            }
            $builder->add('register', 'submit', array(
                'label' => t('Register'),
            ));
    }

    public function getName() {
        return 'registerForm';
    }
}
