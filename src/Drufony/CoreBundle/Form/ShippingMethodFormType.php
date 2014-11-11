<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\CommerceUtils;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

class ShippingMethodFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $shippingFeeList = CommerceUtils::getShippingList();
        $choices = array();

        $default = null;
        foreach($shippingFeeList as $shipping) {
            if($default == 0) {
                $default = $shipping['id'];
            }
            $choices[$shipping['id']] = $shipping['title'] . " - " . round((float)$shipping['price'], 2) . " " . DEFAULT_CURRENCY;
        }
        $builder
            ->setMethod('POST');
            if (SHIPPING_FEE_ENABLED == SHIPPING_FEE_GENERAL) {
                $builder->add('shipping', 'choice', array(
                    'label' => t('Choices'),
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => $choices,
                    'data' => (!empty($options['data']['info']['shipping'])) ? $options['data']['info']['shipping'] : $default,
                ));
            }
            else {
                $builder->add('shipping', 'hidden', array(
                    'data' => null
                ));
            }
            $builder
		->add('comments', 'ckeditor', array(
                'label' => t('Comments'),
                'data' => (!empty($options['data']['info']['comments'])) ? $options['data']['info']['comments'] : '',
                'required' => false,
            ))
            ->add('send', 'submit', array(
                'label' => t('Continue'),
            ));
    }
    public function getName() {
        return 'shippingMethodForm';
    }
}
