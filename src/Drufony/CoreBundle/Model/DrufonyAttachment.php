<?php
/**
 * Implementation of a DrufonyAttachment class. It's useful for those
 * forms which needs to get attached files.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Symfony\Component\HttpFoundation\File\File;

/**
 * Implementation of an Attachment class. Useful for those forms which needs to get attached files.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class DrufonyAttachment extends File
{
    protected $aid;
    protected $fid;
    protected $title;
    protected $weight;
    protected $description;

    function __construct($path, $aid, $fid, $title=NULL, $weight=NULL, $description=NULL) {
        parent::__construct($path);
        $this->aid         = $aid;
        $this->fid         = $fid;
        $this->title       = $title;
        $this->weight      = $weight;
        $this->description = $description;
    }

    public function getAid()         { return $this->aid; }
    public function getFid()         { return $this->fid; }
    public function getTitle()       { return $this->title; }
    public function getWeight()      { return $this->weight; }
    public function getDescription() { return $this->description; }

}
