<?php
/**
 * It defines the Geo layer, which will be used to get countries,
 * regions and provinces related to them. It's based on efforts from
 * MaxMind project.
 */

namespace Drufony\CoreBundle\Model;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
/**
 * Retrieves countries, regions and provinces from database.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Geo
{
    /**
     * Retrieves all the countries in database.
     *
     * @return array; all contries in database
     */
    public static function getCountries() {
        $sql = 'SELECT * FROM country';
        $stmt = db_executeQuery($sql);

        $items = $stmt->fetchAll();

        return $items;
    }

    /**
     * Retrieves all the country names.
     *
     * @return array; associative array by country id with all the names
     */
    public static function getCountriesName() {
        $sql = 'SELECT id, name FROM country';
        $stmt = db_executeQuery($sql);

        $items = array();
        while ($row = $stmt->fetch()) {
            $items[$row['id']] = $row['name'];
        }

        return $items;
    }

    /**
     * Retrieves a country name.
     *
     * @param int $countryId; country id in database
     *
     * @return string; country name found or false if not
     */
    public static function getCountryNamebyId($countryId) {
        $sql = 'SELECT name FROM country WHERE id = ?';

        $countryName = db_fetchColumn($sql, array($countryId));

        return $countryName;
    }

    /**
     * Retrieves a country code.
     *
     * @param int $countryId; country id in database
     *
     * @return string; country codefound or false if not
     */
    public static function getCountryCodebyId($countryId) {
        $sql = 'SELECT code FROM country WHERE id = ?';

        $countryName = db_fetchColumn($sql, array($countryId));

        return $countryName;
    }

    /**
     * Retrieves a country by its own code.
     *
     * @param mixed $code
     *
     * @return array
     */
    public static function getCountryByCode($code) {
        $sql = 'SELECT * FROM country WHERE code = ?';
        $stmt = db_fetchAssoc($sql, array($code));

        return $stmt;
    }

    /**
     * Retrieves all the regions by a country.
     *
     * @param int $countryId; country id in database
     *
     * @return array; all the regions that belongs to that country
     */
    public static function getRegionsByCountry($countryId) {
        $sql = 'SELECT * FROM regions WHERE parentId = ?';
        $stmt = db_executeQuery($sql, array($countryId));

        $items = $stmt->fetchAll();

        return $items;
    }

    /**
     * Retrieves all the region names which belong to a country.
     *
     * @param int $countryId; country id in database
     *
     * @return array; associative array by region id with its name
     */
    public static function getRegionsNameByCountry($countryId) {
        $sql  = 'SELECT region.id, region.name ';
        $sql .= 'FROM region ';
        $sql .= 'INNER JOIN country ON region.parentId = country.id ';
        $sql .= 'WHERE country.id = ?';

        $stmt = db_executeQuery($sql, array($countryId));

        $items = array();
        while ($row = $stmt->fetch()) {
            $items[$row['id']] = $row['name'];
        }

        return $items;

    }

    /**
     * Retrieves all the regions by its own code.
     *
     * @param string $code; region code in database
     *
     * @return array; all the regions with that code
     */
    public static function getRegionsByCode($code) {
        $sql = 'SELECT * FROM region WHERE code = ?';
        $stmt = db_executeQuery($sql, array($code));

        $items = $stmt->fetchAll();

        return $items;
    }

    /**
     * Retrieves all the region provinces.
     *
     * @param int $regionId; region id in database
     *
     * @return array; all the provinces that belong to that region
     */
    public static function getProvincesByRegion($regionId) {
        $sql = 'SELECT * FROM province WHERE parentId = ?';
        $stmt = db_executeQuery($sql, array($regionId));

        $items = $stmt->fetchAll();

        return $items;
    }

    /**
     * Retrieves all the provinces by a country.
     *
     * @param int $countryId; country id in database
     *
     * @return array; all the provinces of that country
     */
    public static function getProvincesByCountry($countryId) {
        $sql  = 'SELECT province.* FROM province ';
        $sql .= 'INNER JOIN region ON region.id = province.parentId ';
        $sql .= 'INNER JOIN country ON country.id = region.parentId ';
        $sql .= 'WHERE country.id = ?';

        $queryResult = db_executeQuery($sql, array($countryId));

        $provinces = array();
        while($row = $queryResult->fetch()) {
            $provinces[$row['id']] = $row;
        }

        return $provinces;
    }

    /**
     * Retrieves all the province names by a country.
     *
     * @param int $countryId; country id in database
     *
     * @return array; associative array by province id containing name
     */
    public static function getProvincesNameByCountry($countryId) {
        $sql  = 'SELECT province.id, province.name FROM province ';
        $sql .= 'INNER JOIN region ON region.id = province.parentId ';
        $sql .= 'INNER JOIN country ON country.id = region.parentId ';
        $sql .= 'WHERE country.id = ? ORDER BY province.name';

        $queryResult = db_executeQuery($sql, array($countryId));

        $provinces = array();
        while($row = $queryResult->fetch()) {
            $provinces[$row['id']] = $row['name'];
        }

        return $provinces;
    }

    /**
     * Retrieves the province name.
     *
     * @param int $provinceId; province id in database
     *
     * @return string; province name
     */
    public static function getProvinceNamebyId($provinceId) {
        $sql = 'SELECT name FROM province WHERE id = ?';

        $provinceName = db_fetchColumn($sql, array($provinceId));

        return $provinceName;
    }

    /**
     * Retrieves a province by its own code.
     *
     * @param string $code; province code
     *
     * @return array; province found with that code
     */
    public static function getProvincesByCode($code) {
        $sql = 'SELECT * FROM province WHERE code = ?';
        $stmt = db_executeQuery($sql, array($code));

        $items = $stmt->fetchAll();

        return $items;
    }

    /**
     * Retrieves the name of a province or region.
     *
     * Method necessary to get province/region name in checkout progress,
     * and we don't know if we have stored and province or region
     *
     * @param int $territoryId
     * @param int $countryId
     * @return string
     */
    public static function getProvinceRegionNameById($territoryId, $countryId) {
        $sql  = 'SELECT province.name FROM province ';
        $sql .= 'INNER JOIN region ON region.id = province.parentId ';
        $sql .= 'INNER JOIN country ON country.id = region.parentId ';
        $sql .= 'WHERE province.id = ? AND country.id = ?';

        $territoryName = db_fetchColumn($sql, array($territoryId, $countryId));

        if(!$territoryName) {
            $sql  = 'SELECT region.name ';
            $sql .= 'FROM region ';
            $sql .= 'INNER JOIN country ON country.id = region.parentId ';
            $sql .= 'WHERE region.id= ? AND country.id = ?';
            $territoryName = db_fetchColumn($sql, array($territoryId, $countryId));
        }

        return $territoryName;
    }

    /**
     * Checks if a city exists in a country.
     *
     * @param string $cityName
     * @param int $countryId
     *
     * @return boolean; true if exists, false otherwise
     */
    public static function cityExists($cityName, $countryId) {
        $sql = 'SELECT COUNT(1) FROM city WHERE name = ? AND countryId = ?';
        $count = db_fetchColumn($sql, array($cityName, $countryId));

        $exists = FALSE;
        if ($count == 1) {
            $exists = TRUE;
        }

        return $exists;
    }

    /**
     * Check if a postalCode exists.
     *
     * @param int $provinceId; province id in database
     *
     * @return boolean; true if exists, false otherwise
     */
    public static function postalCodeExists($provinceId) {
        $sql = 'SELECT postalcode FROM province WHERE id = ?';
        $postalCode = db_fetchColumn($sql, array($provinceId));

        $exists = FALSE;
        if ($postalCode) {
            $exists = TRUE;
        }

        return $exists;
    }

    static public function  getUserLanguage($user = NULL)
    {
        $lang = NULL;

        // If the user exists check if it has a language stored in the DB
        if (is_object($user) && !$user->isAnonymous()) {
            $lang = $user->getLanguage();
        }

        // If lang was not received
        if (!$lang) {
            // Check if the user has a language Cookie set.
            if (!$lang = self::getLangCookie()) {
                //If the user has not a cookie, look at browser headers
                $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
                $valid_languages = array_keys(unserialize(VALID_LANGUAGES));
                    //If there wasn't a matching lang, look at the users ip and get a language based on its location
                    //$geo_array = geoip_record_by_name($_SERVER['REMOTE_ADDR']); // FIX  ME: Is there any other way?.
                    $geo_array = geoip_record_by_name('bbc.co.uk'); // FIX  ME: Is there any other way?.                
                    $country_code = $geo_array['country_code'];
                    foreach($valid_languages as $language) {
                        $language_code = self::country_code_to_locale($country_code, $language);

                        if($language_code != NULL) {
                            break;
                        }
                    }
                                        $lang_array = explode('_', $language_code);
                    $lang = $lang_array[0];
                    //It it does not match any valid language, return the preferred language
                    if(!in_array($lang, $valid_languages)) {
                        $lang = PREFERRED_LANGUAGE;
                    }
                
            }
        }
        $geo_array = geoip_record_by_name('bbc.co.uk'); // FIXME: Is there any other way?.
        $country_code = $geo_array['country_code'];
        locale_set_default($lang . '_' . $country_code); //FIXME ... get country code based on language
        return $lang;
    }

    /**
     * Retrieves the langauge cookie
     * @return string | null depending on if the cookie exists.
     */
    static public function getLangCookie()
    {
        $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        $cookies = $request->cookies;
        $cookie = NULL;
        if ($cookies->has(LANGUAGE_COOKIE))
        {
            $cookie = $cookies->get(LANGUAGE_COOKIE);
        }
        return $cookie;
    }

    /**
     * Sets the language cookie with the value specified as a parameter
     * @param string $lang - the language to set
     */
    static public function setLangCookie($lang)
    {
        $expire_time = 0;
        $path = '/';
        $domain = COOKIE_DOMAIN;
        $cookie = new Cookie('lang', $lang, $expire_time, $path, $domain, false, false);
        $response = new Response();
        $response->headers->setCookie($cookie);
        $response->sendHeaders();
    }

    /**
     * Returns a locale from a country code that is provided.
     *
     * @param $country_code  ISO 3166-2-alpha 2 country code
     * @param $language_code ISO 639-1-alpha 2 language code
     * @returns  a locale, formatted like en_US, or null if not found
     **/
    static public function country_code_to_locale($country_code, $language_code = DEFAULT_LANGUAGE)
    {
        // Locale list taken from:
        // http://stackoverflow.com/questions/3191664/
        // list-of-all-locales-and-their-short-codes
        $locales = array(
                'af-ZA', 'am-ET', 'ar-AE', 'ar-BH', 'ar-DZ',
                'ar-EG', 'ar-IQ', 'ar-JO', 'ar-KW', 'ar-LB',
                'ar-LY', 'ar-MA', 'arn-CL', 'ar-OM', 'ar-QA',
                'ar-SA', 'ar-SY', 'ar-TN', 'ar-YE', 'as-IN',
                'az-Cyrl-AZ', 'az-Latn-AZ', 'ba-RU', 'be-BY',
                'bg-BG', 'bn-BD', 'bn-IN', 'bo-CN', 'br-FR',
                'bs-Cyrl-BA', 'bs-Latn-BA', 'ca-ES', 'co-FR',
                'cs-CZ', 'cy-GB', 'da-DK', 'de-AT', 'de-CH',
                'de-DE', 'de-LI', 'de-LU', 'dsb-DE', 'dv-MV',
                'el-GR', 'en-029', 'en-AU', 'en-BZ', 'en-CA',
                'en-GB', 'en-IE', 'en-IN', 'en-JM', 'en-MY',
                'en-NZ', 'en-PH', 'en-SG', 'en-TT', 'en-US',
                'en-ZA', 'en-ZW', 'es-AR', 'es-BO', 'es-CL',
                'es-CO', 'es-CR', 'es-DO', 'es-EC', 'es-ES',
                'es-GT', 'es-HN', 'es-MX', 'es-NI', 'es-PA',
                'es-PE', 'es-PR', 'es-PY', 'es-SV', 'es-US',
                'es-UY', 'es-VE', 'et-EE', 'eu-ES', 'fa-IR',
                'fi-FI', 'fil-PH', 'fo-FO', 'fr-BE', 'fr-CA',
                'fr-CH', 'fr-FR', 'fr-LU', 'fr-MC', 'fy-NL',
                'ga-IE', 'gd-GB', 'gl-ES', 'gsw-FR', 'gu-IN',
                'ha-Latn-NG', 'he-IL', 'hi-IN', 'hr-BA', 'hr-HR',
                'hsb-DE', 'hu-HU', 'hy-AM', 'id-ID', 'ig-NG',
                'ii-CN', 'is-IS', 'it-CH', 'it-IT', 'iu-Cans-CA',
                'iu-Latn-CA', 'ja-JP', 'ka-GE', 'kk-KZ', 'kl-GL',
                'km-KH', 'kn-IN', 'kok-IN', 'ko-KR', 'ky-KG',
                'lb-LU', 'lo-LA', 'lt-LT', 'lv-LV', 'mi-NZ',
                'mk-MK', 'ml-IN', 'mn-MN', 'mn-Mong-CN', 'moh-CA',
                'mr-IN', 'ms-BN', 'ms-MY', 'mt-MT', 'nb-NO',
                'ne-NP', 'nl-BE', 'nl-NL', 'nn-NO', 'nso-ZA',
                'oc-FR', 'or-IN', 'pa-IN', 'pl-PL', 'prs-AF',
                'ps-AF', 'pt-BR', 'pt-PT', 'qut-GT', 'quz-BO',
                'quz-EC', 'quz-PE', 'rm-CH', 'ro-RO', 'ru-RU',
                'rw-RW', 'sah-RU', 'sa-IN', 'se-FI', 'se-NO',
                'se-SE', 'si-LK', 'sk-SK', 'sl-SI', 'sma-NO',
                'sma-SE', 'smj-NO', 'smj-SE', 'smn-FI', 'sms-FI',
                'sq-AL', 'sr-Cyrl-BA', 'sr-Cyrl-CS', 'sr-Cyrl-ME',
                'sr-Cyrl-RS', 'sr-Latn-BA', 'sr-Latn-CS', 'sr-Latn-ME',
                'sr-Latn-RS', 'sv-FI', 'sv-SE', 'sw-KE', 'syr-SY',
                'ta-IN', 'te-IN', 'tg-Cyrl-TJ', 'th-TH', 'tk-TM', 'tn-ZA',
                'tr-TR', 'tt-RU', 'tzm-Latn-DZ', 'ug-CN', 'uk-UA', 'ur-PK',
                'uz-Cyrl-UZ', 'uz-Latn-UZ', 'vi-VN', 'wo-SN', 'xh-ZA', 'yo-NG',
                'zh-CN', 'zh-HK', 'zh-MO', 'zh-SG', 'zh-TW', 'zu-ZA',
        );

        foreach ($locales as $locale)
        {
            $locale_region   = locale_get_region($locale);
            $locale_language = locale_get_primary_language($locale);
            $locale_array = array(
                'language' => $locale_language,
                'region'   => $locale_region,
            );

            if (strtoupper($country_code) == $locale_region
                && $language_code == '')
            {
                return locale_compose($locale_array);
            }
            else if (strtoupper($country_code) == $locale_region
                     && strtolower($language_code) == $locale_language)
            {
                return locale_compose($locale_array);
            }
        }

        return null;
    }


}
