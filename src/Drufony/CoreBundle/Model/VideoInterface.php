<?php

namespace Drufony\CoreBundle\Model;

interface VideoInterface
{
    public function upload($file, $title, $description);

    public function get($videoToken);
}
