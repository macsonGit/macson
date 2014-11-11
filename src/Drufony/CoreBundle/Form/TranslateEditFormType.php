<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\Locale;

class TranslateEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $languages = Locale::getAllLanguages();

        //Builds one textarea for each language
        foreach ($languages as $langKey => $langName) {

            if ($langKey != Locale::DRUFONY_DEFAULT_LANG) {
                $original   = $options['data']['string'];
                $params     = array();
                $translated = t($original, $params, $langKey);
                $data       = ($original == $translated) ? '' : $translated;

                $builder->add($langKey, 'textarea', array(
                    'label' => t($langName),
                    'required' => FALSE,
                    'data'  => $data,
                ));
            }
        }

        $builder->setMethod('POST');
        $builder->add('save', 'submit', array('label' => t('Save translation')));
    }
    public function getName() {
        return 'translateEdit';
    }
}
