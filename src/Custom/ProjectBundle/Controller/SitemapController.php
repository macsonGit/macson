<?php


namespace Custom\ProjectBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Drufony\CoreBundle\Model\UserUtils;
use Drufony\CoreBundle\Model\Page;
use Drufony\CoreBundle\Entity\Comment;
use Drufony\CoreBundle\Controller\DrufonyController;
use Drufony\CoreBundle\Form\CommentFormType;
use Custom\ProjectBundle\Model\Vocabulary;
use Drufony\CoreBundle\Model\CommerceUtils;
use Drufony\CoreBundle\Model\ContentUtils;

class SitemapController extends DrufonyController
{
    public function sitemapAction() 
    {
        $em = $this->getDoctrine()->getManager();
        
        $urls = array();
        $hostname = $this->getRequest()->getSchemeAndHttpHost();

        // incluye url página inicial
        $urls[] = array(
            'loc' => self::stripAccents(urldecode($this->get('router')->generate('drufony_home_url'))), 
            'changefreq' => 'weekly', 
            'priority' => '1.0'
        );

	$languages=unserialize(VALID_LANGUAGES);



        // incluye urls multiidioma
        foreach($languages as $lang => $desc) {
            $urls[] = array(
                'loc' => self::stripAccents(urldecode($this->get('router')->generate('macson_stores', array(
                    'lang' => $lang
                )))), 
                'changefreq' => 'monthly', 
                'priority' => '0.3'
            );

            $urls[] = array(
                'loc' => self::stripAccents(urldecode($this->get('router')->generate('macson_size', array(
                    'lang' => $lang
                )))), 
                'changefreq' => 'monthly', 
                'priority' => '0.3'
            );

            $urls[] = array(
                'loc' => self::stripAccents(urldecode($this->get('router')->generate('macson_category_shoponline', array(
                    'lang' => $lang
                )))), 
                'changefreq' => 'monthly', 
                'priority' => '0.3'
            );
            $urls[] = array(
                'loc' => self::stripAccents(urldecode($this->get('router')->generate('macson_category_outlet_home', array(
                    'lang' => $lang
                )))), 
                'changefreq' => 'monthly', 
                'priority' => '0.3'
            );
            // ...
        }
  
        // incluye urls desde base de datos
        foreach($languages as $lang => $desc) {
		$categorias = Vocabulary::getAllCategories($lang);
		foreach ($categorias as $item) {
		    $urls[] = array(
			'loc' => self::stripAccents(urldecode($this->get('router')->generate('macson_category', array(
			    'lang' => $lang,
			    'category'=>$item['name'],
			    'categorynames'=>$item['url'],
			)))), 
			'priority' => '0.5'
		    );
		}
	}

        $productos = ContentUtils::getAllNodeUrls('product');
        foreach ($productos as $item) {
            $urls[] = array(
                'loc' => self::stripAccents(urldecode($this->get('router')->generate('drufony_general_url', array(
                    'lang'=>$item['lang'],
		    'url'=>$item['url'], 
                )))), 
                'priority' => '0.5'
            );
        }

        $pages = ContentUtils::getAllNodeUrls('page');
        foreach ($pages as $item) {
            $urls[] = array(
                'loc' => self::stripAccents(urldecode($this->get('router')->generate('drufony_general_url', array(
                    'lang'=>$item['lang'],
		    'url'=>$item['url'], 
                )))), 
                'priority' => '0.3'
            );
        }
        return $this->render('CustomProjectBundle::sitemap.xml.twig', array(
            'urls'     => $urls, 
            'hostname' => $hostname
        ));
    }

    private function stripAccents($string){
	$string = str_replace('ó','o',$string);
	$string=   strtr($string,'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝñ','aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUYn');
	return $string;
	
    }

}
