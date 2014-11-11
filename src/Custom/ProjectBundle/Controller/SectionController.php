<?php

namespace Custom\ProjectBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drufony\CoreBundle\Model\Section;
use Drufony\CoreBundle\Model\ContentUtils;
use Drufony\CoreBundle\Controller\DrufonyController;
use Drufony\CoreBundle\Form\CommentFormType;
use Drufony\CoreBundle\Entity\Comment;

class SectionController extends DrufonyController
{
    public function indexAction($oid = null, $template = null, $lang) {
        $response = new Response();
        $section  = new Section($oid, $lang);

        $promoted = ContentUtils::getAllPublished($lang);

        $comments = $section->getComments();
        if ($section->getCommentStatus() != Comment::COMMENT_STATUS_CLOSED) {
            $commentsForm = array();
            foreach ($comments as $comment) {
                $commentsForm[$comment->getCid()] =  $this->createForm(new CommentFormType(), array(
                    'node' => $section,
                    'destination' => $this->getRequest()->getUri(),
                    'pid' => $comment->getCid(),
                ))->createView();
            }
            $commentsForm['new'] = $this->createForm(new CommentFormType(), array(
                'node' => $section,
                'destination' => $this->getRequest()->getUri()
            ))->createView();
        }

        $widgets = array(
            'social' => array(
                'facebookShare' => TRUE,
                'twitterShare'  => TRUE,
                'googleShare'   => TRUE,
                'facebookLike'  => TRUE,
                'googleLike'    => TRUE,
            )
        );

        $response->setContent($this->renderView("CustomProjectBundle::${template}.html.twig", array(
            'lang' => $lang,
            'contentData' => $section,
            'widgets' => $widgets,
            'contents' => $promoted,
            'comments' => $comments,
            'commentsCount' => $section->getCommentsCount(),
            'commentsForm' => isset($commentsForm) ? $commentsForm : null,
        )));

        return $response;
    }
}
