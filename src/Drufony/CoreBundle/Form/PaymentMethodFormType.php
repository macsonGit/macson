<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\CommerceUtils;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

class PaymentMethodFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $actYear = date('Y', time());
        $builder
            ->setMethod('POST')
            //FIXME: adds new payment methods according to a constant
            ->add('payment', 'hidden', array(
                //'label'    => t('Choices'),
                //'expanded' => true,
                //'multiple' => false,
                //'choices'  => array('1' => 'Stripe'),
                'data'     => TPV_STRIPE_TYPE,                
		//'data'     => (!empty($options['data']['info']['payment'])) ? $options['data']['info']['payment'] : null,
            ))
            ->add('cardHoldername', 'text', array(
                'label'      => t('Credit card holder'),
                'data'       => (!empty($options['data']['info']['cardHoldername'])) ? $options['data']['info']['cardHoldername'] : null,
                'max_length' => 255,
                //'required'   => FALSE,
                'attr'       => array(
                    'data-stripe' => 'name',
                ),
            ))
            ->add('cardNumber', 'number', array(
                'label' => t('Credit card number'),
                'data'  => (!empty($options['data']['info']['cardNumber'])) ? $options['data']['info']['cardNumber'] : null,
                'max_length' => 20,
                'precision'  => 0,
                //'required'   => FALSE,
                'attr'       => array(
                    'autocomplete' => 'off',
                    'data-stripe'  => 'number',
                ),
            ))
            ->add('expirationMonth', 'choice', array(
                'label'      => t('Expiration date'),
                'choices'    => array_combine(range(1, 12), range(1, 12)),
                'data'       => (!empty($options['data']['info']['expirationMonth'])) ? $options['data']['info']['expirationMonth'] : null,
                //'required'   => FALSE,
                'attr'       => array(
                    'data-stripe'   => 'exp-month',
                ),
            ))
            ->add('expirationYear', 'choice', array(
                'choices'    => array_combine(range($actYear, $actYear + 20), range($actYear, $actYear + 20)),
                'data'       => (!empty($options['data']['info']['expirationYear'])) ? $options['data']['info']['expirationYear'] : null,
                //'required'   => FALSE,
                'attr'       => array(
                    'data-stripe'   => 'exp-year',
                ),
            ))
            ->add('cardVerificationNumber', 'number', array(
                'label'      => t('Credit verification number'),
                'data'       => (!empty($options['data']['info']['cardVerificationNumber'])) ? $options['data']['info']['cardVerificationNumber'] : null,
                'max_length' => 4,
                'precision'  => 0,
                //'required'    => FALSE,
                'attr'       => array(
                    'autocomplete'  => 'off',
                    'data-stripe'   => 'cvc',
                ),
            ));
            if(isset($options['data']['info']['storedCards'])) {
                $builder->add('storedMethod', 'choice', array(
                    'label'       => t('Select card, finished in'),
                    'choices'     => $options['data']['info']['storedCards'],
                    'empty_value' => t('New card'),
                    'required'    => FALSE,
                    ));
            }
            else{
                $builder->add('storedMethod', 'hidden', array(
                    'data'     => '',
                    ));
            }
            $builder->add('token', 'hidden')
            ->add('cardLastDigits', 'hidden')
            ->add('selectedPrevious', 'hidden')
            ->add('send', 'submit', array(
                'label' => t('Submit order'),
            ));
    }
    public function getName() {
        return null;
    }
}
