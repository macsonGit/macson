<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Drufony\CoreBundle\Model\Locale;

class TranslateSearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $languages = array('' => t('All'));
        $languages += Locale::getAllLanguages();
        $builder
            ->setMethod('POST')
            ->add('search', 'text', array(
                'label'       => t('Search string'),
                'required'    => false,
                'empty_data'  => '',
                //'constraints' => new NotBlank(),
            ))
            ->add('language', 'choice', array(
                'label'    => t('Language'),
                'required' => false,
                'choices'  => $languages,
            ))
            ->add('submit', 'submit', array('label' => t('Search')));
    }
    public function getName() {
        return 'translateSearch';
    }
}
