<?php

namespace Custom\ProjectBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drufony\CoreBundle\Model\Item;
use Drufony\CoreBundle\Entity\Comment;
use Drufony\CoreBundle\Controller\DrufonyController;
use Drufony\CoreBundle\Form\CommentFormType;

class ItemController extends DrufonyController
{
    public function indexAction($oid = null, $template = null, $lang) {
        $response = new Response();
        $item = new Item($oid, $lang);

        $comments = $item->getComments();
        if ($item->getCommentStatus() != Comment::COMMENT_STATUS_CLOSED) {
            $commentsForm = array();
            foreach ($comments as $comment) {
                $commentsForm[$comment->getCid()] =  $this->createForm(new CommentFormType(), array(
                    'node' => $item,
                    'destination' => $this->getRequest()->getUri(),
                    'pid' => $comment->getCid(),
                ))->createView();
            }
            $commentsForm['new'] = $this->createForm(new CommentFormType(), array(
                'node' => $item,
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
            'contentData' => $item,
            'widget' => $widgets,
            'comments' => $comments,
            'commentsCount' => $item->getCommentsCount(),
            'commentsForm' => isset($commentsForm) ? $commentsForm : null,
        )));

        return $response;
    }
}
