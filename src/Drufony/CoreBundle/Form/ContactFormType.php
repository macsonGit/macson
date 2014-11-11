<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\Task;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->setMethod('POST')
            ->add('name', 'text', array(
                'label'      => t('Name'),
                'max_length' => 255,
                'data'       => '',
            ))
            ->add('email', 'email', array(
                'label'      => t('Email'),
                'max_length' => 255,
                'data'       => '',
            ))
            ->add('phone', 'text', array(
                'label'      => t('Phone number'),
                'required'   => FALSE,
            ))
            ->add('message', 'textarea', array(
                'label'    => t('Message'),
                'data'     => '',
            ))
            ->add('attachment', 'file', array(
                'label'    => t('File'),
                'data'     => '',
                'required' => FALSE,
            ))
            ->add('reset', 'reset', array(
                'label' => t('Reset'),
            ))
            ->add('send', 'submit', array(
                'label' => t('Send'),
            ));
    }
    public function getName() {
        return 'concactForm';
    }
}
