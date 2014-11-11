<?php
/**
 * Implementation of localization and internationalization methods.
 * This class is the most important for getting a multilingual environment.
 * It handles notifications about untranslated strings, and allows to
 * translate any string, besides getting a translation from a string.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Drufony\CoreBundle\Model\Utils;
use Drufony\CoreBundle\Model\Drupal;

// Class constants
// Defines constants if undefined. Those constants could be defined in
// a setting file.
defined('DEFAULT_LANGUAGE') or define('DEFAULT_LANGUAGE','en');

/**
 * Localization and internationalization class. Provides methods for translating
 * setrings and getting translations.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Locale
{
    const DRUFONY_DEFAULT_LANG = 'en';

    /**
     * Translates a string.
     * FIXME $lang is always by DEFAULT English, getLang by SESSION?
     */
    static public function t($string, $args = array(), $lang = null) {
        $lang = $lang ? $lang : getLang();

        $translatedString = $string;

        if (self::_getMoFileLoaded($lang)) {
            $translatedString = gettext($string);
            if (SEND_MO_TRANSLATION_NOT_FOUND_EMAIL && $translatedString === $string) {
                self::_sendTranslationNotFoundEmail($string);
            }
        } else {
            if ($lang != self::DRUFONY_DEFAULT_LANG && !empty($string)) {
                $translatedString = self::_t($lang, $string);
            }
        }

        if ($args) {
            $translatedString = strtr($translatedString, $args);
        }

        return $translatedString;
    }

    static private function _getMoFileLoaded($langCode) {
        $useMoFile = false;
        if (USE_MO_FILE_TRANSLATIONS == 1 ) {
            putenv('LANG=' . $langCode . '.UTF8' );
            $localeToUse = setlocale(LC_ALL, $langCode . '.UTF8' );
            bindtextdomain(PROJECT_LOCALE_NAME, getcwd() . LOCALE_PATH);
            textdomain(PROJECT_LOCALE_NAME);
            if ($localeToUse) {
                $useMoFile = true;
            }
        }
        return $useMoFile;
    }

    /**
     * Sends an email when the translation was not found
     */
    static public function _sendTranslationNotFoundEmail($text) {
        $email      = DEFAULT_EMAIL_ADDRESS;
        $params     = array('%text' => $text);
        $subject    = t("Function t() called with the text '%text'", $params, self::DRUFONY_DEFAULT_LANG);
        $template   = 'email-tfunction.html.twig';

        $trace        = debug_backtrace(FALSE);
        $customParams = self::_getTranslationParams($text, $trace);

        Mailing::sendMail($email, $subject, $template, $customParams);
    }

    static private function _getTranslationParams($text, $trace) {
        $customParams           = array_shift($trace);
        $customParams['text']   = $text;
        $customParams['lang']   = self::DRUFONY_DEFAULT_LANG;

        if (!empty($customParams['args']) && is_array($customParams['args']) ) {
            $customParams_args = array();
            foreach($customParams['args'] as $arg) {
              if (is_string($arg)) {
                  $customParams_args[] = $arg;
              }
              else {
                  $customParams_args[] = gettype($arg);
              }
          }
            $customParams['args'] = $customParams_args;
        }

        return $customParams;
    }

    /**
     * Translate string from code (usually English) to $lang.
     */
    static private function _t($lang, $string)
    {
        $sql = 'SELECT lt.translation
                FROM locales_source ls LEFT JOIN locales_target lt
                    ON (ls.lid = lt.lid AND lt.language = ?)
                WHERE ls.source = ?';

        $translatedString = db_fetchColumn($sql, array($lang, $string));
        if (empty($translatedString)) {
            // Source not found, so no translation available.
            if (!is_null($translatedString)) {
                self::_t_source_add($string);
            }
            $translatedString = $string;
        }

        return $translatedString;
    }

    /**
     * Adds a string to the source table in Drupal.
     */
    static private function _t_source_add($source) {
        $data = array(
                'location'  => 'drufony',
                'textgroup' => 'drufony',
                'source'    => $source,
                'context'   => '',
                'version'   => 'none',
        );
        db_insert('locales_source', $data);
    }

    /**
     * Get all languages enabled for this site.
     */
    static public function getAllLanguages() {
        $sql = 'SELECT language, name FROM languages WHERE enabled = 1';
        $result = db_executeQuery($sql);
        $languages = array();
        while ($row = $result->fetch()) {
            $languages[$row['language']] = t($row['name']);
        }

        return $languages;
    }

    /**
     * Search translatable string
     * @param string, strint to search
     * @param language origin language, if null search in all languages
     * @param textgroup of the string, if null search in all textgroups
     */
    static public function searchTranslatable($string = null, $language = null) {
        $params       = array();
        $strings      = array();
        $searchString = Drupal::filter_xss($string);

        $sql = "SELECT locales_source.lid, location, language, source, translation
            FROM locales_source LEFT JOIN locales_target ON locales_source.lid = locales_target.lid
            WHERE (locales_source.source LIKE (?) OR
            locales_target.translation LIKE (?))";
        $params = array("%${string}%", "%${string}%");

        if (!empty($language)) {
            $sql .= ' AND locales_target.language = ?';
            $params[] = $language;
        }

        $results = db_executeQuery($sql, $params);

        while ($row = $results->fetch()) {
            if (!isset($strings[$row['lid']]) || !is_array($strings[$row['lid']])) {
                $strings[$row['lid']] = $row;
                $strings[$row['lid']]['languages']    = array();
                $strings[$row['lid']]['translations'] = array();
            }

            $strings[$row['lid']]['languages'][$row['language']]    = $row['language'];
            $strings[$row['lid']]['translations'][$row['language']] = $row['translation'];
        }

        return $strings;
    }

    /**
     * Get source string by lid
     */
    static public function getSourceStringByLid($lid) {
        $sql = "SELECT * FROM locales_source WHERE lid = ?";
        return db_executeQuery($sql, array($lid))->fetch();
    }

    static public function saveTranslation($lid, $string, $lang) {
        $record = array(
            'lid' => $lid,
            'translation' => $string,
            'language'    => $lang,
            'plid'        => 0,  //Parent lid (lid of the previous string in the plural chain)
            'plural'      => 0,  //Plural index number in case of plural strings
            'i18n_status' => 0,  //Mark to 1 to indicate if the translation needs to be updated
        );
        $sql = "SELECT COUNT(1) FROM locales_target WHERE language = ? AND lid = ?";
        $result = db_fetchColumn($sql, array($lang, $lid));
        if ($result) {
            if (!db_update('locales_target', $record, array('lid' => $lid, 'language' => $lang))) {
                l(ERROR, 'Error updating a translate with lid ' . $lid . ' and language ' . $lang);
            }
        }
        else {
            if (!db_insert('locales_target', $record)) {
                l(ERROR, 'Error insert a translate with lid ' . $lid . ' and language ' . $lang);
            }
        }
    }

    static public function deleteTranslations($lid) {
        $sql = "DELETE FROM locales_target WHERE lid = ?";
        db_executeQuery($sql, array($lid));
    }

    static public function deleteSourceString($lid) {
        $sql = "DELETE FROM locales_source WHERE lid = ?";
        db_executeQuery($sql, array($lid));
    }

    /**
     * Get percentage of translated interface strings
     * @param lang
     * @return float, percentage of translated string for given language
     */
    static public function getTranslateInterfacePercentage($lang = self::DRUFONY_DEFAULT_LANG) {
        $totalStrings = self::getDefinedInterfaceStrings();
        $translated = self::getTranslatedInterfaceStrings($lang);

        if ($totalStrings != 0) {
            $translatedPercentage = ($translated * 100) / $totalStrings;
        }
        else {
            $translatedPercentage = 0;
        }

        return $translatedPercentage;
    }

    /**
     * Get total of interface strings
     * @return int, total interface strings
     */
    static private function getDefinedInterfaceStrings() {
        $sql = "SELECT COUNT(1) FROM locales_source";

        return db_fetchColumn($sql);
    }

    /**
     * Get number of translated interface strings for given language, if
     * language is default_lang, the number will be total of strings
     * @param lang
     * @return int, number of translated interface strings
     */
    static private function getTranslatedInterfaceStrings($lang = self::DRUFONY_DEFAULT_LANG) {
        if ($lang != self::DRUFONY_DEFAULT_LANG) {
            $sql = "SELECT COUNT(1) as count, lt.lid
                    FROM locales_source ls
                    INNER JOIN locales_target lt ON lt.lid = ls.lid
                    WHERE lt.language = ?";

            $count = db_fetchColumn($sql, array($lang));
        }
        else {
            $count = self::getDefinedInterfaceStrings();
        }

        return $count;
    }
}
