<?php

namespace Macson\ProjectBundle\Twig\Extension;

use Twig_Extension;
use Twig_Filter_Method;

class MacsonExtension extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            'toUrl' => new Twig_Filter_Method($this, 'toUrl'),
        );
    }

    public function toUrl($text)
    {
      $url = str_replace('/','_',$text);
      $url = str_replace(' ','_',$url);
      $url = str_replace('á','a',$url);
      $url = str_replace('é','e',$url);
      $url = str_replace('í','i',$url);
      $url = str_replace('ó','o',$url);
      $url = str_replace('ú','u',$url);
      $url = str_replace('ñ','n',$url);
      $url = strtolower($url);

 

      return $url;
    }

    public function getName()
    {
        return 'macson_extension';
    }
}
