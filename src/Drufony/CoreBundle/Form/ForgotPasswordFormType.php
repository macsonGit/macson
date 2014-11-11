<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ForgotPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->setMethod('POST')
            ->add('email', 'email', array(
                'label' => t('Email')
            ))
            ->add('save', 'submit', array(
                'label' => t('Request new password')
            ));
    }

    public function getName() {
        return 'forgotForm';
    }
}
