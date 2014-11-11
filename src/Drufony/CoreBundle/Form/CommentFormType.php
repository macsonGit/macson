<?php

namespace Drufony\CoreBundle\Form;

use Drufony\CoreBundle\Model\Content;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        global $router;
        global $lang;
        $builder
            ->setMethod('POST')
            ->setAction($router->generate('drufony_content_actions', array(
                'lang' => $lang, 'contentType' => Content::TYPE_COMMENT,
                'action' => 'create'
            )))
            ->add('subject', 'text', array(
                'label' => t('Subject'),
                'max_length' => 255,
                'required' => FALSE,
            ))
            ->add('body', 'textarea', array(
                'label' => t('Comment'),
                'max_length' => 1000,
            ))
            ->add('pid', 'hidden', array(
                'data' => !empty($options['data']['pid']) ? $options['data']['pid'] : 0,
            ))
            ->add('nid', 'hidden', array(
                'data' => !empty($options['data']['node']) ? $options['data']['node']->getNid() : '',
            ))
            ->add('id', 'hidden', array(
                'data' => !empty($options['data']['node']) ? $options['data']['node']->getId() : '',
            ))
            ->add('cid', 'hidden', array(
                'data' => 0,
            ))
            ->add('destination', 'hidden', array(
                'data' => !empty($options['data']['destination']) ? $options['data']['destination'] : '',
            ))
            ->add('send', 'submit', array(
                'label' => t('Send'),
            ));
    }

    public function getName() {
        return 'commentForm';
    }

}
