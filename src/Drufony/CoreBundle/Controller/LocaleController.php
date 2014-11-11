<?php

namespace Drufony\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drufony\CoreBundle\Model\Locale;
use Drufony\CoreBundle\Form\TranslateEditFormType;

class LocaleController extends DrufonyController
{
    public function translateInterfaceAction(Request $request, $lang, $action, $id) {
        return $this->forward("DrufonyCoreBundle:Locale:${action}Translation", array(
            'request' => $request, 'lang' => $lang, 'action' => $action,
            'lid'     => $id
        ));
    }

    function editTranslationAction(Request $request, $lid, $lang) {
        $response = new Response();
        $string = Locale::getSourceStringByLid($lid);
        $string = $string['source'];
        if (!empty($string)) {
            $translationForm = $this->createForm(new TranslateEditFormType(), array('lid' => $lid, 'string' => $string));
            if ($request->getMethod() == 'POST') {
                $translationForm->handleRequest($request);
                if ($translationForm->isValid()) {
                    $data = $translationForm->getData();
                    $languages = Locale::getAllLanguages();
                    foreach ($languages as $langKey => $langName) {
                        if ($langKey != Locale::DRUFONY_DEFAULT_LANG && !empty($data[$langKey])) {
                            Locale::saveTranslation($lid, $data[$langKey], $langKey);
                            return $this->redirect($this->generateUrl('drufony_translate_search', array('lang' => $lang)));
                        }
                    }
                }
            }


            /* Adds items for section breadcrumb*/
            $breadCrumb = array(
              'dashboard' => array( 'label' => 'Dashboard', 'url' => 'drufony_home_dashboard'),
              'translations' => array( 'label' => 'Translations', 'url' => 'drufony_translations_path'),
              'overview' => array( 'label' => 'Translation Edit', 'url' => 'drufony_translationOverview_path'),
            );

            $response->setContent($this->renderView('DrufonyCoreBundle::base.html.twig', array(
                'lang'=>$lang,
                'left' => 'DrufonyCoreBundle::left.html.twig',
                'dashboard' => 'DrufonyCoreBundle::content_create_form.html.twig',
                'form' => $translationForm->createView(),
                'itemMenu' => 'Translation',
                'contentType' => '',
                'columnRight' => '',
                'breadCrumb' => $breadCrumb,
                'string' => $string
            )));
        }
        else {
          throw $this->createNotFoundException(t('This source string doesn\'t exist'));
        }

        return $response;
    }

    function deleteTranslationAction(Request $request, $lang, $action, $lid) {
        $response = new Response();
        Locale::deleteTranslations($lid);
        Locale::deleteSourceString($lid);

        return $this->redirect($this->generateUrl('drufony_translate_search', array('lang' => $lang)));
    }

    /**
     * Generates the text of the po file for all the content
     *
     * @param string $lang
     *
     * @param string $poLang
     *   The language for wich the file will be generated
     *
     */
    function generateTranslationFilesAction($lang, $poLang) {
        $response = new Response();

        $languages = Locale::getAllLanguages();
        unset($languages[Locale::DRUFONY_DEFAULT_LANG]);

        $defaultLangStrings = Locale::searchTranslatable('');

        $output = '';
        foreach ($defaultLangStrings as $key => $string) {
            $output .= '#String: ' . $key . "\n";
            $output .= 'msgid "' . $string['source'] . '"' . "\n";
            $translated_string = (isset($defaultLangStrings[$key]['translation'][$poLang]) && !empty($defaultLangStrings[$key]['translation'][$poLang])) ? $defaultLangStrings[$key]['translation'][$poLang] : '';
            $output .= 'msgsrt "' . $translated_string . '"' . "\n";
            $output .= "\n";
        }

        $response->headers->set('Content-Type', 'text/po');
        $response->headers->set('Content-Disposition', 'attachment; filename="drufony-vintage-strings-' . $poLang . '.po"');
        $response->setContent($output);

        return $response;
    }
}
