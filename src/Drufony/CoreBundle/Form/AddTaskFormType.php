<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\Task;

class AddTaskFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->setMethod('POST')
            ->add('title', 'text', array(
                'label'      => t('Title'),
                'max_length' => 255,
                'data'       => !empty($options['data']['task']['title']) ? $options['data']['task']['title'] : '',
            ))
            ->add('description', 'textarea', array(
                'label'    => t('Description'),
                'required' => FALSE,
                'data'    => !empty($options['data']['task']['description']) ? $options['data']['task']['description'] : '',
            ))
            ->add('assigned', 'choice', array(
                'label'       => t('Assigned to'),
                'choices'     => Task::getAllUserTaskable(),
                'required'    => FALSE,
                'empty_value' => t('None'),
                'data'        => !empty($options['data']['task']['uid']) ? $options['data']['task']['uid'] : '',
            ))
            ->add('status', 'choice', array(
                'label'   => t('Status'),
                'choices' => Task::getAllStatus(),
                'data'    => !empty($options['data']['task']['status']) ? $options['data']['task']['status'] : '',
            ))
            ->add('level', 'choice', array(
                'label'   => t('Level'),
                'choices' => Task::getAllLevels(),
                'data'    => !empty($options['data']['task']['level']) ? $options['data']['task']['level'] : '',
            ))
            ->add('save', 'submit', array(
                'label' => t('Save'),
            ));
    }

    public function getName() {
        return 'addTask';
    }
}
