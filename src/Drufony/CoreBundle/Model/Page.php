<?php
namespace Drufony\CoreBundle\Model;

class Page extends Content
{
    function __construct ($nid = null, $lang = DEFAULT_LANG) {
        $this->contentType = self::TYPE_PAGE;

        if ($nid) {
            $this->_loadNode($nid, $lang);
        }
    }

}

