<?php
/**
 * Implements Singleton pattern for managing tpvs.
 */

namespace Drufony\CoreBundle\Model;

/**
 * Implements Singleton pattern for managing tpvs. It allows to
 * handle several providers in a generic way. Main class which will
 * be used by specific tpv gateways. This class allows to create a generic
 * instance for handling payments.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class TPV {
    static $selectedTpv = TPV_GATEWAY;
    static $path = 'Drufony\CoreBundle\Model\\';

    static function getInstance() {
        $classPath = self::$path . self::$selectedTpv;
        return new $classPath();
    }
}

