<?php

namespace Drufony\CoreBundle\Twig;
use Twig_Extension;
use Twig_Filter_Method;
class ConstantExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'isConstDefined' => new \Twig_Function_Method($this, 'isConstDefined'),
        );
    }

    /**
     * @param $name => string that contains the name of the constant
     * @return true if the constant is already defined in php
     * @return false if the constant is not defined
     */
    public function isConstDefined($name)
    {
        return defined($name);
    }

    public function getName()
    {
        return 'ConstantExtension';
    }
}
