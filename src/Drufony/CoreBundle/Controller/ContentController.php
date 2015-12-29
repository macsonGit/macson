<?php

namespace Drufony\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Form\FormError;
use Drufony\CoreBundle\Form\ItemFormType;
use Drufony\CoreBundle\Form\SectionFormType;
use Drufony\CoreBundle\Form\PageFormType;
use Drufony\CoreBundle\Form\ProductFormType;
use Drufony\CoreBundle\Form\CommentFormType;
use Drufony\CoreBundle\Form\AddTaskFormType;
use Drufony\CoreBundle\Form\CsvUploaderFormType;
use Drufony\CoreBundle\Form\VocabularyFormType;
use Drufony\CoreBundle\Form\CategoryFormType;
use Drufony\CoreBundle\Model\Item;
use Drufony\CoreBundle\Model\Geo;
use Drufony\CoreBundle\Model\Locale;
use Drufony\CoreBundle\Model\Section;
use Drufony\CoreBundle\Model\Utils;
use Drufony\CoreBundle\Model\Page;
use Drufony\CoreBundle\Model\Product;
use Drufony\CoreBundle\Model\ContentUtils;
use Drufony\CoreBundle\Model\UserUtils;
use Drufony\CoreBundle\Model\Content;
use Drufony\CoreBundle\Model\Task;
use Drufony\CoreBundle\Model\Mailing;
use Drufony\CoreBundle\Entity\Comment;
use Drufony\CoreBundle\Exception\ContentTypeNotFound;
use Drufony\CoreBundle\Exception\TranslateNotFound;
use Drufony\CoreBundle\Model\CommerceUtils;
use Drufony\CoreBundle\Model\Category;
use Drufony\CoreBundle\Model\NodePool;

class ContentController extends DrufonyController {
    const USER_ANONYMOUS = 0;
    const EDIT_ACTION = 'edit';
    const CREATE_ACTION = 'create';
    const PREVIEW_ACTION = 'preview';

    function localeAction($route = '', $parameters = array()) {
        define('DEFAULT_LANG', Geo::getUserLanguage());
        return $this->redirect(http_build_url(DEFAULT_LANG . '/' .  $route));
    }

    function contentAction(Request $request, $lang, $contentType, $action, $id = null, $langToTranslate = null) {
        $nodeContentTypes = ContentUtils::getAvailableContentTypes();

        if (in_array($contentType, $nodeContentTypes)) {
            $contentGroup = 'node';
        }
        else {
            $contentGroup = $contentType;
        }

        switch ($action) {
            case self::EDIT_ACTION:
            case self::CREATE_ACTION:
                $controllerName = "${contentGroup}Add";
                break;
            default:
                $actionName = ucfirst(strtolower($action));
                $controllerName = "${contentGroup}${actionName}";
                break;
        }

        return $this->forward("DrufonyCoreBundle:Content:${controllerName}", array(
				'request'     => $request,     'lang'   => $lang,  'id' => $id,
            'contentType' => $contentType, 'action' => $action, 'langToTranslate' => $langToTranslate,
        ));
    }

    private function _getFormType($contentType) {
        return call_user_func('self::_get' . ucfirst(strtolower($contentType)) . 'FormType');
    }

    static private function _getItemFormType() {
        return new ItemFormType();
    }

    static private function _getSectionFormType() {
        return new SectionFormType();
    }

    static private function _getPageFormType() {
        return new PageFormType();
    }

    static private function _getProductFormType() {
        return new ProductFormType();
    }

    private function _processNodeForm($request, $node, $contentType, $lang, $langToTranslate) {
        $action = false;
        $params = array();
        $nodeForm   = $this->createForm($this->_getFormType($contentType), $node);

        if ($request->getMethod() == 'POST') {
            $nodeForm->handleRequest($request);
            if ($nodeForm->isValid()) {

                //Get node tags
                $tags = preg_grep("/^Tags(\d)+$/", array_keys($nodeForm->all()));
                $allFields = $nodeForm->all();

                $nodeTags = array();
                foreach($tags as $tag) {
                    $nodeTags = array_merge($nodeTags, $allFields[$tag]->getData());
                }

                $node = $nodeForm->getData();

                if ($nodeForm->getClickedButton()->getName() == 'preview') {
                    $node->__set('published', FALSE);
                }
                $node->__set('lang', $langToTranslate);
                $node = $node->__toArray();

                $node['tags'] = $nodeTags;
                $node['uid'] = !is_null($this->getUser()) ? $this->getUser()->getUid() : self::USER_ANONYMOUS;

                $errorFound = false;
                if (!empty($node['url'])) {
                    $exist = Utils::existUrl($contentType, $node['url'], $node['id']);
                    if ($exist) {
                        $nodeForm->get('url')->addError(new FormError(t('Url already exists')));
                        $errorFound = true;
                    }
                }

                if ($contentType == Content::TYPE_ITEM || $contentType == Content::TYPE_SECTION) {

                    if (count($node['parents']) == 0 || is_null(array_values($node['parents'][0]))) {
                        $nodeForm->get('parents')->addError(new FormError(t('At least one parent must be selected')));
                        $errorFound = true;
                    }
                }

                if (!$errorFound) {
                    $id = Utils::saveData($node);

                    if ($id != null) {
                        if ($nodeForm->getClickedButton()->getName() == 'preview') {
                            $node = ContentUtils::nodeLoad($id, $langToTranslate, $node['contentType']);
                            $params = array('url' => $node->getUrl());
                            $action = self::PREVIEW_ACTION;
                        }
                        else {
                            $action = self::EDIT_ACTION;
                        }
                        $this->get('session')->getFlashBag()->add(
                            INFO,
                            t('Your changes were saved!')
                        );
                    }
                }
                else {
                    $this->get('session')->getFlashBag()->add(
                        ERROR,
                        t('Sorry, but your form was not processed! Please correct the following errors and submit the form again!')
                    );
                }
            }
            else {
                $this->get('session')->getFlashBag()->add(
                    ERROR,
                    t('Sorry, but your form was not processed! Please correct the following errors and submit the form again!')
                );
            }
        }

        return array($nodeForm, $action, $params);
    }

    function nodeAddAction(Request $request, $lang, $contentType, $action, $id = null, $langToTranslate = null) {
        $response        = new Response();
        $langToTranslate = is_null($langToTranslate) ? $lang : $langToTranslate;
        try {
            $node            = ContentUtils::nodeLoad($id, $langToTranslate, $contentType);
        }
        catch(TranslateNotFound $t) {
            $node            = ContentUtils::nodeLoad(NULL, $langToTranslate, $contentType);
            $node->__set('nid', $id);
        }
        $actionName      = ucfirst(strtolower($action));

        list($nodeForm, $editAction, $params) = $this->_processNodeForm($request, $node, $contentType, $lang, $langToTranslate);

        if ($editAction == self::EDIT_ACTION) {

            $feature = 'latest';
            if(!$node->isPublished()) {
                $feature = 'unpublished';
            }

            $response = $this->redirect($this->generateUrl('drufony_manage_content', array('lang'        => $lang,
                                                                                           'feature'     => $feature,
                                                                                           'contentType' => $contentType)));
        }
        elseif ($editAction == self::PREVIEW_ACTION) {
            $response = $this->redirect($this->generateUrl('drufony_general_url', array('lang' => $langToTranslate, 'url' => $params['url'])));
        }
        else {
            /* Adds nodes for section breadcrumb*/
            $breadCrumb = array(
              'dashboard'  => array( 'label' => t('Dashboard'),          'url' => 'drufony_home_dashboard'),
              'create'     => array( 'label' => t('Create'),             'url' => 'drufony_create_path'),
              $contentType => array( 'label' => t("${actionName} ${contentType}"), 'url' => 'drufony_content_actions'),
            );

            $translationOptions = $this->_getTranslationButtons($request, $contentType, $action);


            $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
                array('lang'               => $lang,
                      'id'                 => $id,
                      'langToTranslate'    => $langToTranslate,
                      'translationOptions' => $translationOptions,
                      'left'               => 'DrufonyCoreBundle::left.html.twig',
                      'form'               => $nodeForm->createView(),
                      'itemMenu'           => t($actionName),
                      'dashboard'          => 'DrufonyCoreBundle::content_create_form.html.twig',
                      'contentType'        => t("${actionName} ${contentType}"),
                      'nodeType'           => $contentType,
                      'itemsFormLeft'      => '',
                      'itemsFormRight'     => '',
                      'columnMiddle'       => array("text", 'body'),
                      'columnRight'        => array("_${contentType}Form_addLink", 'addLink'),
                      'breadCrumb'         => $breadCrumb,
                )
            ));
        }

        return $response;
    }

    private function _getTranslationButtons($request, $contentType, $action) {
        $availableLanguages = Locale::getAllLanguages();
        $translationOptions = array();

        foreach($availableLanguages as $oneLang => $langName) {
            $translationOptions[] = array(
                'route'           => $request->get('_route'),
                'contentType'     => $contentType,
                'action'          => $action,
                'langToTranslate' => $oneLang,
                'langName'        => $langName,
            );
        }

        return $translationOptions;
    }

    function taskAddAction(Request $request, $lang, $contentType, $action, $id = null, $langToTranslate = null) {
        $response = new Response();
        $task     = null;

        if(!is_null($id)) {
          $task = Task::load($id);
          if (empty($task)) {
              throw $this->createNotFoundException(t('This task doesn\'t exist'));
          }
        }

        $taskForm = $this->createForm(new AddTaskFormType(), array('task' => $task));
        if ($request->getMethod() == 'POST') {
            $taskForm->handleRequest($request);
            if ($taskForm->isValid()) {
                $data       = $taskForm->getData();
                $data['id'] = $id;
                $id         = Task::save($data);

                if ($id != null) {
                    $this->get('session')->getFlashBag()->add(
                        INFO,
                        t('Your changes were saved!')
                    );

                    return $this->redirect($this->generateUrl('drufony_tasks_list', array('lang' => $lang)));
                }
            }
            else {
                $this->get('session')->getFlashBag()->add(
                        ERROR,
                        t('Sorry, but your form was not processed! Please correct the following errors and submit the form again!')
                );
            }
        }

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'tasks' => array( 'label' => 'Tasks', 'url' => 'drufony_tasks_path'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'=> $lang,
                'langToTranslate' => $langToTranslate,
                'definedLangs' => Locale::getAllLanguages(),
                'id' => $id,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'form' => $taskForm->createView(),
                'itemMenu' => 'Tasks',
                'dashboard' => 'DrufonyCoreBundle::content_create_form.html.twig',
                'contentType' => t('Tasks'),
                'columnRight' => array('text','description'),
                'type' => 'post',
                'breadCrumb' => $breadCrumb,
            )
        ));

        return $response;
    }

    function eventAddAction(Request $request, $lang, $id=NULL) {
        $response = new Response();
        $item = new Item($id, $lang);
        $item->__set('showInCalendar', TRUE);
        $item->__set('type', 'event');
        $itemForm = $this->createForm(new ItemFormType(), $item);
        if ($request->getMethod() == 'POST') {
            $itemForm->handleRequest($request);
            if ($itemForm->isValid()) {
                $item = $itemForm->getData();
                $item = $item->__toArray();
                $item['uid'] = !is_null($this->getUser()) ? $this->getUser()->getUid() : self::USER_ANONYMOUS;
                $id = Utils::saveData($item);
            }
        }

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'create' => array( 'label' => 'Create', 'url' => 'drufony_create_path'),
          'item' => array( 'label' => 'Add Event', 'url' => 'drufony_event_add'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
            array('lang'=> $lang,
                'definedLangs' => Locale::getAllLanguages(),
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'form' => $itemForm->createView(),
                'id' => $id,
                'itemMenu' => 'Create',
                'dashboard' => 'DrufonyCoreBundle::content_create_form.html.twig',
                'contentType' => t('Add Event'),
                'itemsFormLeft' => '',
                'itemsFormRight' => '',
                'columnMiddle'       => array("text", 'body'),
                'columnRight' => array('_itemForm_addLink','addLink'),
                'breadCrumb' => $breadCrumb,
          )
        ));
        return $response;
    }

    function postAddAction(Request $request, $lang, $id=NULL, $langToTranslate=NULL) {
        $response = new Response();
        $langToTranslate = is_null($langToTranslate) ? $lang : $langToTranslate;
        try {
            $item = new Item($id, $langToTranslate);
        } catch(NotFoundHttpException $e) {
            $item = new Item(NULL, $langToTranslate);
            $item->__set('nid', $id);
        }
        $mainSection = ContentUtils::getMainBlogContent();
        if ($item->isNull()) {
            $item->__set('parents', array($mainSection));
        }
        $itemForm = $this->createForm(new ItemFormType(), $item);
        if ($request->getMethod() == 'POST') {
            $itemForm->handleRequest($request);
            if ($itemForm->isValid()) {
                $item = $itemForm->getData();
                if ($itemForm->getClickedButton()->getName() == 'preview') {
                    $item->__set('published', FALSE);
                }
                $item = $item->__toArray();
                $item['uid'] = !is_null($this->getUser()) ? $this->getUser()->getUid() : self::USER_ANONYMOUS;
                $id = Utils::saveData($item);
                if ($itemForm->getClickedButton()->getName() == 'preview') {
                    $item = new Item($id, $langToTranslate);
                    $redirect = $this->generateUrl('drufony_general_url', array('lang' => $langToTranslate, 'url' => $item->getUrl()));
                }
                else {
                    $this->get('session')->getFlashBag()->add(
                        INFO,
                        t('Your changes were saved!')
                    );
                    $redirect = $this->generateUrl('drufony_item_edit', array('lang' => $lang, 'id' => $id, 'langToTranslate' => $langToTranslate));
                }

                return $this->redirect($redirect);
            }
            else {
                $this->get('session')->getFlashBag()->add(
                        ERROR,
                        t('Sorry, but your form was not processed! Please correct the following errors and submit the form again!')
                );
            }
        }

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang' => $lang,
                'langToTranslate' => $langToTranslate,
                'definedLangs' => Locale::getAllLanguages(),
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'form' => $itemForm->createView(),
                'id'   => $id,
                'itemMenu' => 'Create',
                'dashboard' => 'DrufonyCoreBundle::content_create_form.html.twig',
                'contentType' => t('Items'),
                'itemsFormLeft' => '',
                'itemsFormRight' => '',
                'columnRight' => array('_itemForm_addLink','addLink'),
                'type' => 'post',
          )
        ));

        return $response;
    }

    //FIXME create real controllers to nodo menu items
    function createPathAction(Request $request, $lang) {
        $response = new Response();

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang' => $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'Create',
                'dashboard' => 'DrufonyCoreBundle::blank.html.twig',
                'contentType' => t('Here Be Dragons Create'),
          )
        ));

        return $response;
    }

    function managePathAction(Request $request, $lang) {
        $response = new Response();

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang' => $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'Manage',
                'dashboard' => 'DrufonyCoreBundle::blank.html.twig',
                'contentType' => t('Here Be Dragons Manage'),
          )
        ));

        return $response;
    }

    function translationsPathAction(Request $request, $lang) {
        $response = new Response();

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang' => $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'Translations',
                'dashboard' => 'DrufonyCoreBundle::blank.html.twig',
                'contentType' => t('Here Be Dragons Translations'),
          )
        ));

        return $response;
    }

    function commercePathAction(Request $request, $lang) {
        $response = new Response();

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang' => $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'shop',
                'dashboard' => 'DrufonyCoreBundle::blank.html.twig',
                'contentType' => t('Here Be Dragons Commerce'),
          )
        ));

        return $response;
    }

    function nodeDeleteAction(Request $request, $lang, $id) {
        $pathDestination = $request->query->get('destination');
        $pathRedirect    = $pathDestination ? $pathDestination : 'drufony_manage_content';

        try {
            Utils::deleteContent($id);
            $this->get('session')->getFlashBag()->add(
                INFO,
                t('Content deleted successfully.')
            );
        }
        catch (ContentTypeNotFound $e) {
            $this->get('session')->getFlashBag()->add(
                ERROR,
                t('Content could not be deleted.')
            );

        }

        return $this->redirect($pathRedirect);
    }

    function taskDeleteAction(Request $request, $lang, $id) {
        $response = new Response();
        $task = Task::delete($id);

        return $this->redirect($this->generateUrl('drufony_tasks_list', array('lang' => $lang)));
    }

    function commentAddAction (Request $request, $lang) {
        $response = new Response();
        $user = $this->getUser();
        $commentForm = $this->createForm(new CommentFormType());
        $data = array();
        $destination = '/';
        $commentForm->handleRequest($request);
        if ($commentForm->isValid()) {
            $commentData = $commentForm->getData();
            $destination = $commentData['destination'];
            unset($commentData['destination']);
            $node = ContentUtils::nodeLoad($commentData['nid'], $lang);
            if ($node->getCommentStatus() != Comment::COMMENT_STATUS_CLOSED) {
                if ($commentData['cid'] == 0) {
                    unset($commentData['cid']);
                    $commentData['changed'] = time();
                }
                $commentData['uid'] = !is_null($user) ? $user->getUid() : 0;
                $commentData['ip']  = $this->container->get('request')->getClientIp();
                $commentData['created'] = time();
                $commentData['status'] = $node->getCommentStatus() == Comment::COMMENT_STATUS_PREMODERATED ? 0 : 1;
                $commentData['name'] = !is_null($user) ? $user->getUsername() : t('Anonymous');
                $commentData['mail'] = !is_null($user) ? $user->getEmail() : null;
                $node->saveComment($commentData);
                $this->get('session')->getFlashBag()->add(
                    INFO,
                    t('Comment registered successfully')
                );
            }
        }
        return $this->redirect($destination);
    }

    function publishContentAction(Request $request) {
        $response = new Response();
        ContentUtils::publishFutureContent();
        $response->setContent('OK');

        return $response;
    }

    function rateContentAction(Request $request, $nid) {
        $response = new Response();

        $user = $this->getUser();
        $value = $request->get('value');

        if (!is_null($value) && is_numeric($value)) {
            UserUtils::setContentRate($nid, $user->getUid(), $value);
        }

        //Update average and count of votes
        $numRates = count(UserUtils::getContentRates($nid));
        $avgRate = UserUtils::getContentAverageRate($nid);

        $response->setContent(json_encode(array(
            'numRates' => $numRates,
            'avgRate' => $avgRate
        )));
        $response->headers->set('Content-type', 'application/json');

        return $response;
    }

    function manageFavoriteContentAction(Request $request, $action, $nid) {
        $response = new Response();

        $user = $this->getUser();
        if (!is_null($user)) {
            if ($action == 'add') {
                UserUtils::setFavorite($user->getUid(), $nid);
            }
            elseif ($action == 'remove') {
                UserUtils::removeFavorite($user->getUid(), $nid);
            }
            $response->setContent(json_encode(array(
                'status' => 'ok',
            )));
        }
        else {
            $response->setContent(json_encode(array(
                'status' => 'error',
            )));
        }

        $response->headers->set('Content-type', 'application/json');

        return $response;
    }

    function importExportProductsAction(Request $request, $lang, $action) {
        return $this->forward('DrufonyCoreBundle:Content:' . $action . 'Products', array(
            'request' => $request,
            'lang' => $lang,
        ));
    }

    function importProductsAction(Request $request, $lang) {
        $response  = new Response();
        $user      = $this->getUser();
        $uid       = !is_null($user) ? $user->getUid() : self::USER_ANONYMOUS;
        $numToSkip = 2;  //Number of rows to skip, usually to avoid process headers rows
        unset($user);
        $productImporterForm = $this->createForm(new CsvUploaderFormType());
        if ($request->getMethod() == 'POST') {
            $productImporterForm->handleRequest($request);
            if ($productImporterForm->isValid()) {
                $productData = $productImporterForm->getData();
                $csv = $productData['csv']->move(FILES_BASE . SUBPATH_CSV, uniqid() . '.csv');

                //Open file to count number of lines
                $csv = $csv->openFile('r');
                $csv->setCsvControl(';', '"');
                $csv->setFlags(\SplFileObject::READ_CSV);
                $numberLines = 0;
                foreach ($csv as $csvItem) {
                    $numberLines++;
                }

                return $this->forward('DrufonyCoreBundle:Admin:batch', array(
                    'file' => $csv->getPathname(),
                    'offset' => 0,
                    'page' => BATCH_BLOCK_SIZE,
                    'numElements' => $numberLines - $numToSkip,
                    'request' => $request,
                    'lang' => $lang,
                ));
            }
        }
        if ($request->query->get('batchProcessing')) {
            //Mapped fields, change columns numbers depending on csv columns
            //Real csv columns change keep this to import from real csv
            $csvColumnsReal = array(
                0  => 'sku',
                1  => 'title',
                3  => 'brand',
                4  => 'image',
                6  => 'priceSubtotalNoVat',
                11 => 'family1',
                13 => 'family2',
                15 => 'family3',
                27 => 'priceVatPercentage',
                30 => 'body',
            );
            $csvColumns = $csvColumnsReal;

            $fileName    = $request->query->get('file');
            if (file_exists($fileName)) {
                $rowsProcessed = 0;
                $count  = 0;
                $offset = (is_numeric($request->query->get('offset')) ? $request->query->get('offset') : 0) + $numToSkip;
                $page   = is_numeric($request->query->get('page'))    ? $request->query->get('page')   : 0;

                $file = new \SplFileObject($fileName);
                $file->setCsvControl(';', '"');
                $file->setFlags(\SplFileObject::READ_CSV);
                foreach($file as $row) {
                    if ($count >= $offset && ($count < $offset + $page)) {
                        if (count($row) > 1) {
                            //Filter columns and change keys name
                            $row = array_intersect_key($row, $csvColumns);
                            $row = array_combine(array_values($csvColumns), array_values($row));

                            //In first load all products will be inserted
                            $row['contentType'] = Content::TYPE_PRODUCT;
                            $row['uid']         = $uid;
                            $row['published']   = TRUE;

                            //Categories save
                            $tid1 = Category::save($row['family2'], $row['family1']);
                            $tid2 = Category::save($row['family3'], $row['family1'], $tid1);
                            unset($row['family1']);
                            unset($row['family2']);
                            unset($row['family3']);
                            $image = trim($row['image']);
                            unset($row['image']);

                            //Customizations to data
                            $row['priceVatPercentage'] = floatval(str_replace(',', '.', $row['priceVatPercentage']));
                            $row['priceSubtotalNoVat'] = (1 - ($row['priceVatPercentage']/100)) * floatval(str_replace(',', '.', $row['priceSubtotalNoVat']));
                            $row['nid'] = $id = Utils::saveData($row);
                            Category::setNodeCategory($id, $tid1);
                            Category::setNodeCategory($id, $tid2);

                            //Create bones for images
                            //FIXME delete this when first content load is done
                            if (!empty($image)) {
                                $pathUrl = FILES_BASE . SUBPATH_IMAGES . Utils::getNodeFilesPath($id);
                                if (!file_exists($pathUrl . "/")) {
                                    mkdir($pathUrl, 0755, TRUE);
                                }
                                $sql = "SELECT fid FROM file_managed WHERE uri = ?";
                                $fid = db_fetchColumn($sql, array($pathUrl . $image . '.jpg'));
                                if (!$fid) {
                                    $fileManaged = array(
                                        'uid'       => $uid,
                                        'filename'  => $image . '.jpg',
                                        'status'    => 1,
                                        'uri'       => $pathUrl . $image . '.jpg',
                                        'filemime'  => 'image/jpg',
                                        'filesize'  => 0,
                                        'timestamp' => time(),
                                    );
                                    $sql = "SELECT id FROM product WHERE nid = ? AND lang = ?";
                                    $row['id'] = db_fetchColumn($sql, array($id, $lang));

                                    if (($fid = db_insert('file_managed', $fileManaged)) === FALSE) {
                                        l(ERROR, 'Error inserting file information in table file_managed');
                                    }
                                    Utils::imagesFieldSave(array(array('fid')), $row);
                                }
                            }
                            //End FIXME

                        }
                        $rowsProcessed++;
                    }
                    $count++;
                    unset($row);
                }
                l('INFO', 'Import batch completed');

                //Prepare response in json format to infor to batch screen
                $response->setContent(json_encode(array('status' => 'OK', 'elementsProcessed' => $rowsProcessed)));
                $response->headers->set('Content-type', 'application/json');

                $this->get('session')->getFlashBag()->clear();
                $this->get('session')->getFlashBag()->add(
                    INFO,
                    t('Products imported successfully')
                );
            }
        }
        else {
            /* Adds nodes for section breadcrumb*/
            $breadCrumb = array(
              'dashboard'  => array( 'label' => t('Dashboard'),          'url' => 'drufony_home_dashboard'),
              'productImporter' => array( 'label' => t("Product Importer"), 'url' => 'drufony_content_actions'),
            );
            $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
                array('lang'               => $lang,
                      'left'               => 'DrufonyCoreBundle::left.html.twig',
                      'form'               => $productImporterForm->createView(),
                      'itemMenu'           => t('Product importer'),
                      'dashboard'          => 'DrufonyCoreBundle::content_create_form.html.twig',
                      'itemsFormLeft'      => '',
                      'itemsFormRight'     => '',
                      'contentType'        => t('Product importer'),
                      'columnRight'        => '',
                      'id'                 => 0,
                      'breadCrumb'         => $breadCrumb,
                )
            ));
        }

        return $response;
    }

    function exportProductsAction(Request $request, $lang) {
        $response = new Response();
        $fieldsCSV = array(
            0 => 'nid',
            1 => 'lang',
            2 => 'title',
            3 => 'brand',
            3 => 'description',
            4 => 'teaser',
            5 => 'summary',
            6 => 'body',
            7 => 'sgu',
            8 => 'sku',
            9 => 'priceSubtotalNoVat',
            10 => 'priceVatPercentage',
            11 => 'cat1',
            12 => 'cat2',
            13 => 'cat3',
        );
        $itemsPerPage = 100;
        $page = 1;
        $handle = tmpfile();
        $fieldsCSVFlipped = array_flip($fieldsCSV);
        $allFields = array_fill_keys(array_keys($fieldsCSVFlipped), '');
        //Get all categories and vocabulary once to reduce memory
        $categories = Category::getAllNodeCategories();
        $vocabularies = Category::getAllNodeVocabulary();
        fputcsv($handle, $fieldsCSV, ';');
        while ($products = ContentUtils::getPublished(Content::TYPE_PRODUCT, null, $page)) {
            foreach($products as $product) {
                $product = $product->__toArray();
                $product = array_intersect_key($product, $fieldsCSVFlipped);
                $product = array_merge($allFields, $product);
                //Set vocabulary and categories
                $product['cat1'] = isset($vocabularies[$product['nid']][0]) ? $vocabularies[$product['nid']][0] : '';
                $product['cat2'] = isset($categories[$product['nid']][0])   ? $categories[$product['nid']][0]   : '';
                $product['cat3'] = isset($categories[$product['nid']][1])   ? $categories[$product['nid']][1]   : '';
                fputcsv($handle, array_values($product), ';');
                unset($product);
            }
            $page++;
            unset($product);
            unset($products);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        l('INFO', 'Export product process completed');

        $response->setContent($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="products.csv"');

        return $response;
    }

    public function commentDeleteAction(Request $request, $lang, $id) {
        $comment  = new Comment($id);

        $comment->remove();

        $this->get('session')->getFlashBag()->add(
            INFO,
            t('Comment deleted successfully')
        );

        $pathDestination = $request->query->get('destination');

        return $this->redirect($pathDestination);
    }

    public function commentApproveAction(Request $request, $lang, $id) {
        $comment  = new Comment($id);

        $comment->approve();

        $this->get('session')->getFlashBag()->add(
            INFO,
            t('Comment approved successfully')
        );

        $pathDestination = $request->query->get('destination');

        return $this->redirect($pathDestination);
    }

    public function vocabularyAddAction(Request $request, $lang, $contentType, $action, $id = null, $langToTranslate = null) {
        $response = new Response();
        $vocabularyData = null;

        $categories = array();
        if (!is_null($id)) {
          $vocabularyName = Category::getVocabularyName($id);
          if (empty($vocabularyName)) {
              throw $this->createNotFoundException(t('This taxonomy doesn\'t exist'));
          }
          $vocabularyData = array('vid' => $id, 'name' => $vocabularyName);
          //$children = Category::getChildrenByVocabulary($vocabularyName);
          list($parents, $children) = Category::getCategoryHierarchyByVocabulary($id);
          $categories = Category::getFormatedCategory($parents, $children);
        }

        $vocabularyForm = $this->createForm(new VocabularyFormType(), array('vocabulary' => $vocabularyData));
        if ($request->getMethod() == 'POST') {
            $vocabularyForm->handleRequest($request);

            if ($vocabularyForm->isValid()) {
                $data = $vocabularyForm->getData();
                $id   = Category::createVocabulary($data['name'], $id);

                if ($id != null) {
                    $this->get('session')->getFlashBag()->add(
                        INFO,
                        t('Your changes were saved!')
                    );

                    return $this->redirect($this->generateUrl('drufony_manage_content', array('lang' => $lang, 'feature' => 'taxonomy')));
                }
            }
            else {
                $this->get('session')->getFlashBag()->add(
                        ERROR,
                        t('Sorry, but your form was not processed! Please correct the following errors and submit the form again!')
                );
            }
        }

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'taxonomy' => array( 'label' => 'Taxonomy', 'url' => 'drufony_taxonomy_list'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'=> $lang,
                'langToTranslate' => $langToTranslate,
                'definedLangs' => Locale::getAllLanguages(),
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'form' => $vocabularyForm->createView(),
                'itemMenu' => 'Manage',
                'dashboard' => 'DrufonyCoreBundle::content_create_form.html.twig',
                'categories' => $categories,
                'contentType' => t('Taxonomy'),
                'columnRight' => array('text','description'),
                'vid' => $id,
                'type' => 'post',
                'breadCrumb' => $breadCrumb,
            )
        ));

        return $response;
    }

    public function vocabularyDeleteAction(Request $request, $lang, $contentType, $action, $id = null, $langToTranslate = null) {
        $response = new Response();

        try {
            $vocabulary = Category::getVocabularyName($id);
            Category::removeAllByVocabulary($vocabulary);
            $this->get('session')->getFlashBag()->add(
                INFO,
                t('Taxonomy deleted successfully.')
            );
        }
        catch (Exception $e) {
            $this->get('session')->getFlashBag()->add(
                ERROR,
                t('Taxonomy could not be deleted.')
            );

        }

        $response = $this->redirect($this->generateUrl('drufony_manage_content', array('lang'    => $lang,
                                                                                       'feature' => 'taxonomy')));


        return $response;
    }

    public function categoryAddAction(Request $request, $lang, $vid, $tid = null, $parentId = null) {
        $response = new Response();

        $category = array();
        //Add child action
        if(!is_null($parentId)) {
            $category = array('parentId' => $parentId);
        }
        //Edit action
        else if(!is_null($tid)) {
            $category = Category::getCategoryData($tid);
        }

        $errorMessage = t('Sorry, but your form was not processed! Please correct the following errors and submit the form again!');
        $categoryForm = $this->createForm(new CategoryFormType(), array('vid' => $vid, 'category' => $category));
        if ($request->getMethod() == 'POST') {
            $categoryForm->handleRequest($request);

            if ($categoryForm->isValid()) {
                $data = $categoryForm->getData();
                $vocablaryName = Category::getVocabularyName($vid);

                $parentId = 0;
                if ($data['parentId']) {
                    $parentId = $data['parentId'];
                }

                $tid = null;
                if ($data['tid']) {
                    $tid = $data['tid'];
                }

                $errorFound = false;

                if (!is_null($tid) && $tid == $parentId) {
                    $categoryForm->get('parentId')->addError(new FormError(t('Category can\'t be parent of itself')));
                    $errorFound = true;
                }

                if (!$errorFound) {
                    $id = Category::save($data['name'], $vocablaryName, $parentId, $tid);

                    if ($id != null) {
                        $this->get('session')->getFlashBag()->add(
                            INFO,
                            t('Your changes were saved!')
                        );

                        return $this->redirect($this->generateUrl('drufony_content_actions', array('lang'        => $lang,
                            'action'      => 'edit',
                            'id'          => $vid,
                            'contentType' => 'vocabulary')));
                    }
                }
                else {
                    $this->get('session')->getFlashBag()->add(ERROR, $errorMessage);
                }
            }
            else {
                $this->get('session')->getFlashBag()->add(ERROR, $errorMessage);
            }
        }

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'taxonomy' => array( 'label' => 'Taxonomy', 'url' => 'drufony_taxonomy_list'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'            => $lang,
                'langToTranslate' => '',
                'definedLangs'    => Locale::getAllLanguages(),
                'left'            => 'DrufonyCoreBundle::left.html.twig',
                'form'            => $categoryForm->createView(),
                'itemMenu'        => 'Manage',
                'dashboard'       => 'DrufonyCoreBundle::content_create_form.html.twig',
                'contentType'     => t('Category'),
                'columnRight'     => array('text','description'),
                'type'            => 'post',
                'breadCrumb'      => $breadCrumb,
            )
        ));

        return $response;
    }

    public function categoryDeleteAction(Request $request, $lang, $contentType, $action, $id = null, $langToTranslate = null) {
        $response = new Response();

        try {
            $vocabulary = Category::getVocabularyByCategory($id);
            Category::removeAll($id);
            $this->get('session')->getFlashBag()->add(
                INFO,
                t('Category deleted successfully.')
            );
        }
        catch (Exception $e) {
            $this->get('session')->getFlashBag()->add(
                ERROR,
                t('Category could not be deleted.')
            );

        }

        $response = $this->redirect($this->generateUrl('drufony_content_actions', array('lang'        => $lang,
                                                                                        'contentType' => 'vocabulary',
                                                                                        'id'          => $vocabulary['vid'],
                                                                                        'action'      => 'edit')));

        return $response->setContent('Delete category controller');
    }

    public function reportAbuseAction(Request $request, $lang, $nid) {
        $response = new Response();
        $pathDestination = $request->query->get('destination');
        $pathRedirect    = $pathDestination ? $pathDestination : 'drufony_home_url';

        $content = ContentUtils::nodeLoad($nid, $lang);
        $contentUrl= '/' . $lang . '/' . $content->getUrl();
        $contentUri = $request->getUriForPath($contentUrl);

        //Send email to default address, fix this if another email is neccesary
        Mailing::sendReportAbuse(DEFAULT_EMAIL_ADDRESS, $nid, $contentUri);

        $this->get('session')->getFlashBag()->add(
            INFO,
            t('Thank for, a moderator will review your request')
        );

        return $this->redirect($pathRedirect);

    }

    public function generateRssAction(Request $request, $lang, $rssType, $id) {
        $response = new Response();

        $channel = array();
        if ($rssType == 'section') {
            $channel= ContentUtils::getRssBySection($id);
        }
        else if ($rssType == 'category'){
            $channel = ContentUtils::getRssByTid($id);
        }
        else {
            throw new NotFoundHttpException("Page not found");
        }

        $rssContent = $this->renderView('DrufonyCoreBundle::rss.html.twig', array('lang' => $lang, 'channel' => $channel));

        $response->headers->set('Content-Type', 'application/rss+xml');
        $response->setContent($rssContent);

        return $response;
    }
}
