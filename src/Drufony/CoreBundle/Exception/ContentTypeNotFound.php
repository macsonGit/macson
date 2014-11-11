<?
/**
 * It defines ContentTypeNotFound Exception Class, which only includes its constructor.
 */

namespace Drufony\CoreBundle\Exception;

/**
 * Exception for handling "Content type not found" events
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class ContentTypeNotFound extends \Exception {
    /**
     * ContentTypeNotFound constructor
     *
     * @return void
     */
    public function __construct() {
        $message = 'Content type field not found';
        parent::__construct($message);
        l(ERROR, $message);
    }
}
