<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\Constraints\Url;

class AttachmentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('file', 'file', array(
                'label' => t('File'),
                'required' => FALSE,
                    'constraints' => array(
                        new FileConstraint(
                            array(
                                'maxSize' => MAX_ATTACHMENT_FILE_SIZE,
                                'maxSizeMessage' => t('The file is too large ({{ size }} MB). Allowed maximum size is {{ limit }} MB'),
                            )))
                ))
            ->add('title', 'text', array(
                'label' => t('Title'),
                'required' => FALSE,
                'max_length' => 255,
            ))
            ->add('description', 'text', array(
                'label' => t('Description'),
                'required' => FALSE,
                'max_length' => 255,
            ))
            ->add('fid', 'hidden', array(
            ))
            ->add('aid', 'hidden', array(
            ))
            ->add('uri', 'hidden', array(
            ));
    }
    public function getName() {
        return 'imageForm';
    }
}
