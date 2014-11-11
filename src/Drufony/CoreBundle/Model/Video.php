<?php
/**
 * Implements Singleton pattern for managing videos.
 */

namespace Drufony\CoreBundle\Model;

/**
 * Implements Singleton pattern for managing videos. It allows to
 * handle several providers in a generic way. Main class which will
 * be used by specific video providers. This class allows to create
 * a generic instance for handling videos.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Video {
    public static $allowedVideoMimeTypes = array('video/mpeg', 'video/mp4', 'video/ogg', 'video/quicktime', 'video/webm', 'video/x-matroska', 'video/x-ms-wmv', 'video/x-flv', 'video/x-msvideo', 'video/x-ms-asf', 'application/x-shockwave-flash');
    const MAX_VIDEO_FILE_SIZE = '100M';

    static function getInstance ($provider = VIDEO_PROVIDER) {
        $provider = 'Drufony\\CoreBundle\\Model\\' . $provider;
        return new $provider();
    }
}
