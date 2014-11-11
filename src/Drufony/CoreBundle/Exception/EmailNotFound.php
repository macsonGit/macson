<?
/**
 * It defines EnailNotFound Exception Class, which only includes its constructor.
 */

namespace Drufony\CoreBundle\Exception;

/**
 * Exception for handling "Email not found" events
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class EmailNotFound extends \Exception {
    /**
     * EmailNotFound constructor
     *
     * @param string $email
     * @return void
     */
    public function __construct($email) {
        $message = 'User email does not exist: '. $email;
        parent::__construct($message);
        l(ERROR, $message);
    }
}
