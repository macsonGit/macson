<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\Task;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Custom\ProjectBundle\Model\Store;

class GencatFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
	$stores = Store::getStoreNames();


	$stores_column = array();


	foreach($stores as $row ){
	    if($row['name']){
	    	$stores_column[] = $row['city']." ".$row['address']." ".$row['name'] ;
	    }
	    else{
	    	$stores_column[] = $row['city']." ".$row['address'];
	    }

	}	
	$builder
            ->setMethod('POST')
	    ->add('stores', 'choice', array(
                'label'      => t('Store'),
    		'choices'  => $stores_column,
	    ))
            ->add('email', 'email', array(
                'label'      => t('Email'),
                'max_length' => 255,
                'data'       => '',
                'required'   => FALSE,
            ))
            ->add('message', 'textarea', array(
                'label'    => t('Message'),
                'data'     => '',
                'required'   => FALSE,
            ))
            ->add('send', 'submit', array(
                'label' => t('Send'),
            ));
    }
    public function getName() {
        return 'gencatForm';
    }
}
