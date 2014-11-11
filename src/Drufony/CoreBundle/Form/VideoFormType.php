<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Drufony\CoreBundle\Model\Video;

class VideoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('video', 'file', array(
                'label' => t('Video'),
                'required' => FALSE,
                'constraints' => array(
                    new File(
                        array(
                            'mimeTypes' => Video::$allowedVideoMimeTypes,
                                'maxSize' => Video::MAX_VIDEO_FILE_SIZE,
                                'mimeTypesMessage' => t('The file type is invalid ({{ type }}). Allowed file types are {{ types }}'),
                                'maxSizeMessage' => t('The file is too large ({{ size }} MB). Allowed maximum size is {{ limit }} MB'),
                            )),
                ),
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
            ->add('token', 'hidden', array(
            ));
    }
    public function getName() {
        return 'videoForm';
    }
}
