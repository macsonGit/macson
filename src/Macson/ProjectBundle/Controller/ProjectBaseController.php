<?php
namespace Macson\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Drufony\UserBundle\Model\User;
use Drufony\GeoBundle\Model\Geo;
use Drufony\UserBundle\Model\Session;

/**
 * This class extends from controller and contains commom methods that
 * are going to be used in other controllers...
 * @author AG
 *
 */
class ProjectBaseController extends Controller
{
    protected $user;
    protected $request;
    protected $page;
    protected $userObj;
    protected $baseParams = array();

    // users profile passed to all twig templates in self::renderPage()
    protected $profile = NULL;

    /**
     * Inits base data.
     *
     * Inits the data used through the all the website.
     * i.e: the user data.
     */
    protected function init()
    {
        // This globals are used only in app_drufony
        global $db;
        $db = $this->get('database_connection'); // Only place allowed to do this.

        global $memcache;
        //$memcache = new Memcache;
        //$memcache->addServer('localhost', 11211);      

        global $geo;
        //$geo=new Geo;
        
        global $dispatcher;
        $dispatcher = $this->get('event_dispatcher');

        global $router;
        $router = $this->get('router');

        global $templating;
        $templating = $this->container->get('templating');



        // End globals

        // Load user data in renderParams.
        //$this->user = $this->loadUserData();
        //$this->request = $this->getRequest();

        // Add the page for paginators
        //$this->page = $this->request->query->get('page');        

    }

}
