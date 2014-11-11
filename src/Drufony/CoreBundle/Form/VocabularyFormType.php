<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class VocabularyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->setMethod('POST')
            ->add('name', 'text', array(
                'label'      => t('Name'),
                'max_length' => 255,
                'data'       => !empty($options['data']['vocabulary']['name']) ? $options['data']['vocabulary']['name'] : '',
            ))
            ->add('vid', 'hidden', array(
                'data' => !empty($options['data']['vocabulary']['vid']) ? $options['data']['vocabulary']['vid'] : '',
            ))
            ->add('save', 'submit', array(
                'label' => t('Save'),
            ));
    }

    public function getName() {
        return 'vocabularyForm';
    }
}
