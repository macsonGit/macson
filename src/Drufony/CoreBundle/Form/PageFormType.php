<?php

namespace Drufony\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Drufony\CoreBundle\Model\Content;
use Drufony\CoreBundle\Model\ContentUtils;
use Drufony\CoreBundle\Model\Category;
use Drufony\CoreBundle\Entity\Comment;

class PageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $requiredFields = unserialize(FIELDS_REQUIRED_PAGE);
        $builder
            ->setMethod('POST')
            ->add('title', 'text', array(
                'label'      => t('Title'),
                'max_length' => 255,
                'required'   => (array_search('title', $requiredFields) !== FALSE),
            ))
            ->add('url', 'text', array(
                'label'       => t('Url friendly'),
                'max_length'  => 255,
                'required'    => (array_search('url', $requiredFields) !== FALSE),
                'constraints' => array(
                    new Regex(
                            array(
                                'pattern' => "/^\/?([a-z0-9\-]*\/?)+$/",
                                'message' => t('This value can\'t have spaces or special characters, words must be separated by "-"'),
                            )
                    )),
            ))
            ->add('description', 'textarea', array(
                'label' => t('Description'),
                'required'   => (array_search('description', $requiredFields) !== FALSE),
            ))
            ->add('teaser', 'textarea', array(
                'label' => t('Teaser'),
                'required'   => (array_search('teaser', $requiredFields) !== FALSE),
            ))
            ->add('body', 'textarea', array(
                'label' => t('Body'),
                'required'   => (array_search('teaser', $requiredFields) !== FALSE),
            ))
            //Second column
            //Images
            ->add('images', 'collection', array(
                'type' => new ImageFormType(),
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'label' => t('Images'),
                'prototype_name' => '__image__',
                'attr' => array('data-prototype-name' => '__image__'),
                'options' => array(
                    'required' => FALSE,
                    'label' => ' ',
                ),
            ))
            ->add('addImg', 'button', array('label' => t('+ Add image'), 'attr' => array('class' => 'btn add-img-btn', 'data-target' => 'pageForm_images')))

            //Attachments
            ->add('attachments', 'collection', array(
                'type' => new AttachmentFormType(),
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'label' => t('Attachments'),
                'prototype_name' => '__attachment__',
                'attr' => array('data-prototype-name' => '__attachment__'),
                'options' => array(
                    'required' => FALSE,
                    'label' => ' ',
                ),
            ))
            ->add('addAttach', 'button', array('label' => t('+ Add attachment'), 'attr' => array('class' => 'btn add-attach-btn', 'data-target' => 'pageForm_attachments')))

            //Videos
            ->add('videos', 'collection', array(
                'type' => new VideoFormType(),
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'label' => t('Videos'),
                'prototype_name' => '__video__',
                'attr' => array('data-prototype-name' => '__video__'),
                'options' => array(
                    'required' => FALSE,
                    'label' => ' ',
                ),
            ))
            ->add('addVideo', 'button', array('label' => t('+ Add Video'), 'attr' => array('class' => 'btn add-attach-btn', 'data-target' => 'pageForm_videos')))

            //Links
            ->add('links', 'collection', array(
                'type' => new LinkFormType(),
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'prototype_name' => '__link__',
                'attr' => array('data-prototype-name' => '__link__'),
                'label' => t('Links'),
                'options' => array(
                    'label' => ' ',
                ),
            ))
            ->add('addLink', 'button', array('label' => t('+ Add link'), 'attr' => array('class' => 'btn add-link-btn', 'data-target' => 'pageForm_links')))

            //Options
            //Third column
            ->add('published', 'checkbox', array(
                'label' => t('Published'),
                'required'   => (array_search('published', $requiredFields) !== FALSE),
            ))
            ->add('futurePublicationDate', 'datetime', array(
                'label' => t('Future publication Date'),
                'time_widget' => 'single_text',
                'required' => FALSE,
                'date_widget' => 'single_text',
                'constraints' => array(
                    new GreaterThan(
                        array(
                            'value' => new \DateTime(),
                            'message' => t('This value should be greater than {{ compared_value }}'),
                        )
                ))
            ))
            ->add('promoted', 'checkbox', array(
                'label' => t('Promoted'),
                'required'   => (array_search('promoted', $requiredFields) !== FALSE),
            ))
            ->add('sticky', 'checkbox', array(
                'label' => t('Sticky'),
                'required'   => (array_search('sticky', $requiredFields) !== FALSE),
            ))
            ->add('xmlMap', 'checkbox', array(
                'label' => t('List on Sitemap for Google'),
                'required' => FALSE,
            ))
            ->add('userMap', 'checkbox', array(
                'label' => t('List on SiteMap for Users'),
                'required'   => (array_search('userMap', $requiredFields) !== FALSE),
            ));

            if (!in_array('tags', unserialize(FIELDS_PAGE_HIDE))) {
                $index = 1;
                $nodeTags = $options['data']->getTags();
                $vocabularies = Category::getVocabularies();
                foreach ($vocabularies as $vocabulary) {
                    list($parents, $children) = Category::getCategoryHierarchyByVocabulary($vocabulary['vid']);
                    $categories = Category::getFormatedCategory($parents, $children);
                    if (count($categories) > 0 && $vocabulary['name'] != FREE_TAGS) {
                        $builder->add("Tags" . $index, 'choice', array(
                            'label' => $vocabulary['name'],
                            'multiple' => true,
                            'expanded' => false,
                            'mapped' => false,
                            'required' => false,
                            'choices' => $categories,
                            'data' => $nodeTags,
                        ));
                        $index++;
                    }
                }
            }

            $builder->add('freeTags', 'text', array(
                'label'     => t('Free tags'),
                'required' => false,
                'constraints' => array(
                    new Regex(
                            array(
                                'pattern' => "/^([a-z0-9\-]*,?)+$/",
                                'message' => t('Words must be separated by commas and no spaces'),
                            )
                    )),
            ))
            ->add('weight', 'choice', array(
                'label' => t('Importance'),
                'choices' => array_combine(range(-127, 127),range(-127, 127)),
                'empty_data' => 0,
                'required'   => (array_search('weight', $requiredFields) !== FALSE),
            ))
            ->add('commentStatus', 'choice', array(
                'label' => t('Comment status'),
                'choices' => Comment::getAllowedCommentStatus(),
                'empty_data'    => DEFAULT_COMMENT_STATUS,
            ));
            if (!$options['data']->isNull()) {
                $builder->add('majorChange', 'checkbox', array(
                    'label' => t('Major change'),
                    'data' => TRUE,
                    'required' => FALSE,
                ));
            }
            else {
                $builder->add('majorChange', 'hidden', array(
                    'data' => TRUE,
                ));
            }

            //Adds location fields if content is geo positionable
            ContentUtils::addLocationFormFields(Content::TYPE_PAGE, $builder);

            $builder->add('nid', 'hidden', array())
            ->add('save', 'submit', array(
                'label' => t('Save'),
            ));
            if ($options['data']->isNull()) {
                $builder->add('preview', 'submit', array(
                    'label' => t('Preview'),
                    'attr' => array(
                        'class' => 'preview',
                    ),
                ));
            }

        foreach (unserialize(FIELDS_PAGE_HIDE) as $toHide) {
            $builder->remove($toHide);
        }

    }
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Drufony\CoreBundle\Model\Page',
        ));
    }
    public function getName() {
        return 'pageForm';
    }
}
