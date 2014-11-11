<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Url;

class ImageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('image', 'file', array(
                'label' => t('Imagen'),
                'required' => FALSE,
                'constraints' => array(
                    new Image(
                        array(
                            'mimeTypes' => unserialize(ALLOWED_IMAGE_FORMATS),
                            'maxSize' => MAX_IMAGE_FILE_SIZE,
                            'mimeTypesMessage' => t('The file type is invalid ({{ type }}). Allowed file types are {{ types }}'),
                            'maxSizeMessage' => t('The file is too large ({{ size }} MB). Allowed maximum size is {{ limit }} MB'),
                        )),
                ),
            ))
            ->add('link', 'text', array(
                'label' => t('URL'),
                'required' => FALSE,
                'max_length' => 255,
                'constraints' => array(
                new Url(
                    array(
                        'message' => t('This value is not a valid URL')
                    ))
                )
            ))
            ->add('title', 'text', array(
                'label' => t('Title'),
                'required' => FALSE,
                'max_length' => 255,
            ))
            ->add('alt', 'text', array(
                'label' => t('Alternative title'),
                'required' => FALSE,
                'max_length' => 255,
            ))
            ->add('description', 'text', array(
                'label' => t('Description'),
                'required' => FALSE,
                'max_length' => 255,
            ))
            ->add('iid', 'hidden', array(
            ))
            ->add('uri', 'hidden', array(
            ));
    }
    public function getName() {
        return 'imageForm';
    }
}
