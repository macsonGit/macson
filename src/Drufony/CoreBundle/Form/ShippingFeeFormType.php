<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\Task;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

class ShippingFeeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->setMethod('POST')
            ->add('title', 'text', array(
                'label'      => t('Title'),
                'max_length' => 255,
                'data'       => '',
            ))
            ->add('description', 'textarea', array(
                'label'      => t('Description'),
                'required'   => FALSE,
                'data'       => '',
            ))
            ->add('freeThreshold', 'number', array(
                'label'      => t('Free Threshold'),
            ))
            ->add('price', 'number', array(
                'label'    => t('Price'),
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
