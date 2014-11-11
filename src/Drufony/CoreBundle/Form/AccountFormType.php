<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class AccountFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $options['data']['user'];
        $isAdmin = isset($options['data']['isAdmin']) ? $options['data']['isAdmin'] : false;
        $isRecoveryPassword = isset($options['data']['isRecoveryPassword']);
        $builder
            ->setMethod('POST')
            ->add('email', 'email', array(
                'label'     => t('Your email'),
                'data'      => $user->getEmail(),
                'read_only' => TRUE,
            ));
            if (!$isRecoveryPassword && !$isAdmin) {
                $builder->add('password', 'password', array(
                    'label' => t('Your current password'),
                    'constraints' => array(
                        new UserPassword(array(
                            'message' => t('Wrong value for your current password'),
                        )))
                    ));
            }
            $builder->add('newPassword', 'repeated', array(
                'type' => 'password',
                'invalid_message' => t('The password fields must match.'),
                'first_options'   => array('label' => t('New password')),
                'second_options'  => array('label' => t('Repeat new password')),
            ))
            ->add('save', 'submit', array(
                'label' => t('Save changes'),
            ));
    }

    public function getName() {
        return 'accountForm';
    }

}
