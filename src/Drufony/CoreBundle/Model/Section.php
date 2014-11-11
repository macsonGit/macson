<?php
namespace Drufony\CoreBundle\Model;

class Section extends Content
{
    protected $showItems;
    protected $showSubsectionItems;
    protected $showSubsections;
    protected $subsectionGroups;
    protected $addContentEnable;
    protected $maxPerPage;
    protected $feedEnabled;
    protected $orderCriteria;
    protected $orderMode;

    function __construct($nid = null, $lang = DEFAULT_LANG) {
        $this->contentType = self::TYPE_SECTION;

        if ($nid) {
            $this->_loadNode($nid, $lang);
        }
    }

    public function getMaxPerPage()          { return $this->maxPerPage;}
    public function getOrderCriteria()       { return $this->orderCriteria; }
    public function getOrderMode()           { return $this->orderMode; }
    public function isShowSubsections()      { return $this->showSubsections; }
    public function isShowItems()            { return $this->showItems; }
    public function isSubsectionGroups()     { return $this->subsectionGroups; }
    public function isShowSubsectionItems()  { return $this->showSubsectionItems; }
    public function isFeedEnabled()          { return $this->feedEnabled; }
    public function isAddContentEnable()     { return $this->addContentEnable; }
}
