<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class HierarchyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('parent', 'choice', array(
                'choices' => array(),
                'label' => t('Parent'),
                'required' => FALSE,
            ));
    }

    public function getName() {
        return 'hierarchyForm';
    }

}
