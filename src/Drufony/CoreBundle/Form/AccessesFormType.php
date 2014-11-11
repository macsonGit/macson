<?php

namespace Drufony\CoreBundle\Form;

use Doctrine\DBAL\Connection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\Access;

class AccessesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->setMethod('POST');
        $accesses = Access::getAllRoleAccesses();
        $roles = Access::getRoles();
        $roleAccess = array();
        foreach($accesses as $key => $access) {
            $rolePerm = array_keys($roles);
            foreach($rolePerm as $role) {
                $roleAccess[$access['access']][$role] = array($role . '[' . $access['access'] . ']' => Access::roleHasAccess($role, $access['access']));
            }
        }
        foreach ($roleAccess as $key => $access) {
            $data = array();
            foreach ($access as $roleAccess) {
                $keys = array_keys($roleAccess, 1);
                if (!empty($keys)) {
                    $data[] = $keys[0];
                }
            }
            $builder->add(str_replace(' ', '_', $key), 'choice', array(
                'choices'  => $access,
                'label'    => $key,
                'expanded' => TRUE,
                'multiple' => TRUE,
                'data'     => array_values($data),
            ));
        }
        $builder->add('save', 'submit', array(
            'attr' => array('class' => 'save')));
    }

    public function getName()
    {
        return 'accessesForm';
    }
}



