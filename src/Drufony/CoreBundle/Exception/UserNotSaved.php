<?
/**
 * It defines UserNotSaved Exception Class, which only includes its constructor.
 */

namespace Drufony\CoreBundle\Exception;

/**
 * Exception for handling "User cannot be saved" events
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class UserNotSaved extends \Exception {
    /**
     * UserNotSaved constructor
     *
     * @param User $user
     * @return void
     */
    function __construct($user) {
        $message = 'User cannot be saved: '. serialize($user);
        parent::__construct($message);
        l(ERROR, $message);
    }
}
