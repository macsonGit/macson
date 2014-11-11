<?php

namespace Drufony\CoreBundle\Twig;

class LangExtension extends \Twig_Extension
{

    public function getFilters() {
        return array(
            new \Twig_SimpleFilter('t', array($this, 'langfilter')),
        );
    }

    public function langfilter($string, $args = NULL, $lang = NULL) {
        return t($string, $args, $lang);
    }

    public function getName() {
        return 'lang_extension';
    }
}
