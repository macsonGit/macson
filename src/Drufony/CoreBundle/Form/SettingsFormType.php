<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Drufony\CoreBundle\Model\Setting;

class SettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->setMethod('POST')
            ->add('emailContentNotifications', 'email', array(
                'label' => t('Email for notifications'),
                'max_length' => 255,
                'required' => FALSE,
                'data' => Setting::get('emailContentNotifications'),
            ))
            ->add('notifyNewUsersCreated', 'checkbox', array(
                'label' => t('New users created'),
                'required' => FALSE,
                'data' => Setting::get('notifyNewUsersCreated'),
            ))
            ->add('notifyCommentsCreated', 'checkbox', array(
                'label' => t('New comments created'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommentsCreated'),
            ))
            ->add('notifyTasksCreated', 'checkbox', array(
                'label' => t('New tasks created'),
                'required' => FALSE,
                'data' => Setting::get('notifyTasksCreated'),
            ))
            ->add('notifyNewTranslationsIsNeeded', 'checkbox', array(
                'label' => t('New translations is needed'),
                'required' => FALSE,
                'data' => Setting::get('notifyNewTranslationsIsNeeded'),
            ))
            ->add('notifyNewTranslationReviewIsNeeded', 'checkbox', array(
                'label' => t('New translation review is needed'),
                'required' => FALSE,
                'data' => Setting::get('notifyNewTranslationReviewIsNeeded'),
            ))
            ->add('notifyNewOrdersCreated', 'checkbox', array(
                'label' => t('New orders created'),
                'required' => FALSE,
                'data' => Setting::get('notifyNewOrdersCreated'),
            ))
            ->add('notifyNewUserModerations', 'checkbox', array(
                'label' => t('New user moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyNewUserModerations'),
            ))
            ->add('notifyNewCommentModerations', 'checkbox', array(
                'label' => t('New comment Moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyNewCommentModerations'),
            ))
            ->add('notifyNewTaskModerations', 'checkbox', array(
                'label' => t('New task moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyNewTaskModerations'),
            ))
            ->add('notifyNewTranslationModerations', 'checkbox', array(
                'label' => t('New translation moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyNewTranslationModerations'),
            ))
            ->add('notifyNewTranslationReviewModerations', 'checkbox', array(
                'label' => t('New translation review moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyNewTranslationReviewModerations'),
            ))
            ->add('notifyNewOrdersModerations', 'checkbox', array(
                'label' => t('New orders moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyNewOrdersModerations'),
            ))
            ->add('notifyChangesOrderStatus', 'checkbox', array(
                'label' => t('Changes in order status'),
                'required' => FALSE,
                'data' => Setting::get('notifyChangesOrderStatus'),
            ))
            //Commerce settings
            ->add('emailCommerceNotifications', 'email', array(
                'label' => t('Email for notifications'),
                'required' => FALSE,
                'data' => Setting::get('emailCommerceNotifications'),
                'max_length' => 255,
            ))
            ->add('notifyCommerceNewUsersCreated', 'checkbox', array(
                'label' => t('New users created'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceNewUsersCreated'),
            ))
            ->add('notifyCommerceCommentsCreated', 'checkbox', array(
                'label' => t('New comments created'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceCommentsCreated'),
            ))
            ->add('notifyCommerceTasksCreated', 'checkbox', array(
                'label' => t('New tasks created'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceTasksCreated'),
            ))
            ->add('notifyCommerceNewTranslationsIsNeeded', 'checkbox', array(
                'label' => t('New translations is needed'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceNewTranslationsIsNeeded'),
            ))
            ->add('notifyCommerceNewTranslationReviewIsNeeded', 'checkbox', array(
                'label' => t('New translation review is needed'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceNewTranslationsIsNeeded'),
            ))
            ->add('notifyCommerceNewOrdersCreated', 'checkbox', array(
                'label' => t('New orders created'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceNewOrdersCreated'),
            ))
            ->add('notifyCommerceNewUserModerations', 'checkbox', array(
                'label' => t('New user moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceNewUserModerations'),
            ))
            ->add('notifyCommerceNewCommentModerations', 'checkbox', array(
                'label' => t('New comment Moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceNewCommentModerations'),
            ))
            ->add('notifyCommerceNewTaskModerations', 'checkbox', array(
                'label' => t('New task moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceNewTaskModerations'),
            ))
            ->add('notifyCommerceNewTranslationModerations', 'checkbox', array(
                'label' => t('New translation moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceNewTranslationModerations'),
            ))
            ->add('notifyCommerceNewTranslationReviewModerations', 'checkbox', array(
                'label' => t('New translation review moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceNewTranslationReviewModerations'),
            ))
            ->add('notifyCommerceNewOrdersModerations', 'checkbox', array(
                'label' => t('New orders moderations'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceNewOrdersModerations'),
            ))
            ->add('notifyCommerceChangesOrderStatus', 'checkbox', array(
                'label' => t('Changes in order status'),
                'required' => FALSE,
                'data' => Setting::get('notifyCommerceChangesOrderStatus'),
            ))
            ->add('shippingRangeCost039', 'money', array(
                'label' => t('Shipping range cost: 0 - 39€'),
                'currency' => DEFAULT_CURRENCY,
                'max_length' => 255,
                'required' => FALSE,
                'data' => Setting::get('shippingRangeCost039'),
            ))
            ->add('shippingRangeCost401000', 'money', array(
                'label' => t('Shipping range cost: 40 - 1000€'),
                'currency' => DEFAULT_CURRENCY,
                'max_length' => 255,
                'required' => FALSE,
                'data' => Setting::get('shippingRangeCost401000'),
            ))
            ->add('maxOrderPrice', 'money', array(
                'label' => t('Maximum order price'),
                'currency' => DEFAULT_CURRENCY,
                'max_length' => 20,
                'required' => FALSE,
                'data' => Setting::get('maxOrderPrice'),
            ))
            ->add('save', 'submit', array(
                'label' => t('Save'),
                'attr' => array('class' => 'btn btn-primary')
            ));

    }

    public function getName() {
        return 'settingsForm';
    }
}
