<?php

namespace Custom\ProjectBundle\Twig;

class PriceExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('decimals', array($this, 'decimalsFilter')),
        );
    }

    public function decimalsFilter($number)
    {
//        $decimals = round(($number-round($number, 0, PHP_ROUND_HALF_DOWN))*100,0);
        $decimals = round(($number-floor($number))*100);

        return $decimals;
    }

    public function getName()
    {
        return 'price_extension';
    }
}


