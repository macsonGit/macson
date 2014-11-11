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

class SectionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        //Place this in a function?
        $sql = "SELECT n.nid, s.title FROM section s INNER JOIN node n ON n.nid = s.nid WHERE n.status = 1";
        $results = db_executeQuery($sql);
        $sections = array();
        while ($row = $results->fetch()) {
            $sections[$row['nid']] = $row['title'];
        }
        $requiredFields = unserialize(FIELDS_REQUIRED_SECTION);

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
            // - Hierarchy
            ->add('parents', 'collection', array(
                'type' => 'choice',
                'label' => t('Hierarchy'),
                'allow_add' => true,
                'allow_delete' => true,
                'prototype_name' => '__parent__',
                'attr' => array('data-prototype-name' => '__parent__'),
                'error_bubbling' => false,
                'options' => array(
                    'choices' => $sections,
                    'label' => t('Parent'),
                    'required' => FALSE,
                ),
            ))
            ->add('addHierarchy', 'button', array(
                'label' => t('Add another parent'),
                'attr' => array('class' => 'btn add-btn', 'data-target' => 'sectionForm_parents')
            ))

            ->add('description', 'textarea', array(
                'label' => t('Description'),
                'required'   => (array_search('description', $requiredFields) !== FALSE),
            ))
            ->add('teaser', 'textarea', array(
                'label' => t('Teaser'),
                'required'   => (array_search('teaser', $requiredFields) !== FALSE),
            ))
            ->add('summary', 'textarea', array(
                'label' => t('Summary'),
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
            ->add('addImg', 'button', array('label' => t('+ Add image'), 'attr' => array('class' => 'btn add-img-btn', 'data-target' => 'sectionForm_images')))

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
            ->add('addAttach', 'button', array('label' => t('+ Add attachment'), 'attr' => array('class' => 'btn add-attach-btn', 'data-target' => 'sectionForm_attachments')))

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
            ->add('addVideo', 'button', array('label' => t('+ Add Video'), 'attr' => array('class' => 'btn add-attach-btn', 'data-target' => 'sectionForm_videos')))

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
            ->add('addLink', 'button', array('label' => t('+ Add link'), 'attr' => array('class' => 'btn add-link-btn', 'data-target' => 'sectionForm_links')))

            //Options
            //Third column
            ->add('published', 'checkbox', array(
                'label' => t('Published'),
                'required'   => (array_search('published', $requiredFields) !== FALSE),
            ))
            ->add('futurePublicationDate', 'datetime', array(
                'label' => t('Future publication Date'),
                'time_widget' => 'single_text',
                'required'   => (array_search('futurePublicationDate', $requiredFields) !== FALSE),
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
                'required'   => (array_search('xmlMap', $requiredFields) !== FALSE),
            ))
            ->add('userMap', 'checkbox', array(
                'label' => t('List on SiteMap for Users'),
                'required'   => (array_search('userMap', $requiredFields) !== FALSE),
            ));

            if (!in_array('tags', unserialize(FIELDS_SECTION_HIDE))) {
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
            ->add('showItems', 'checkbox', array(
                'label' => t('Show items'),
                'required'   => (array_search('showItems', $requiredFields) !== FALSE),
            ))
            ->add('showSubsections', 'checkbox', array(
                'label' => t('Show subsections'),
                'required'   => (array_search('showSubsections', $requiredFields) !== FALSE),
            ))
            ->add('showSubsectionItems', 'checkbox', array(
                'label' => t('Show subsection items'),
                'required'   => (array_search('showSubsectionItems', $requiredFields) !== FALSE),
            ))
            ->add('subsectionGroups', 'checkbox', array(
                'label' => t('Group subsections'),
                'required'   => (array_search('subsectionGroups', $requiredFields) !== FALSE),
            ))
            ->add('weight', 'choice', array(
                'label' => t('Weight'),
                'choices' => array_combine(range(-127, 127),range(-127, 127)),
                'empty_data' => 0,
                'required'   => (array_search('weight', $requiredFields) !== FALSE),
            ))
            ->add('maxPerPage', 'text', array(
                'label' => t('Items per page'),
                'max_length' => 4,
                'empty_data' => 0,
                'required'   => (array_search('maxPerPage', $requiredFields) !== FALSE),
                'constraints' => array(
                    new Regex(array(
                        'pattern' => '/\d+/',
                        'message' => t('Only numbers must be inserted'),
                    ))
                ),

            ))
            ->add('orderCriteria', 'choice', array(
                'label' => t('Order criteria'),
                'choices' => array('DATE' => t('By Date'), 'BLOG' => t('As a blog'), 'WEIGHT' => t('By weight')),
                'required'   => (array_search('orderCriteria', $requiredFields) !== FALSE),
                'empty_value' => 'DATE',
            ))
            ->add('orderMode', 'choice', array(
                'label' => t('Order mode'),
                'choices' => array('ASC' => t('Ascending'), 'DESC' => t('Descending')),
                'required'   => (array_search('orderMode', $requiredFields) !== FALSE),
                'empty_value' => 'ASC',
            ))
            ->add('addContentEnable', 'checkbox', array(
                'label' => t('A user will be able to create child content.'),
                'required'   => (array_search('addContentEnable', $requiredFields) !== FALSE),
            ))
            ->add('feedEnabled', 'checkbox', array(
                'label' => t('Enable feeds'),
                'required'   => (array_search('feedEnabled', $requiredFields) !== FALSE),
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
            ContentUtils::addLocationFormFields(Content::TYPE_SECTION, $builder);

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

        foreach (unserialize(FIELDS_SECTION_HIDE) as $toHide) {
            $builder->remove($toHide);
        }

    }
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Drufony\CoreBundle\Model\Section',
        ));
    }
    public function getName() {
        return 'sectionForm';
    }
}
