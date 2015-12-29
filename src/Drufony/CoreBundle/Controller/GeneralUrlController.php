<?php

namespace Drufony\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Drufony\CoreBundle\Model\Path;
use Drufony\CoreBundle\Model\ContentUtils;
use Drufony\CoreBundle\Model\Utils;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Drufony\CoreBundle\Model\Geo;

class GeneralUrlController extends DrufonyController
{
    /**
     * indexAction
     *
     * Catchall controller for any not-found URL in Symfony.
     *     The real path for an URL is checked with DB model.
     *     Forwards to real controller in order to machineName found.
     *
     * @param string $lang
     * @param string $url
     * @return void
     */
    public function indexAction($lang = DEFAULT_LANGUAGE, $url = 'index') {

	
        $contentData = $this->_getContentData($lang, $url);
	if($url=='index'){
		$contentData['contentType']='page';
		$contentData['oid']=17696;
	}
        $matches = array();
        if (!empty($contentData) && array_key_exists('redirect', $contentData) && $contentData['redirect']) {
		return $this->redirect($this->generateUrl('drufony_general_url', array('lang' => $contentData['lang'],
                                                                                   'url' => $contentData['url'])));
        }
        else if (!empty($contentData) && $contentData['contentType'] && $contentData['oid']) {
            return $this->forward('CustomProjectBundle:' . ucfirst($contentData['contentType']) . ':index', array(
                    'oid' => $contentData['oid'],
                    'template' => $contentData['contentType'],
                    'lang' => $lang,
                ));
        }
        else {
            // If not found on Drupal, returns a 404 error page.
            throw $this->createNotFoundException(t('404 Page Not Found'));
        }
    }

    /**
     * _getContentData
     *
     * Returns content type machineName and nodeId by urlAlias.
     *
     * @param string $lang
     * @param string $url
     * @return array
     */
    public function _getContentData($lang, $url) {
        $url = rtrim($url, '/');
        $oid = null;
        $contentType = null;
        $contentData = array();
        $nodeData = array();

        if ($url) {
            $sql = 'SELECT oid, module, expirationDate, target FROM url_friendly WHERE target = ?';
            $urlData = db_fetchAssoc($sql, array($url));

            //If expirationDate is not null, is no the current URL
            if($urlData && !is_null($urlData['expirationDate'])) {
                $realTarget = Utils::getCorrectUrl($urlData);

                if(!is_null($realTarget)) {
                    return array('redirect' => true, "lang" => $lang, 'url' => $realTarget);
                }
            }
            $oid = $urlData['oid'];
        }

        if ($urlData && $lang) {
            $sql = "SELECT type, nid FROM ${urlData['module']} WHERE id = ? AND lang = ?";
            $nodeData = db_fetchAssoc($sql, array($urlData['oid'], $lang));
        }
        if (!empty($nodeData)) {
            $contentData = array('oid' => $nodeData['nid'], 'contentType' => is_null($nodeData['type']) ? $urlData['module'] : $nodeData['type']);
        }
        return $contentData;
    }
}
