<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->setMethod('POST')
            ->add('username', 'text', array(
                'label' => t('Email'),
                'max_length' => 255,
            ))
            ->add('password', 'password', array(
                'label' => t('Password'),
                'max_length' => 255,
            ))
            ->add('rememberme', 'checkbox', array(
                'label' => t('Remember me'),
                'required' => FALSE,
                'data' => TRUE,
            ))
            ->add('login', 'submit', array(
                'label' => t('Login'),
            ));
    }

    public function getName() {
        return 'loginForm';
    }
}
