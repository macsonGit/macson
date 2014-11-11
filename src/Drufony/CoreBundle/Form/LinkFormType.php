<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Url;

class LinkFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('url', 'text', array(
                'label' => t('URL'),
                'required' => FALSE,
                'max_length' => 255,
                'constraints' => array(
                    new Url(
                        array(
                            'message' => t('This value is not a valid URL')
                        ))
                    )
                ))
            ->add('title', 'text', array(
                'label' => t('Link text'),
                'required' => FALSE,
                'max_length' => 255,
            ))
            ->add('newWindow', 'checkbox', array(
                'label' => t('Opens in new window'),
                'required' => FALSE,
            ));
    }
    public function getName() {
        return 'linkForm';
    }
}
