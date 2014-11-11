<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\Locale;
use Drufony\CoreBundle\Model\Menu;

class MenuFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $languagesAvailable = Locale::getAllLanguages();

        if(array_key_exists('excludeId', $options['data']['info'])) {
            $menus = Menu::getMenusForForm($options['data']['info']['excludeId']);
        }
        else {
            $menus = Menu::getMenusForForm();
        }

        $builder
            ->setMethod('POST')
            ->add('type', 'choice', array(
                'label'      => t('Section'),
                'choices'    => unserialize(MENU_TYPE_OPTIONS),
                'data'       => !empty($options['data']['info']['type']) ? $options['data']['info']['type'] : null,
                'disabled'       => array_key_exists('disable', $options['data']['info']) ? true : false,
            ))
            ->add('linkText', 'text', array(
                'label'      => t('Link Text'),
                'max_length' => 255,
                'data'       => !empty($options['data']['info']['linkText']) ? $options['data']['info']['linkText'] : null,
            ))
            ->add('url', 'text', array(
                'label'      => t('Url (path)'),
                'max_length' => 255,
                'data'       => !empty($options['data']['info']['url']) ? $options['data']['info']['url'] : null,
            ))
            ->add('title', 'text', array(
                'label'      => t('Title'),
                'max_length' => 255,
                'data'       => !empty($options['data']['info']['title']) ? $options['data']['info']['title'] : null,
            ))
            ->add('parentId', 'choice', array(
                'label'      => t('Parent'),
                'choices'    =>  $menus,
                'data'       => !empty($options['data']['info']['parentId']) ? $options['data']['info']['parentId'] : null,
                //'disabled'   => array_key_exists('disable', $options['data']['info']) ? true : false,
                'disabled'   => true,
            ))
            ->add('weight', 'choice', array(
                'label'      => t('Weight'),
                'choices'    => array_combine(range(-127, 127),range(-127, 127)),
                'data'       => !empty($options['data']['info']['weight']) ? $options['data']['info']['weight'] : 0,
                'empty_data' => 0,
            ))
            ->add('userTarget', 'choice', array(
                'label'      => t('User target'),
                'choices'    => Menu::getMenuTarget(),
                'data'       => !empty($options['data']['info']['userTarget']) ? $options['data']['info']['userTarget'] : null,
            ))
            ->add('lang', 'choice', array(
                'label'      => t('Language'),
                'choices'    => $languagesAvailable,
                'data'       => !empty($options['data']['info']['lang']) ? $options['data']['info']['lang'] : null,
                'disabled'   => array_key_exists('disable', $options['data']['info']) ? true : false,
            ))
            ->add('add', 'submit', array(
                'label' => t('Add'),
            ));
    }

    public function getName() {
        return 'menuForm';
    }
}
