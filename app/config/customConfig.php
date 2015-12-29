<?php
//Nombre de la página web
define('PROJECT_NAME', 'Drufony');

//Sesión y cookies
define('COOKIE_DOMAIN', 'www.macson.es'); //dominio de las cookies
define('LANGUAGE_COOKIE', 'drufonylang'); //nombre de la cookie de idioma
define('EXPIRY_TIME_COOKIE', 1); //tiempo de vida predeterminado de las cookies

//FIXME: You need to set your own salt
define('DRUFONY_SALT', 'NvwKmbfRaf4tG1ObazTmHfLb7zSgAY0jzZgsjyABFhbX9vj8MLiZaq9q'); //Se obtiene ejecutando base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);

// Cookie policy
// Values:
//    - 1: desactivado
//    - 2: modo aceptacion si usuario continua navegando
//    - 3: modo aceptacion si usuario hace click en boton aceptar
define('COOKIE_MODE', 3);

// IDIOMAS
define('DEFAULT_LANGUAGE','es');
define('VALID_LANGUAGES', serialize (array('es' => 'Español','en' => 'English')));
// Fechas
define('DEFAULT_PUBLICATION_DATE_FORMAT','Y-m-d H:i:s');

// TRADUCCIONES
//Activa/Desactiva transaciones desde MO files
define('USE_MO_FILE_TRANSLATIONS', 1);
define('PROJECT_LOCALE_NAME', 'Macson');
# locale directory must exist as a child of Symfony root path
define('LOCALE_PATH', '/../locale');
define('SEND_MO_TRANSLATION_NOT_FOUND_EMAIL', 1);

//REGISTER AND LOGIN
//Only accepts path names defined in routing.yml
define('PATH_AFTER_REGISTRATION', 'drufony_home_url');
define('PATH_AFTER_LOGIN', 'drufony_home_url');

//FACEBOOK API
//FIXME: You need to set your own keys
define('FACEBOOK_PRIVATE_KEY','XXXXXXXXXXXXXXXXXXXXXX');
define('FACEBOOK_API_KEY','XXXXXXXXXXXXXXXXXX');

// CONTENTS
define('CONTENT_HOME_DEFAULT_URL','index');//Url a home. Se usa en los breadcrumbs.
define('ITEMS_PER_PAGE', 20);//número de elementos por defecto a mostrar en cada listado

// COMMENTS
define('COMMENT_ALL', 0);
define('COMMENT_ONLY_APPROVED', 1);
define('COMMENT_SHOW_DEFAULT_STATE', COMMENT_ONLY_APPROVED);
define('COMMENT_IS_NOT_A_REPLY', 0);
define('COMMENT_SUBJECT_MAX_LENGHT', 64);
define('COMMENT_DEFAULT_LANGUAGE', 'und');
define('COMMENT_DEFAULT_FORMAT', 'filtered_html');
define('COMMENT_DEFAULT_DELETED', 0);
define('COMMENT_BUNDLE', 'comment_node_group');
define('COMMENT_ENTITY_TYPE', 'comment');

/** Para definir los modos de comentarios, hemos de realizar la suma de los estados 
 *  activados.
 *  Los valores son:
 *      CLOSED = 1
 *      OPENED = 2
 *      PREMODERATED = 4
 *      POSTMODERATED = 8
 */
define('COMMENT_STATUS_MODE', 15);
define('COMMENT_DEFAULT_STATUS', 2);

// GOOGLE ANALYTICS
// define('GOOGLE_ANALYTIC_CODE','THECODEHERE'); //descomentar en caso de usarse

//CORREOS ELECTRÓNICOS
define ('DEFAULT_EMAIL_ADDRESS','gfuset@macson.es');
define ('HR_EMAIL_ADDRESS','rrhh@macson.es');
define ('COMUNICACION_EMAIL_ADDRESS','comunicacion@macson.es');
define ('UNIFORMS_EMAIL_ADDRESS','uniformes@macson.es');
define ('FRANCHISES_EMAIL_ADDRESS','franquicias@macson.es');
define ('GENCAT_EMAIL_ADDRESS','comunicacion@macson.es');
//email base template
define ('DRUFONY_EMAIL_TEMPLATENAME','email-base-template.html.twig');
// USER CONFIGURATION
define('USER_DEFAULT_TIME_ZONE','Europe/Madrid');
//prefijo que se incluirá para su inserción url_alias.
define('USER_URL_ALIAS_PREFIX','user/');
//Ruta en routing.yml que define el enlace para validad email (donde pinchará el usuario).
define('USER_VALIDATE_EMAIL_ROUTER_NAME','drufony_user_validate_email');
//Ruta en routing.yml que define el enlace para recuperar la clave (donde pinchará el usuario).
define('USER_PASS_RECOVERY_ROUTER_NAME','drufony_user_forgot_pass');
// Sets the default user status when created:
// Values:
//   - 0: blocked
//   - 1: active
//
// Default: 1
define('USER_DEFAULT_STATUS', 1);

//Formats are defined on the IANA website
define('ALLOWED_IMAGE_FORMATS', serialize(array('image/png', 'image/jpg', 'image/jpeg', 'image/gif')));

//Example 5M for 5MB, 200k for 200KB
define('MAX_IMAGE_FILE_SIZE', '20M');
define('MAX_ATTACHMENT_FILE_SIZE', '20M');

define('DEFAULT_COMMENT_STATUS', 1);

//For fields visibility form definition
define('FIELDS_ITEM_HIDE', serialize(array()));
define('FIELDS_SECTION_HIDE', serialize(array()));
define('FIELDS_PAGE_HIDE', serialize(array()));
define('FIELDS_PRODUCT_HIDE', serialize(array('priceVatPercentage', 'currency')));

//Required fields
define('FIELDS_REQUIRED_ITEM', serialize(array('title')));
define('FIELDS_REQUIRED_SECTION', serialize(array('title')));
define('FIELDS_REQUIRED_PAGE', serialize(array('title')));
define('FIELDS_REQUIRED_PRODUCT', serialize(array('title', 'stock')));

//Files
define('FILES_BASE', 'bundles/drufonycore/files/');
define('FILE_PREPATH_DEPTH', 2); //Directory nesting level for node files
define('SUBPATH_IMAGES', 'images/');
define('SUBPATH_PROFILE_IMAGES', 'profiles/images/');
define('SUBPATH_ATTACHMENTS', 'attachments/');
define('SUBPATH_TEMPORARY_VIDEOS', 'videos/');
define('SUBPATH_CSV', 'csv/');
define('SUBPATH_CONTACT_ATTACHMENTS', 'contact-attachments');

//Images effects
define('IMAGE_EFFECTS', serialize(array(
    'style200x200' => array(
        'effects' => array(
            'scaleAndCrop' => array(
                'info' => array('width' => 200, 'height' => 200)
    )))
)));


define('DEFAULT_CURRENCY', 'EUR');
define('DEFAULT_CURRENCY_SYMBOL', '€');
define('DEFAULT_SHIPPING_FEE_ID', 0);

define('SHIPPING_FEE_GENERAL', 'general');
define('SHIPPING_FEE_BY_COUNTRY', 'country');
define('SHIPPING_FEE_ENABLED', SHIPPING_FEE_BY_COUNTRY);
define('SHIPPING_FEE_DEFAULT_PRICE', 3);


//TPV
define('TPV_STRIPE_TYPE', 1);
define('TPV_SERMEPA_TYPE', 2);
define('TPV_PAYPAL_TYPE', 3);
define('TPV_STRIPE', 'Stripe');
define('TPV_SERMEPA', 'Visa/American Express/Mastercard');
define('TPV_PAYPAL', 'Paypal');
define('TPV_GATEWAY', TPV_STRIPE);
//Adds TPVs enabled to be used
define('TPV_ENABLED', serialize(array(TPV_SERMEPA, TPV_PAYPAL)));
define ('COMMERCE_MANAGEMENT_EMAIL', "pedidos@macson.es");
//SERMEPA
//Replace this URL in production mode (test: https://sis-t.redsys.es:25443/sis/realizarPago, production: https://sis.redsys.es/sis/realizarPago)
define('SERMEPA_URL', 'https://sis.redsys.es/sis/realizarPago');
define('SERMEPA_MERCHANT_CODE', '327234068');
define('SERMEPA_MERCHANT_KEY', 'vQzswfl8rwefUO/bTvaHO8G0ulKVv0cT');
//define('SERMEPA_MERCHANT_KEY', 'erjusrtjus45usestruh');
//define('SERMEPA_MERCHANT_KEY', 'qwertyasdf0123456789');

define('SERMEPA_CURRENCY_EQUIVALENCE', serialize(array('EUR' => '978', 'default' => '978')));
define('SERMEPA_MERCHANT_TERMINAL', '1');
define('SERMEPA_MERCHANT_NAME', 'Pruebas');
define('SERMEPA_MERCHANT_TRANSACTION_TYPE', 0);
define('SERMEPA_LANGUAGE_EQUIVALENCE', serialize(array('es' => '001', 'en' => '002', 'default' => '002')));
define('SERMEPA_HASH_ALGORITHM', 'sha256');

//PAYPAL
define('PAYPAL_CLIENT_ID', 'ARyXqxC39FAUvYnAYNL1O1VxJdRwAgGzICUo-l1DOMqfEgyLN_egRP4o4F1a');
define('PAYPAL_CLIENT_SECRET', 'EAatNBB2fueZg-J3msDAMNO7C6GPNZ0p1opr58ioB8N7_rlPcWfIZeRisimM');
//Set PAYPAL_MODE to sandbox for tests and live for production
define('PAYPAL_MODE', 'live');


define('SHIFT_TICKET_NUMBER',100);
define('SHIFT_INVOICE_NUMBER_EXPORT',100);
define('SHIFT_INVOICE_NUMBER',100);



//STRIPE
//FIXME: You need to set your own keys
define('STRIPE_PRIVATE_KEY',            'sk_test_XXXXXXXXXXXXXXXXXXXXXXXX');
define('STRIPE_PUBLIC_KEY',             'pk_test_XXXXXXXXXXXXXXXXXXXXXXXX');
define('STRIPE_CARD_DECLINED_ERROR',      1);
define('STRIPE_CUSTOMER_NOT_FOUND_ERROR', 2);
define('STRIPE_NETWORK_ERROR',            3);
define('STRIPE_API_ERROR',                4);
define('STRIPE_INVALID_KEY_ERROR',        5);
define('STRIPE_INVALID_REQUEST_ERROR',    6);
define('STRIPE_GENERIC_ERROR',            7);
define('MAIL_ON_STRIPE_CARD_USER_ERROR',  1);
define('STRIPE_DEBUG_MAIL', 'tests+stripe@example.com');
define('STRIPE_ERROR_ADDRESS', STRIPE_DEBUG_MAIL);
//define('STRIPE_ERROR_ADDRESS', DEFAULT_EMAIL_ADDRESS);

//STRIPE ERRORS
define('STRIPE_CARD_DECLINED', 'card_declined');
define('STRIPE_INCORRECT_CVC', 'incorrect_cvc');
define('STRIPE_EXPIRED_CARD', 'expired_card');
define('STRIPE_PROCESSING_ERROR', 'processing_error');

#Log levels
define('ERROR', 'err');
define('WARNING', 'warn');
define('INFO', 'info');
define('DEBUG', 'debug');

//Videos
//Default provider
//FIXME: You need to set your own keys
define('VIDEO_PROVIDER',        'Vimeo');
define('VIMEO_CONSUMER_KEY',    'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
define('VIMEO_CONSUMER_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
define('VIMEO_ACCESS_TOKEN',    'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
define('VIMEO_ACCESS_SECRET',   'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');

//Order status
define('ORDER_STATUS_NEW', 1);
define('ORDER_STATUS_ISSUE', 2);
define('ORDER_STATUS_PREPARATION', 3);
define('ORDER_STATUS_READY_TO_SEND', 4);
define('ORDER_STATUS_SHIPPED', 5);
define('ORDER_STATUS_ISSUE_ON_RECEPTION', 6);
define('ORDER_STATUS_RECEPTION_CONFIRMED', 7);
define('ORDER_STATUS_CUSTOMER_SEND_BACK', 8);
define('ORDER_STATUS_RETURNED', 9);

//Payment status
define('PAYMENT_STATUS_PENDING', 1);
define('PAYMENT_STATUS_PAID', 2);
define('PAYMENT_STATUS_REFUND', 3);
define('PAYMENT_STATUS_DISPUTE', 4);
define('PAYMENT_STATUS_CHARGEBACK', 5);

//Export Zone
define('EXPORT_ZONE_EU', 1);
define('EXPORT_ZONE_NO_EU', 10);

//Menu types
define('MENU_TYPE_HEADER', '1');
define('MENU_TYPE_FOOTER', '2');
define('MENU_TYPE_OPTIONS', serialize(array(MENU_TYPE_HEADER => 'Header', MENU_TYPE_FOOTER => 'Footer')));

//Coupons constants
define('COUPON_UNIQUE', '1');
define('COUPON_MULTIUSER', '2');
define('COUPON_TYPE_OPTIONS', serialize(array(COUPON_UNIQUE => 'Unique', COUPON_MULTIUSER => 'Multiuser')));
define('COUPON_ENABLED', '1');
define('COUPON_DISABLED', '0');
define('COUPON_EXPIRED', '2');
define('COUPON_VALID', '3');
define('COUPON_USED', '4');
define('COUPON_NONEXISTENT', '5');
define('COUPON_NONACTIVE', '6');

// Mandrill
//FIXME: You need to set your own keys
define('MANDRILL_SMTP_ADDRESS', 'smtp.mandrillapp.com');
define('MANDRILL_SMTP_PORT', 587);
define('MANDRILL_SMTP_USERNAME', 'tests@example.com');
define('MANDRILL_SMTP_PASSWORD', 'XXXXXXXXXXXXXXXXXXXXXX');

//Batch
define('BATCH_BLOCK_SIZE', 50);

//Url redirect validity in days
define('URL_REDIRECT_VALIDITY', 10);

//Contents that can have geo position
define('CONTENTS_GEO_POSITION', serialize(array('item', 'page')));

//Types of VAT
defined('DEFAULT_VAT') or define('DEFAULT_VAT', 21);
define('VAT_TYPES', serialize(array('1' => 0, '2' => 0.10, '3' => 0.21)));
define('DEFAULT_VAT_TYPE', '3');

//Free tags, vocabulary name
define('FREE_TAGS', 'freeTags');
