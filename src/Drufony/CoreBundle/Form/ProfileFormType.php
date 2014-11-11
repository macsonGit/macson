<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->setMethod('POST')
            ->add('name', 'text', array(
                'label'  => t('Profile name'),
                'max_length' => 255,
            ))
            ->add('mobile', 'text', array(
                'label' => t('Mobile'),
                'required' => FALSE,
                'max_length' => 20,
                'constraints' => array(
                    new Regex(array(
                        'pattern' => '/\d+/',
                        'message' => t('Only numbers must be inserted'),
                    ))
                )
            ))
            ->add('phone', 'text', array(
                'label' => t('Phone'),
                'required' => FALSE,
                'max_length' => 20,
                'constraints' => array(
                    new Regex(array(
                        'pattern' => '/\d+/',
                        'message' => t('Only numbers must be inserted'),
                    ))
                )
            ))
            ->add('save', 'submit', array(
                'label' => t('Save'),
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Drufony\CoreBundle\Model\Profile',
        ));
    }
    public function getName() {
        return 'profileForm';
    }
}
