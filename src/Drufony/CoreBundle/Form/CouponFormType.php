<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CouponFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $disableFields = $options['data']['duplicate'];

        $builder
            ->setMethod('POST')
            ->add('typeChoice', 'choice', array(
                'label'      => t('Type'),
                'choices'    => unserialize(COUPON_TYPE_OPTIONS),
                'data'       => $options['data']['type'],
                'disabled'   => true,
            ))
            ->add('type', 'hidden', array(
                'data' => $options['data']['type'],
            ))
            ->add('startDate', 'date', array(
                'label'         => t('Start date'),
                'years'         => range(date('Y') - 10, date('Y') + 10),
                'format'        => 'yyyy-MM-dd',
                'input'         => 'string',
                'data'          => !empty($options['data']['info']['startDate']) ? date('Y-m-d', strtotime($options['data']['info']['startDate'])) : date('Y-m-d', strtotime("now")),
                'disabled'      => $disableFields,
            ))
            ->add('expirationDate', 'date', array(
                'label'         => t('Expiration date'),
                'years'         => range(date('Y') - 10, date('Y') + 10),
                'format'        => 'yyyy-MM-dd',
                'input'         => 'string',
                'data'          => !empty($options['data']['info']['expirationDate']) ? date('Y-m-d', strtotime($options['data']['info']['expirationDate'])) : date('Y-m-d', strtotime("+1 now")),
                'disabled'      => $disableFields ,
            ))
            ->add('isPercentage', 'choice', array(
                'label'      => t('Percentage'),
                'choices'    => array('0' => 'No', '1' => 'Yes'),
                'data'       => !empty($options['data']['info']['isPercentage']) ? $options['data']['info']['isPercentage'] : '0',
                'disabled'   => $disableFields ,
            ))
            ->add('value', 'number', array(
                'label'      => t('Value'),
                'data'       => !empty($options['data']['info']['value']) ? $options['data']['info']['value'] : null,
                'disabled'   => $disableFields ,
            ));
            //if is unique and not editing, show number input
            if($options['data']['type'] == COUPON_UNIQUE && (empty($options['data']['info']) || $disableFields)) {
                $builder->add('number', 'integer', array(
                    'label'      => t('Number of coupons'),
                    'required'   => false,
                    'empty_data' => 1,
                    'data'       => 1,
                ));
            }
            $builder->add('add', 'submit', array(
                'label' => t('Add'),
            ));
    }

    public function getName() {
        return 'couponForm';
    }
}
