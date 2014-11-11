<?php
/**
 * Implementation of a DrufonyImage class. It's useful for those
 * forms which needs to get attached images.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Symfony\Component\HttpFoundation\File\File;

/**
 * Implementation of an Image class. Useful for those forms which needs to get attached images.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class DrufonyImage extends File
{
    protected $iid;
    protected $fid;
    protected $link;
    protected $title;
    protected $alt;
    protected $weight;
    protected $description;

    function __construct($path, $iid, $fid, $link=NULL, $title=NULL, $alt=NULL, $weight=NULL, $description=NULL) {
        parent::__construct($path);
        $this->iid         = $iid;
        $this->fid         = $fid;
        $this->link        = $link;
        $this->title       = $title;
        $this->alt         = $alt;
        $this->weight      = $weight;
        $this->description = $description;
    }

    public function getIid()         { return $this->iid; }
    public function getFid()         { return $this->fid; }
    public function getLink()        { return $this->link; }
    public function getTitle()       { return $this->title; }
    public function getAlt()         { return $this->alt; }
    public function getWeight()      { return $this->weight; }
    public function getDescription() { return $this->description; }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }

}
