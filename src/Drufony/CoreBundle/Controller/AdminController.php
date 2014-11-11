<?php

namespace Drufony\CoreBundle\Controller;
use Drufony\CoreBundle\Model\Geo;
use Drufony\CoreBundle\Form\CountryListFormType;
use Drufony\CoreBundle\Form\CountryShippingCostFormType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Drufony\CoreBundle\Form\TranslateSearchFormType;
use Drufony\CoreBundle\Model\Permission;
use Drufony\CoreBundle\Model\ContentUtils;
use Drufony\CoreBundle\Model\Locale;
use Drufony\CoreBundle\Form\AccessesFormType;
use Drufony\CoreBundle\Model\Access;
use Drufony\CoreBundle\Model\Category;
use Drufony\CoreBundle\Model\Task;
use Drufony\CoreBundle\Entity\Comment;
use Drufony\CoreBundle\Model\CommerceUtils;
use Drufony\CoreBundle\Form\ShippingFeeFormType;
use Drufony\CoreBundle\Form\SettingsFormType;
use Drufony\CoreBundle\Model\Setting;
use Drufony\CoreBundle\Model\Menu;
use Drufony\CoreBundle\Form\MenuFormType;
use Drufony\CoreBundle\Form\CouponFormType;

class AdminController extends DrufonyController
{
    function manageContentAction(Request $request, $lang, $feature, $contentType = 'item', $page = 1) {
        return $this->forward("DrufonyCoreBundle:Admin:${feature}", array(
            'request' => $request, 'lang' => $lang, 'feature' => $feature,
            'page'    => $page, 'contentType' => $contentType,
        ));
    }

    function accessesAction(Request $request, $lang) {
        $response = new Response();
        $roles = Access::getRoles();
        $modules = Access::getAllModuleAccess();
        $accessesForm = $this->createForm(new AccessesFormType());
        if ($request->getMethod() == 'POST') {
            $accessesForm->handleRequest($request);
            if ($accessesForm->isValid()) {
                $data = $accessesForm->getData();

                $this->get('session')->getFlashBag()->add(
                    INFO,
                    t('Your changes were saved!')
                );

                unset($data['action']);
                Access::cleanAccesses();
                foreach ($data as $accessName => $access) {
                    $module = isset($modules[$accessName]) ? $modules[$accessName] : '';
                    foreach ($access as $itemPerm) {
                        $perm = explode('[', $itemPerm);
                        $rid = $perm[0];
                        $permName = substr($perm[1], 0, strlen($perm[1]) - 1);
                        Access::assignAccess($rid, $permName, $module);
                    }
                }
            }
        }
        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'accesses' => array( 'label' => 'Accesses permssions', 'url' => 'drufony_accesses'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'=> $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'users',
                'dashboard' => 'DrufonyCoreBundle::accesses.html.twig',
                'title' => t('Accesses permissions'),
                'breadCrumb' => $breadCrumb,
                'accessesForm' => $accessesForm->createView(),
                'modules' => $modules,
                'roles' => $roles,
          )
        ));

        return $response;
    }

    function generateTranslationFilesAction($lang, $poLang) {
        $response = new Response();

        $languages = Locale::getAllLanguages();
        unset($languages[Locale::DRUFONY_DEFAULT_LANG]);

        $defaultLangStrings = Locale::searchTranslatable('');

        $output = '';
        foreach ($defaultLangStrings as $key => $string) {
            $output .= '#String: ' . $key . "\n";
            $output .= 'msgid "' . $string['source'] . '"' . "\n";
            $translated_string = (isset($defaultLangStrings[$key]['translations'][$poLang]) && !empty($defaultLangStrings[$key]['translations'][$poLang])) ? $defaultLangStrings[$key]['translations'][$poLang] : '';
            $output .= 'msgsrt "' . $translated_string . '"' . "\n";
            $output .= "\n";
        }

        $response->headers->set('Content-Type', 'text/po');
        $response->headers->set('Content-Disposition', 'attachment; filename="drufony-vintage-strings-' . $poLang . '.po"');
        $response->setContent($output);

        return $response;
    }

    function contentTranslateSearchAction(Request $request) {
        $response = new Response();
        $languages = Locale::getAllLanguages();
        unset($languages[Locale::DRUFONY_DEFAULT_LANG]);
        $searched = FALSE;
        $nodes = ContentUtils::getTranslationContentStatus();
        $response->setContent($this->renderView('DrufonyCoreBundle::contentTranslateSearch.html.twig', array(
            'lang'=> 'en',
            'searched' => $searched,
            'nodes' => $nodes,
            'languages' => $languages,
        )));

        return $response;
    }

    function translateSearchAction(Request $request, $lang = Locale::DRUFONY_DEFAULT_LANG) {
        $translations = array();
        $languages    = Locale::getAllLanguages();
        $searched     = FALSE;

        $translationForm= $this->createForm(new TranslateSearchFormType());
        if ($request->getMethod() == 'POST') {
            $translationForm->handleRequest($request);
            if ($translationForm->isValid()) {
                $data         = $translationForm->getData();
                $translations = Locale::searchTranslatable($data['search'], $data['language']);
                $searched     = TRUE;
            }
        }

        // Default search
        if ($searched == FALSE) {
            $translations = Locale::searchTranslatable();
        }

        $response = new Response();

        /* Set cols to show in table */
        $tableCols = array(
          'source' => array('name' => 'source', 'label' => t('Source') ),
        );

        foreach($languages as $rowKey => $rowValue) {
            if ($rowKey != Locale::DRUFONY_DEFAULT_LANG) {
                $tableCols[$rowKey] = array('name' => $rowKey, 'label' => t($rowValue), 'icon' => true);
            }
        }

        /* Set rows to show in table */
        $tableRows = $translations;

        /* Set handles for different types */
        $tableRows = array_map(function($row) use ($languages) {
            foreach($languages as $rowKey => $rowValue) {
                if ($rowKey != Locale::DRUFONY_DEFAULT_LANG) {
                    $row[$rowKey] = isset($row['languages'][$rowKey]) ? 'fa fa-check-square-o' : 'fa fa-square-o';
                }
            }
            return $row;
        }, $tableRows);

        /* Set buttons, actions and icons to show in view */
        $tableActions = array(
            'edit' => array('label' => 'edit', 'link' => 'drufony_translate_actions',
                            'id'    => 'lid',  'op'   => 'edit',
                            'icon'  => 'fa fa-edit'),
            'delete' => array('label' => 'delete',      'link' => 'drufony_translate_actions',
                              'id'    => 'lid',         'op'   => 'delete',
                              'type'  => 'contentType', 'icon' => 'fa fa-trash-o'),
        );

        $languages = Locale::getAllLanguages();
        $languagesOptions = array();
        foreach($languages as $language => $name) {
            $languagesOptions[] = array(
                'label'         => t("Get po file"),
                'link'          => 'drufony_generate_po_files',
                'poLang'        => $language,
                'languageLabel' => $name,
            );
        }

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard'   => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'translation' => array( 'label' => 'Translations', 'url' => 'drufony_manage_path'),
          'interface'   => array( 'label' => 'Interface Translation', 'url' => 'drufony_promoted_list'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'                => $lang,
                'left'                => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'            => 'Translation',
                'dashboard'           => 'DrufonyCoreBundle::general_list_table.html.twig',
                'title'               => t('Interface Translation'),
                'tableRows'           => $tableRows,
                'tableCols'           => $tableCols,
                'editUrl'             => 'drufony_item_edit',
                'breadCrumb'          => $breadCrumb,
                'tableActions'        => $tableActions,
                'languagesOptions'    => $languagesOptions,
                'translateFormSearch' => $translationForm->createView(),
          )
        ));
        return $response;

    }

    function promotedAction(Request $request, $lang, $feature, $contentType, $page) {
        $response = new Response();

        /* Set rows to show in table */
        $tableRows = array();
        if($contentType == 'all') {
            $tableRows = ContentUtils::getAllPromoted($lang, $page);
            $tableResults = ContentUtils::getAllPromotedCount($lang);
        }
        else {
            $tableRows = ContentUtils::getPromoted($contentType, $lang, $page);
            $tableResults = ContentUtils::getPromotedCount($contentType, $lang);
        }

        /* Set cols to show in table */
        $tableCols = array(
          'title'   => array('name' => 'title',            'label' => t('Title'), 'class' => 'wd65'),
          'type'    => array('name' => 'contentType',      'label' => t('Type'), 'class' => 'wd10' ),
          'author'  => array('name' => 'authorName',       'label' => t('Author'), 'class' => 'wd10' ),
          'updated' => array('name' => 'modificationDate', 'label' => t('Updated'), 'class' => 'wd10' ),
        );

        $tableActions = array(
            'edit' => array('label' => 'edit', 'link' => 'drufony_content_actions',
                            'id'    => 'nid',  'op'   => 'edit',
                            'icon'  => 'fa fa-edit'),
            'delete' => array('label' => 'delete',      'link' => 'drufony_content_actions',
                              'id'    => 'nid',         'op'   => 'delete',
                              'type'  => 'contentType', 'icon' => 'fa fa-trash-o'),
        );

        /* Set buttons, actions and icons to show in view */
        $actionButtons = $this->_getActionButtons();
        $contentTypeButtons = $this->_getContentTypeButtons($contentType, $feature);

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => t('Dashboard'),         'url' => 'drufony_home_dashboard'),
          'manage'    => array( 'label' => t('Manage'),            'url' => 'drufony_manage_path'),
          'promoted'  => array( 'label' => t('Content: Promoted'), 'url' => 'drufony_promoted_list'),
      );

        /* Set pagination info */
        $pages = ceil($tableResults / ITEMS_PER_PAGE);
        $pagination = array(
            'currentPage' => $page,
            'pages' => $pages,
            'results' => $tableResults,
            'elementsByPage' => ITEMS_PER_PAGE,
            'currentPath'   => $request->get('_route'),
            'action' => $feature,
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'               => $lang,
                'left'               => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'           => 'Manage',
                'dashboard'          => 'DrufonyCoreBundle::content_list_table.html.twig',
                'title'              => t('Content: Promoted'),
                'tableRows'          => $tableRows,
                'tableCols'          => $tableCols,
                'actionButtons'      => $actionButtons,
                'contentTypeButtons' => $contentTypeButtons,
                'editUrl'            => 'drufony_item_edit',
                'breadCrumb'         => $breadCrumb,
                'tableActions'       => $tableActions,
                'pagination'         => $pagination,
          )
        ));
        return $response;
    }


    function latestAction(Request $request, $lang, $feature, $contentType, $page) {
        $response = new Response();

        //FIXME only actual lang is shown
        $manageLang = $lang;

        $tableRows = array();
        if($contentType == 'all') {
            $tableRows = ContentUtils::getAllPublished($manageLang, $page);
            $tableResults = ContentUtils::getAllPublishedCount($manageLang);
        }
        else {
            $tableRows = ContentUtils::getPublished($contentType, $manageLang, $page);
            $tableResults = ContentUtils::getPublishedCount($contentType, $manageLang);
        }

        /* Set handles for different types */
        $tableRows = array_map(function($row) {
            $row->__set('published', $row->isPublished() == 0 ? t('Unpublished') : t('Published'));
            return $row;
        }, $tableRows);

        /* Set cols to show in table */
        $tableCols = array(
          'title'   => array('name' => 'title',            'label' => t('Title'), 'class' => 'wd55'),
          'type'    => array('name' => 'contentType',      'label' => t('Type'), 'class' => 'wd10' ),
          'author'  => array('name' => 'authorName',       'label' => t('Author'), 'class' => 'wd10' ),
          'updated' => array('name' => 'modificationDate', 'label' => t('Updated'), 'class' => 'wd10' ),
          'published' => array('name' => 'publicationDate','label' => t('Published'), 'class' => 'wd10' ),
        );

        /* Set buttons, actions and icons to show in view */
        $actionButtons = $this->_getActionButtons();
        $contentTypeButtons = $this->_getContentTypeButtons($contentType, $feature);

        $tableActions = array(
            'edit'   => array('label' => 'edit', 'link' => 'drufony_content_actions',
                              'id'    => 'nid',  'op'   => 'edit',
                              'icon'  => 'fa fa-edit'),
            'delete' => array('label' => 'delete',        'link'        => 'drufony_content_actions',
                              'id'    => 'nid',           'op'          => 'delete',
                              'icon'  => 'fa fa-trash-o', 'destination' => 'drufony_manage_content'),
        );

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
            'dashboard' => array( 'label' => t('Dashboard'),       'url' => 'drufony_home_dashboard'),
            'manage'    => array( 'label' => t('Manage'),          'url' => 'drufony_manage_path'),
            'latest'    => array( 'label' => t('Content: Latest'), 'url' => 'drufony_latest_list'),
        );

        /* Set results count */
        $pages = ceil($tableResults / ITEMS_PER_PAGE);

        /* Set pagination info */
        $pagination = array(
            'currentPage' => $page,
            'pages' => $pages, //FIXME Get total pages
            'results' => $tableResults,
            'elementsByPage' => ITEMS_PER_PAGE,
            'currentPath'   => $request->get('_route'),
            'action' => $feature,
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
            array('lang'             => $lang,
                'left'               => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'           => 'Manage',
                'dashboard'          => 'DrufonyCoreBundle::content_list_table.html.twig',
                'title'              => t('Content: Latest'),
                'tableRows'          => $tableRows,
                'tableCols'          => $tableCols,
                'tableActions'       => $tableActions,
                'actionButtons'      => $actionButtons,
                'contentTypeButtons' => $contentTypeButtons,
                'breadCrumb'         => $breadCrumb,
                'pagination'         => $pagination,
          )
        ));
        return $response;
    }

    function stickyAction(Request $request, $lang, $feature, $contentType, $page) {
        $response = new Response();

        /* Set rows to show in table */
        $tableRows = array();
        if($contentType == 'all') {
            $tableRows = ContentUtils::getAllSticky($lang, $page);
            $tableResults = ContentUtils::getAllStickyCount($lang);
        }
        else {
            $tableRows = ContentUtils::getSticky($contentType, $lang, $page);
            $tableResults = ContentUtils::getStickyCount($contentType, $lang);
        }

        /* Set cols to show in table */
        $tableCols = array(
          'title'   => array('name' => 'title',            'label' => t('Title'), 'class' => 'wd65'),
          'type'    => array('name' => 'contentType',      'label' => t('Type'), 'class' => 'wd10' ),
          'author'  => array('name' => 'authorName',       'label' => t('Author'), 'class' => 'wd10' ),
          'updated' => array('name' => 'modificationDate', 'label' => t('Updated'), 'class' => 'wd10' ),
        );

        /* Set buttons, actions and icons to show in view */
        $actionButtons = $this->_getActionButtons();
        $contentTypeButtons = $this->_getContentTypeButtons($contentType, $feature);

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => t('Dashboard'),       'url' => 'drufony_home_dashboard'),
          'manage'    => array( 'label' => t('Manage'),          'url' => 'drufony_manage_path'),
          'sticky'    => array( 'label' => t('Content: Sticky'), 'url' => 'drufony_sticky_list'),
        );

        $tableActions = array(
            'edit' => array('label' => 'edit', 'link' => 'drufony_content_actions',
                            'id'    => 'nid', 'op'    => 'edit',
                            'icon'  => 'fa fa-edit'),
            'delete' => array('label' => 'delete',      'link' => 'drufony_content_actions',
                              'id'    => 'nid',         'op'   => 'delete',
                              'type'  => 'contentType', 'icon' => 'fa fa-trash-o'),
        );

        /* Set pagination info */
        $pages = ceil($tableResults / ITEMS_PER_PAGE);
        $pagination = array(
            'currentPage' => $page,
            'pages' => $pages,
            'results' => $tableResults,
            'elementsByPage' => ITEMS_PER_PAGE,
            'currentPath'   => $request->get('_route'),
            'action' => $feature,
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'               => $lang,
                'left'               => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'           => 'Manage',
                'dashboard'          => 'DrufonyCoreBundle::content_list_table.html.twig',
                'title'              => t('Content: Sticky'),
                'tableRows'          => $tableRows,
                'tableCols'          => $tableCols,
                'tableActions'       => $tableActions,
                'actionButtons'      => $actionButtons,
                'contentTypeButtons' => $contentTypeButtons,
                'breadCrumb'         => $breadCrumb,
                'pagination'         => $pagination,
          )
        ));
        return $response;
    }

    function unpublishedAction(Request $request, $lang, $feature, $contentType, $page) {
        $response = new Response();

        /* Set rows to show in table */
        $tableRows = array();
        if($contentType == 'all') {
            $tableRows = ContentUtils::getAllUnpublished($lang, $page);
            $tableResults = ContentUtils::getAllUnpublishedCount($lang);
        }
        else {
            $tableRows = ContentUtils::getUnpublished($contentType, $lang, $page);
            $tableResults = ContentUtils::getUnpublishedCount($contentType, $lang);
        }

        /* Set cols to show in table */
        $tableCols = array(
          'title'   => array('name' => 'title',            'label' => t('Title'), 'class' => 'wd65'),
          'type'    => array('name' => 'contentType',      'label' => t('Type'), 'class' => 'wd10' ),
          'author'  => array('name' => 'authorName',       'label' => t('Author'), 'class' => 'wd10' ),
          'updated' => array('name' => 'modificationDate', 'label' => t('Updated'), 'class' => 'wd10' ),
        );

        /* Set buttons, actions and icons to show in view */
        $actionButtons = $this->_getActionButtons();
        $contentTypeButtons = $this->_getContentTypeButtons($contentType, $feature);

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
            'dashboard'   => array('label' => t('Dashboard'),            'url' => 'drufony_home_dashboard'),
            'manage'      => array('label' => t('Manage'),               'url' => 'drufony_manage_path'),
            'unpublished' => array('label' => t('Content: Unpublished'), 'url' => 'drufony_unpublished_list'),
        );

        $tableActions = array(
            'edit'    => array('label' => 'edit',        'link' => 'drufony_content_actions',
                               'id'    => 'nid',         'op'   => 'edit',
                               'icon'  => 'fa fa-edit'),
            'delete'  => array('label' => 'delete',      'link' => 'drufony_content_actions',
                               'id'    => 'nid',         'op'   => 'delete',
                               'type'  => 'contentType', 'icon' => 'fa fa-trash-o'),
        );

        /* Set pagination info */
        $pages = ceil($tableResults / ITEMS_PER_PAGE);
        $pagination = array(
            'currentPage' => $page,
            'pages' => $pages,
            'results' => $tableResults,
            'elementsByPage' => ITEMS_PER_PAGE,
            'currentPath'   => $request->get('_route'),
            'action' => $feature,
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
            array(
                'lang'               => $lang,
                'left'               => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'           => 'Manage',
                'dashboard'          => 'DrufonyCoreBundle::content_list_table.html.twig',
                'title'              => t('Content: Unpublished'),
                'tableRows'          => $tableRows,
                'tableCols'          => $tableCols,
                'tableActions'       => $tableActions,
                'actionButtons'      => $actionButtons,
                'breadCrumb'         => $breadCrumb,
                'pagination'         => $pagination,
                'contentTypeButtons' => $contentTypeButtons,
            )
        ));

        return $response;
    }

    function scheduleAction(Request $request, $lang, $feature, $contentType, $page) {
        $response = new Response();

        /* Set rows to show in table */
        $tableRows = array();
        if($contentType == 'all') {
            $tableRows = ContentUtils::getAllScheduled($lang, $page);
        }
        else {
            $tableRows = ContentUtils::getScheduled($contentType, $lang, $page);
        }

        /* Set cols to show in table */
        $tableCols = array(
            'title'   => array('name' => 'title',                 'label' => t('Title'),              'width' => '25%'),
            'type'    => array('name' => 'contentType',           'label' => t('Type') ),
            'author'  => array('name' => 'authorName',            'label' => t('Author') ),
            'status'  => array('name' => 'futurePublicationDate', 'label' => t('Publication Date') ),
            'updated' => array('name' => 'modificationDate',      'label' => t('Updated') ),
        );

        /* Set buttons, actions and icons to show in view */
        $actionButtons = $this->_getActionButtons();
        $contentTypeButtons = $this->_getContentTypeButtons($contentType, $feature);

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => t('Dashboard'),         'url' => 'drufony_home_dashboard'),
          'manage'    => array( 'label' => t('Manage'),            'url' => 'drufony_manage_path'),
          'schedule'  => array( 'label' => t('Content: Schedule'), 'url' => 'drufony_schedule_list'),
        );

        $tableActions = array(
            'edit' => array('label' => 'edit', 'link' => 'drufony_content_actions',
                            'id'    => 'nid',  'op'   => 'edit',
                            'icon'  => 'fa fa-edit'),
            'delete' => array('label' => 'delete',      'link' => 'drufony_content_actions',
                              'id'    => 'nid',         'op'   => 'delete',
                              'type'  => 'contentType', 'icon' => 'fa fa-trash-o'),
        );

        /* Set pagination info */
        $pagination = array(
            'currentPage' => $page,
            'pages' => 10, //FIXME Get total pages
            'elementsByPage' => ITEMS_PER_PAGE,
            'currentPath'   => $request->get('_route'),
            'action' => $feature,
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'               => $lang,
                'left'               => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'           => 'Manage',
                'dashboard'          => 'DrufonyCoreBundle::content_list_table.html.twig',
                'title'              => t('Content: Schedule'),
                'tableRows'          => $tableRows,
                'tableCols'          => $tableCols,
                'tableActions'       => $tableActions,
                'actionButtons'      => $actionButtons,
                'breadCrumb'         => $breadCrumb,
                'pagination'         => $pagination,
                'contentTypeButtons' => $contentTypeButtons,
          )
        ));
        return $response;
    }

    function tasksAction(Request $request, $lang, $page) {
        $response = new Response();

        /* Set rows to show in table */
        $tableRows = Task::getAll($page);

        /* Set cols to show in table */
        $tableCols = array(
          'type' => array('name' => 'title', 'label' => t('Type'), 'class' => 'wd65'),
          'user' => array('name' => 'userName', 'label' => t('Assigned to'), 'class' => 'wd10'),
          'status' => array('name' => 'statusName', 'label' => t('Status'), 'class' => 'wd10'),
          'level' => array('name' => 'levelName', 'label' => t('Level'), 'class' => 'wd10'),
        );

        $tableActions = array(
            'edit' => array('label' => 'edit', 'link' => 'drufony_content_actions',
            'id' => 'id', 'op' => 'edit', 'icon' => 'fa fa-edit'),
            'delete' => array('label' => 'delete', 'link' => 'drufony_content_actions',
            'id' => 'id', 'op' => 'delete', 'icon' => 'fa fa-trash-o'),
        );

        /* Set buttons, actions and icons to show in view */
        $actionButtons = array(array(
            'label'       => t('Add task'), 'link'   => 'drufony_content_actions', 'icon' => 'fa fa-plus-circle',
            'contentType' => 'task',        'action' => 'create'
        ));

        /* Set handles for different types */
        $tableRows = array_map(function($row) {
            $row['userName'] = is_null($row['userName']) ? t('None') : $row['userName'];
            return $row;
        }, $tableRows);

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'tasks' => array( 'label' => 'Tasks', 'url' => 'drufony_tasks_path'),
        );

        /* Set results count */
        $tableResults = Task::getAllCount();
        $pages = ceil($tableResults / ITEMS_PER_PAGE);

        /* Set pagination info */
        $pagination = array(
            'currentPage' => $page,
            'pages' => $pages, //FIXME Get total pages
            'results' => $tableResults,
            'elementsByPage' => ITEMS_PER_PAGE,
            'currentPath'   => $request->get('_route'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'=> $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'Tasks',
                'dashboard' => 'DrufonyCoreBundle::content_list_table.html.twig',
                'title' => t('Tasks'),
                'tableRows' => $tableRows,
                'tableCols' => $tableCols,
                'actionButtons' => $actionButtons,
                'tableActions' => $tableActions,
                'breadCrumb' => $breadCrumb,
                'pagination' => $pagination,
          )
        ));
        return $response;
    }

    public function eventsAction(Request $request, $lang) {
        $response = new Response();

        $events = ContentUtils::getEvents();

        /* Set buttons, actions and icons to show in view */
        $actionButtons = $this->_getActionButtons();

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'schedule' => array( 'label' => 'Schedule', 'url' => 'drufony_schedule_list'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig', array(
                'lang'          => $lang,
                'top-menu-bar'  => 'top-menu-bar.html.twig',
                'left'          => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'      => 'Unknow',
                'dashboard'     => 'DrufonyCoreBundle::schedule.html.twig',
                'title'   => t('Schedule'),
                'titleSection'  => 'Schedule',
                'contentType'   => 'event',
                'events'        => $events,
                'actionButtons' => $actionButtons,
                'breadCrumb'    => $breadCrumb,
            )));

        return $response;
    }

    function viewTaskAction(Request $request, $lang, $id) {
        $response = new Response();
        $task = Task::load($id);
        if (empty($task)) {
            throw $this->createNotFoundException(t('This task doesn\'t exist'));
        }
        $response->setContent($this->renderView('DrufonyCoreBundle::viewTask.html.twig', array('lang' => $lang, 'task' => $task)));

        return $response;
    }

    function addShippingFeeAction(Request $request, $lang) {
        $shippingForm = $this->createForm(new ShippingFeeFormType(), array());

        if ($request->getMethod() == 'POST') {
            $shippingForm->handleRequest($request);

            if ($shippingForm->isValid()) {
                $data = $shippingForm->getData();
                CommerceUtils::saveShipping($data);

                return new Response('Shipping fee created successfully', '200');
            }
        }

        $response = new Response();
        $response->setContent($this->renderView('DrufonyCoreBundle::shippingFeeForm.html.twig',
                            array('form' => $shippingForm->createView())));

        return $response;
    }


    function taxonomyAction(Request $request, $lang, $feature, $page) {
        $response = new Response();

        /* Set rows to show in table */
        $tableRows = Category::getVocabularies($page);

        /* Set cols to show in table */
        $tableCols = array(
          'name' => array('name' => 'name', 'label' => t('Name')),
        );

        $tableActions = array(
            'edit' => array('label' => 'edit', 'link' => 'drufony_content_actions',
            'id' => 'vid', 'contentType' => 'taxonomy', 'op' => 'edit', 'icon' => 'fa fa-edit'),
            'delete' => array('label' => 'deleteOneId', 'link' => 'drufony_content_actions',
            'id' => 'vid', 'contentType' => 'taxonomy', 'op' => 'delete', 'icon' => 'fa fa-trash-o'),
        );

        /* Set buttons, actions and icons to show in view */
        $actionButtons = array();
        $actionButtons[] = array(
            'label'  => t("Add Taxonomy"), 'link'        => 'drufony_content_actions',
            'icon'   => 'fa fa-plus-circle',        'contentType' => 'vocabulary',
            'action' => 'create'
        );

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'taxonomy' => array( 'label' => 'Taxonomy', 'url' => 'drufony_taxonomy_list'),
        );

        /* Set results count */
        $tableResults = Category::getVocabulariesCount();
        $pages = ceil($tableResults / ITEMS_PER_PAGE);

        /* Set pagination info */
        $pagination = array(
            'currentPage' => $page,
            'pages' => $pages, //FIXME Get total pages
            'results' => $tableResults,
            'elementsByPage' => ITEMS_PER_PAGE,
            'currentPath'   => $request->get('_route'),
            'feature' => $feature,
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig', array(
                'lang'          => $lang,
                'top-menu-bar'  => 'top-menu-bar.html.twig',
                'left'          => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'      => 'Manage',
                'dashboard'     => 'DrufonyCoreBundle::content_list_table.html.twig',
                'title'   => t('Taxonomy'),
                'titleSection'  => 'Taxonomy',
                'tableRows'     => $tableRows,
                'tableCols'     => $tableCols,
                'tableActions'  => $tableActions,
                'actionButtons' => $actionButtons,
                'breadCrumb'    => $breadCrumb,
                'pagination'    => $pagination,
            )));

        return $response;
    }

    function commentsAction(Request $request, $lang, $feature, $page) {
        $response = new Response();

        /* Set rows to show in table */
        $tableRows = Comment::getAll();


        /* Set cols to show in table */
        $tableCols = array(
          'subject' => array('name' => 'subject', 'label' => t('Subject'), 'class' => 'wd65'),
          'user' => array('name' => 'authorName', 'label' => t('User'), 'class' => 'wd10'),
          'status' => array('name' => 'status', 'label' => t('Status'), 'class' => 'wd10'),
          'date' => array('name' => 'created', 'label' => t('Date') , 'class' => 'wd10'),
        );

        $actions = array(
            'accept'  => array(
                'label' => 'approve', 'link' => 'drufony_content_actions', 'icon' => 'fa fa-check',
                'id'    => 'cid',     'op'   => 'approve',                 'contentType' => 'comment'),
            'delete'  => array(
                'label' => 'delete', 'link' => 'drufony_content_actions', 'icon' => 'fa fa-trash-o',
                'id'    => 'cid',    'op'   => 'delete',                  'contentType' => 'comment'),
        );

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'manage' => array( 'label' => 'Manage', 'url' => 'drufony_manage_path'),
          'comment' => array( 'label' => 'Comments', 'url' => 'drufony_comment_list'),
        );

        /* Set results count */
        $tableResults = Comment::getAllCount();
        $pages = ceil($tableResults / ITEMS_PER_PAGE);

        /* Set pagination info */
        $pagination = array(
            'currentPage' => $page,
            'pages' => $pages,
            'results' => $tableResults,
            'elementsByPage' => ITEMS_PER_PAGE,
            'currentPath'   => $request->get('_route'),
            'action' => $feature,
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig', array(
                'lang'          => $lang,
                'top-menu-bar'  => 'top-menu-bar.html.twig',
                'left'          => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'      => 'Manage',
                'dashboard'     => 'DrufonyCoreBundle::content_list_table.html.twig',
                'title'   => t('Comments'),
                'titleSection'  => 'Comments',
                'actionButtons' => '',
                'tableRows'     => $tableRows,
                'tableCols'     => $tableCols,
                'tableActions'  => $actions,
                'breadCrumb'    => $breadCrumb,
                'pagination'    => $pagination,
            )));

        return $response;
    }

    function settingsAction(Request $request, $lang) {
        $response = new Response();
        $user = $this->getUser();
        $settings  = $this->createForm(new SettingsFormType());
        if ($request->getMethod() == 'POST') {
            $settings->handleRequest($request);

            if ($settings->isValid()) {
                $data = $settings->getData();
                foreach($data as $key => $value) {
                    Setting::set($key, $value);
                }

                $this->get('session')->getFlashBag()->add(
                    INFO,
                    t('Your changes were saved!')
                );
            }
        }

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'settings' => array( 'label' => 'Settings', 'url' => 'drufony_create_path'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig', array(
                'lang'          => $lang,
                'top-menu-bar'  => 'top-menu-bar.html.twig',
                'left'          => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'      => 'settings',
                'dashboard'     => 'DrufonyCoreBundle::settings.html.twig',
                'title'         => t('Settings'),
                'content'       => 'content.html.twig',
                'titleSection'  => 'Settings',
                'settings'      => $settings->createView(),
                'breadCrumb'    => $breadCrumb,
            )));

        return $response;
    }

    function contentTranslationStatusAction(Request $request, $contentType, $lang) {
        $response = new Response();

        /* Set rows to show in table */
        $type = null;
        if($contentType != 'all') {
            $type = $contentType;
        }
        $tableRows = ContentUtils::getAllTranslationContentStatus($type, $page = 0, $itemsPerPage = ITEMS_PER_PAGE);

        $languages = Locale::getAllLanguages();

        /* Set cols to show in table */
        $tableCols = array(
          'title' => array('name' => 'title', 'label' => t('Title')),
          'type' => array('name' => 'contentType', 'label' => t('Type') ),
        );

        foreach($languages as $rowKey => $rowValue) {
            $tableCols[$rowKey] = array('name' => $rowKey, 'label' => t($rowValue), 'icon' => true);
        }

        /* Set handles for different types */
        $tableRows = array_map(function($row) use ($languages) {
            foreach($languages as $rowKey => $rowValue) {
                //$row[$rowKey] = $row[$rowKey] ? t('Done') : t('Pending');
                $row[$rowKey] = $row[$rowKey] ? 'fa fa-check-square-o' : 'fa fa-square-o';
            }
            return $row;
        }, $tableRows);

        $contentTypeButtons = $this->_getContentTypeButtons($contentType, '', 'drufony_translation_content');

        /* Set buttons, actions and icons to show in view */
        $tableActions = array(
            'edit' => array('label' => 'edit', 'link' => 'drufony_content_actions',
                            'id'    => 'nid',  'op'   => 'edit',
                            'icon'  => 'fa fa-edit'),
            'delete' => array('label' => 'delete',      'link' => 'drufony_content_actions',
                              'id'    => 'nid',         'op'   => 'delete',
                              'type'  => 'contentType', 'icon' => 'fa fa-trash-o'),
        );

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'translations' => array( 'label' => 'Translations', 'url' => 'drufony_translations_path'),
          'overview' => array( 'label' => 'Translation Overview', 'url' => 'drufony_translationOverview_path'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'               => $lang,
                'left'               => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu'           => 'Translation',
                'dashboard'          => 'DrufonyCoreBundle::content_list_table.html.twig',
                'title'              => t('Translation Overview'),
                'tableRows'          => $tableRows,
                'tableCols'          => $tableCols,
                'tableActions'       => $tableActions,
                'breadCrumb'         => $breadCrumb,
                'contentTypeButtons' => $contentTypeButtons,
          )
        ));
        return $response;
    }

    public function translateOverviewAction(Request $request, $lang) {
        $response = new Response();
        $types = ContentUtils::getAvailableContentTypes();
        $languages = Locale::getAllLanguages();
        $contentPercent = 0;
        foreach ($languages as $langKey => $langName) {
            foreach ($types as $type) {
                $contentPercent += ContentUtils::getTranslatedPercentage($type, $langKey);
            }
            $contentPercentage[$langKey] = number_format($contentPercent / count($types), 2);
            $interfacePercentage[$langKey] = number_format(Locale::getTranslateInterfacePercentage($langKey), 2);
            $contentPercent = 0;
        }

        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'translations' => array( 'label' => 'Translations', 'url' => 'drufony_translations_path'),
          'overview' => array( 'label' => 'Translation Overview', 'url' => 'drufony_translate_overview'),
        );
        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'=> $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'Translation',
                'dashboard' => 'DrufonyCoreBundle::translateOverview.html.twig',
                'contentType' => t('Translation Overview'),
                'contentPercentage' => $contentPercentage,
                'interfacePercentage' => $interfacePercentage,
                'languages' => $languages,
                'breadCrumb' => $breadCrumb,
          )
        ));
        return $response;

    }

    public function addMenuAction(Request $request, $lang, $parentId = null, $id = null) {
        $response = new Response();

        $formFields = array();
        if(!is_null($parentId)) {
            $parentMenu = Menu::get($parentId);

            if($parentMenu) {
                $formFields = array('type'       => $parentMenu['type'],
                                    'parentId'   => $parentMenu['itemId'],
                                    'lang'       => $parentMenu['lang'],
                                    'userTarget' => $parentMenu['userTarget'],
                                    'disable'    => true,
                                    );
            }
        }
        else if(!is_null($id)) {
            $formFields = Menu::get($id);
            $formFields['excludeId'] = $id;
        }

        $menuForm = $this->createForm(new MenuFormType(), array('info' => $formFields));

        if ($request->getMethod() == 'POST') {
            $menuForm->handleRequest($request);

            if ($menuForm->isValid()) {
                $data = $menuForm->getData();

                $data['parentId'] = 0;
                if(!is_null($parentId)) {
                    $data['type'] = $formFields['type'];
                    $data['parentId'] = $formFields['parentId'];
                    $data['lang'] = $formFields['lang'];
                }

                if(!is_null($id)) {
                    $data['itemId'] = $id;
                    $data['type'] = $formFields['type'];
                    $data['parentId'] = $formFields['parentId'];
                    $data['lang'] = $formFields['lang'];
                }

                $baseUrl = $base = $this->getRequest()->getSchemeAndHttpHost();
                list($urlToStore, $urlToCheck) = Menu::validateUrl($data['url'], $baseUrl);
                if($urlToStore == '') {
                    $menuForm->get('url')->addError(new FormError(t('The url given is not valid')));
                }
                else if($urlToCheck != '' && !Menu::urlExists($urlToCheck)){
                    $menuForm->get('url')->addError(new FormError(t('The url given does not exist')));
                }
                else {
                    Menu::save($data);
                    return $this->redirect($this->generateUrl('drufony_menu_list', array('lang' => $lang)));
                }
            }
        }

        $response->setContent($this->renderView('DrufonyCoreBundle::menuForm.html.twig',
                                                array('form' => $menuForm->createView(),
                                                    'lang' => $lang)
                                                ));
        return $response;
    }

    public function menuListAction(Request $request, $lang) {
        $response = new Response();

        $menus = Menu::getAll();

        $menuHeader = Menu::getMenu(MENU_TYPE_HEADER);
        $menuFooter = Menu::getMenu(MENU_TYPE_FOOTER);

        $response->setContent($this->renderView('DrufonyCoreBundle::menuList.html.twig',
                                                array('lang'     => $lang,
                                                'menus'          => $menus,
                                                'headerParents'  => $menuHeader->parents,
                                                'headerChildren' => $menuHeader->children,
                                                'footerParents'  => $menuFooter->parents,
                                                'footerChildren' => $menuFooter->children)
                                                ));
        return $response;
    }

    public function deleteMenuAction(Request $request, $lang, $id) {
        $response = new Response();
        Menu::delete($id);

        $menus = Menu::getAll();

        return $this->redirect($this->generateUrl('drufony_menu_list', array('lang' => $lang, 'menus' => $menus)));
    }

    private function _getActionButtons() {
        $contentTypes = ContentUtils::getAvailableContentTypes();
        $actionButtons = array();
        foreach ($contentTypes as $oneContentType) {
            $actionButtons[] = array(
                'label'  => t("Add ${oneContentType}"), 'link'        => 'drufony_content_actions',
                'icon'   => 'fa fa-plus-circle',        'contentType' => $oneContentType,
                'action' => 'create'
            );
        }

        return $actionButtons;
    }

    private function _getContentTypeButtons($contentType, $feature, $link = 'drufony_manage_content') {
        $contentTypes = ContentUtils::getAvailableContentTypes();
        $contentTypeButtons = array();
        foreach ($contentTypes as $oneContentType) {
            $label = ucfirst($oneContentType) . 's';
            $contentTypeButtons[] = array(
                'label'       => t("${label}"), 'link'      => $link,
                'contentType' => $oneContentType, 'feature' => $feature,
                'active'      => $contentType == $oneContentType ? 'active': '',
            );
        }
        $contentTypeButtons[] = array(
            'label'       => t("All"), 'link' => $link,
            'contentType' => 'all', 'feature' => $feature,
            'active'      => $contentType == 'all' ? 'active': '',
        );
        return $contentTypeButtons;
    }

    public function batchAction(Request $request, $file, $offset, $page, $lang, $numElements) {
        $response = new Response();
        $response->setContent($this->renderView('DrufonyCoreBundle::batch.html.twig',
            array(
                'lang'        => $lang,
                'file'        => $file,
                'offset'      => $offset,
                'numElements' => $numElements,
                'page'        => $page,
                'request'     => $request,
            )));
        return $response;
    }

    public function couponsListAction(Request $request, $lang) {
        $response = new Response();

        $uniqueCoupons = CommerceUtils::getCoupons(COUPON_UNIQUE);
        $multiCoupons = CommerceUtils::getCoupons(COUPON_MULTIUSER);

        $response->setContent($this->renderView('DrufonyCoreBundle::coupon_list.html.twig',
                                                array('uniqueCoupons' => $uniqueCoupons,
                                                      'multiCoupons'  => $multiCoupons,
                                                      'currency'      => DEFAULT_CURRENCY,
                                                      'enable'        => COUPON_ENABLED,
                                                      'disable'       => COUPON_DISABLED,
                                                      'unique'        => COUPON_UNIQUE,
                                                      'multiuser'     => COUPON_MULTIUSER,
                                                      'lang'          => $lang)
                                                ));
        return $response;
    }

    public function couponsFormAction(Request $request, $lang, $type, $id = null, $duplicate = false) {
        $response = new Response();

        $coupon = null;
        if(!is_null($id)) {
          $coupon = CommerceUtils::getCoupon($id);
          if (empty($coupon)) {
              throw $this->createNotFoundException(t('This coupon doesn\'t exist'));
          }
        }

        $couponForm= $this->createForm(new CouponFormType(), array('info' => $coupon, 'type' => $type, 'duplicate' => $duplicate));
        if ($request->getMethod() == 'POST') {
            $couponForm->handleRequest($request);
            if ($couponForm->isValid()) {
                $data = $couponForm->getData();

                #Duplicate means we dont get date from the form
                $startDate = !$duplicate ? date('Y-m-d', strtotime($data['startDate'])) : date('Y-m-d', strtotime("now"));
                $expirationDate = !$duplicate ? date('Y-m-d', strtotime($data['expirationDate'])) : date('Y-m-d', strtotime("now"));

                if($expirationDate < $startDate){
                    $id = null;
                    $couponForm->addError(new FormError(t('Expiration date have to be equal or greater than start date')));
                }
                else if(!is_null($id)) {
                    #Update the coupons giving the type
                    if(!$duplicate) {
                        $data['id'] = $id;
                        $id = CommerceUtils::saveCoupon($data, $type);
                    }
                    #Duplicate the coupon
                    else{
                        unset($coupon['id']);
                        for($i = 0; $i < $data['number']; $i++) {
                            $id = CommerceUtils::saveCoupon($coupon);
                        }
                    }
                }
                //Save coupons as usual
                else{
                    if(!array_key_exists('number', $data)) {
                        $id = CommerceUtils::saveCoupon($data);
                    }
                    else {
                        for($i = 0; $i < $data['number']; $i++) {
                            $id = CommerceUtils::saveCoupon($data);
                        }
                    }
                }

                if ($id != null) {
                    $this->get('session')->getFlashBag()->add(INFO, t('Your changes were saved!'));
                    return $this->redirect($this->generateUrl('drufony_coupons_list', array('lang' => $lang)));
                }
            }else{
                if(!array_key_exists('expirationDate', $couponForm->getData())) {
                    $couponForm->addError(new FormError(t('The expiration date given is not valid')));
                }
                if(!array_key_exists('startDate', $couponForm->getData())) {
                    $couponForm->addError(new FormError(t('The start date given is not valid')));
                }
                $this->get('session')->getFlashBag()->add(
                        ERROR,
                        t('Sorry, but your form was not processed! Please correct the following errors and submit the form again!')
                );

            }
        }

        $response->setContent($this->renderView('DrufonyCoreBundle::coupon_form.html.twig',
                                                array('lang' => $lang,
                                                      'form' => $couponForm->createView(),
                                                     )
                                                ));

        return $response;
    }

    public function couponsStatusAction(Request $request, $lang, $status, $id, $type) {

        if($status == COUPON_ENABLED) {
            CommerceUtils::enableCoupon($id, $type);
        }
        else if ($status == COUPON_DISABLED) {
            CommerceUtils::disableCoupon($id, $type);
        }
        else {
                $this->get('session')->getFlashBag()->add(
                        ERROR,
                        t('Sorry, the action you are trying to do, does not exist')
                );
        }

        return $this->redirect($this->generateUrl('drufony_coupons_list', array('lang' => $lang)));
    }

    public function dashboardHomeAction($lang) {
        $response = new Response();

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'=> $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'Home',
                'dashboard' => 'DrufonyCoreBundle::dashboard_home.html.twig',
                'title' => t('Home'),
                'breadCrumb' => $breadCrumb,
          )
        ));
        return $response;
    }

    public function nowStatsAction($lang) {
        $response = new Response();

        /* Adds items for section breadcrumb*/
        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'now' => array( 'label' => t('Now')),
        );

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'=> $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'Home',
                'dashboard' => 'DrufonyCoreBundle::stats_now.html.twig',
                'title' => t('Home'),
                'breadCrumb' => $breadCrumb,
          )
        ));

        return $response;
    }

    public function nowStatsAjaxRequestAction(Request $request) {
        $response = new Response();

        $today = date('Y-m-d');
        $startedCarts = CommerceUtils::getStartedCartsCount($today, $today);
        $saleForeCast = CommerceUtils::getSalesTotal($today, $today);
        $startedCheckoutsAmount = CommerceUtils::getStartedCheckoutsAmount($today, $today);
        //TODO: get checkout visits
        $checkoutVisits = 0;

        $response->setContent(json_encode(array(
            'status' => 'ok',
            'startedCarts' => $startedCarts,
            'checkoutsAmount' => $startedCheckoutsAmount,
            'saleForeCast' => $saleForeCast + $startedCheckoutsAmount,
            'checkoutVisits' => $checkoutVisits,
        )));

        $response->headers->set('Content-type', 'application/json');

        return $response;
    }

    public function countryShippingListAction(Request $request, $lang) {
        $response = new Response();

        $form = $this->createForm(new CountryListFormType(), array());

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                return $this->redirect($this->generateUrl('drufony_country_shipping_cost',
                    array('lang' => $lang, 'countryId' => $data['countryId'])));
            }
        }

        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'shipping_costs' => array( 'label' => 'Shipping costs', 'url' => 'drufony_country_list_shipping'),
        );

        $allLanguages = array_keys(Locale::getAllLanguages());
        $currentRoute = $request->get('_route');

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'=> $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'Home',
                'dashboard' => 'DrufonyCoreBundle::content_create_form.html.twig',
                'title' => t('Contry selection'),
                'itemMenu' => 'Manage',
                'breadCrumb' => $breadCrumb,
                'allLanguages' => $allLanguages,
                'currentRoute' => $currentRoute,
                'form' => $form->createView(),
          )
        ));

        return $response;
    }

    public function countryShippingCostAction(Request $request, $lang, $countryId) {
        $response = new Response();

        $form = $this->createForm(new CountryShippingCostFormType(), array());
        $defaultForm = $this->createForm(new CountryShippingCostFormType(), array());

        $shippingFees = CommerceUtils::getCountryShippingFees($countryId);

        foreach ($shippingFees as $index => $oneFee) {
            //Checks if is the default fee for that country
            if ($oneFee['weight'] == 0) {
                $form->get('default')->setData($oneFee['price']);
                $form->get('default_id')->setData($oneFee['id']);
                unset($shippingFees[$index]);
            }
        }
        $form->get('shippingFees')->setData($shippingFees);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $data['shippingFees'][] = array('weight' => 0, 'price' => $data['default'], 'id' => $data['default_id']);

                CommerceUtils::saveCountryShippingFees($countryId, $data['shippingFees']);

                $this->get('session')->getFlashBag()->add(INFO, t('Your changes were saved!'));

                return $this->redirect($this->generateUrl('drufony_country_list_shipping', array('lang' => $lang)));
            }
        }

        $breadCrumb = array(
          'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
          'shipping_costs' => array( 'label' => 'Shipping costs', 'url' => 'drufony_country_list_shipping'),
          'shipping_form' => array( 'label' => 'Shipping country fee', 'url' => 'drufony_country_list_shipping'),
        );

        $allLanguages = array_keys(Locale::getAllLanguages());
        $currentRoute = $request->get('_route');

        $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig',
          array('lang'=> $lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'itemMenu' => 'Home',
                'dashboard' => 'DrufonyCoreBundle::content_create_form.html.twig',
                'title' => t('Country shipping costs'),
                'itemMenu' => 'Manage',
                'breadCrumb' => $breadCrumb,
                'allLanguages' => $allLanguages,
                'currentRoute' => $currentRoute,
                'form' => $form->createView(),
          )
        ));

        return $response;
    }


}
