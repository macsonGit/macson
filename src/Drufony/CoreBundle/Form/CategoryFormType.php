<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\Category;

class CategoryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        list($parents, $children) = Category::getCategoryHierarchyByVocabulary($options['data']['vid']);
        $categories = Category::getFormatedCategory($parents, $children);

        $builder
            ->setMethod('POST')
            ->add('name', 'text', array(
                'label'      => t('Name'),
                'max_length' => 255,
                'data'       => !empty($options['data']['category']['name']) ? $options['data']['category']['name'] : '',
            ))
            ->add('parentId', 'choice', array(
                'label'    => t('Parent'),
                'choices'  => $categories,
                'required' => false,
                'data'    => !empty($options['data']['category']['parentId']) ? $options['data']['category']['parentId'] : null,
            ))
            ->add('tid', 'hidden', array(
                'data'    => !empty($options['data']['category']['tid']) ? $options['data']['category']['tid'] : null,
            ))
            ->add('vid', 'hidden', array(
                'data' => $options['data']['vid'],
            ))
            ->add('save', 'submit', array(
                'label' => t('Save'),
            ));
    }

    public function getName() {
        return 'categoryForm';
    }
}
