<?php
/**
 * It defines TranslateNotFound Exception Class, which only includes its constructor.
 */

namespace Drufony\CoreBundle\Exception;

/**
 * Exception for handling "Translate not found" events
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class TranslateNotFound extends \Exception {
    /**
     * TranslateNotFound constructor
     *
     * @param String $lang
     * @return void
     */
    function __construct($lang) {
        $message = 'Translate cannot be found for language '. $lang;
        parent::__construct($message);
        l(ERROR, $message);
    }
}
