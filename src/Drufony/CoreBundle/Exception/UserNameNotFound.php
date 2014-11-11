<?
/**
 * It defines UserNameNotFound Exception Class, which only includes its constructor.
 */

namespace Drufony\CoreBundle\Exception;

/**
 * Exception for handling "UserName not found" events
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class UserNameNotFound extends \Exception {
    /**
     * UserNameNotFound constructor
     *
     * @param string $email
     * @return void
     */
    function __construct($email) {
        $message = 'Username does not exist: '. $email;
        parent::__construct($message);
        l(ERROR, $message);
    }
}
