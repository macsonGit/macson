<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CsvUploaderFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->setMethod('POST')
            ->add('csv', 'file', array(
                'label' => t('Csv to import'),
                ))
            ->add('save', 'submit', array(
                'label' => t('Import'),
            ));
    }

    public function getName() {
        return 'addTask';
    }
}

