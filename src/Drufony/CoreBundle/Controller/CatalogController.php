<?php

namespace Drufony\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Drufony\CoreBundle\Model\CommerceUtils;
use Drufony\CoreBundle\Model\ContentUtils;
use Drufony\CoreBundle\Model\Content;
use Drufony\CoreBundle\Model\Product;

defined('DEFAULT_VAT') or define('DEFAULT_VAT', 21);

class CatalogController extends DrufonyController
{

    public function productListAction(Request $request, $lang){
        $response = new Response();
        $products = ContentUtils::getPublished(Content::TYPE_PRODUCT, $lang);

        $itemsCount= CommerceUtils::getCartItemsCount();

        $response->setContent($this->renderView('DrufonyCoreBundle::productList.html.twig',
                                                array('lang' => $lang,
                                                'products' => $products,
                                                'itemsCount' => $itemsCount)));
        return $response;
    }

}
