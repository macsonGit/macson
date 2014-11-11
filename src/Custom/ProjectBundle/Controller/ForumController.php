<?php

namespace Custom\ProjectBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drufony\CoreBundle\Model\Section;
use Drufony\CoreBundle\Model\ContentUtils;
use Drufony\CoreBundle\Model\UserUtils;
use Drufony\CoreBundle\Controller\DrufonyController;
use Drufony\CoreBundle\Form\CommentFormType;
use Drufony\CoreBundle\Entity\Comment;

class ForumController extends DrufonyController
{
    public function indexAction($oid = null, $template = null, $lang) {
        $response = new Response();
        $section  = new Section($oid, $lang);

        $children = $section->getChildren();

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
            ),
            'rate' => array(
                'rateValue' => UserUtils::getContentRateByUid($section->getNid(), !is_null($this->getUser()) ? $this->getUser()->getUid() : NULL),
                'numRates' => count(UserUtils::getContentRates($section->getNid())),
            ),
            'favorite' => array(
                'isFavorited' => UserUtils::isFavorited(!is_null($this->getUser()) ? $this->getUser()->getUid() : NULL, $section->getNid()),
            ),
            'print' => TRUE,
            'reportAbuse' => TRUE,
        );

        $response->setContent($this->renderView("CustomProjectBundle::${template}.html.twig", array(
            'lang' => $lang,
            'contentData' => $section,
            'topics' => $children,
            'widgets' => $widgets,
            'comments' => $comments,
            'commentsCount' => $section->getCommentsCount(),
            'commentsForm' => isset($commentsForm) ? $commentsForm : null,
        )));

        return $response;
    }
}
