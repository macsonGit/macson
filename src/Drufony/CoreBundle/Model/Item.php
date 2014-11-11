<?php
namespace Drufony\CoreBundle\Model;

class Item extends Content
{
    protected $dateCalendar;
    protected $showInCalendar = false;

    function __construct($nid = null, $lang = DEFAULT_LANG) {
        $this->contentType = self::TYPE_ITEM;

        if ($nid) {
            $this->_loadNode($nid, $lang);
        }
    }

    public function getDateCalendar()          { return $this->dateCalendar;}
    public function isShowInCalendar()         { return $this->showInCalendar;}
}
