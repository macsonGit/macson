<?php
/**
 * This class provides some static methods for handling user sessions.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

// Class constants
// Defines constants if undefined. Those constants could be defined in
// a setting file.
defined('DEFAULT_TIME_FOR_REMEMBER') or define('DEFAULT_TIME_FOR_REMEMBER', 1728000);//20 days

/**
 * Provides some methods for handling user sessions.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Session
{

    /**
     * Check user session based on the cookie.
     */
    static public function getActiveSession($sid=null)
    {
        $session = FALSE;
        if (is_null($sid)) {
            $sid=self::getSessionId();
        }
        $sql = "
        SELECT uid, hostname, timestamp
        FROM sessions
        WHERE sid = '$sid'
        ";

        return  db_fetchAssoc($sql);
    }

    static public function getSessionId()
    {
        $request = Request::createFromGlobals();
        $sessionId = $request->cookies->get(SESSION_HASH);
        return $sessionId;
    }

    static public function getCookieName($cookieName)
    {
        $request = Request::createFromGlobals();
        $cookies = $request->cookies;
        $cookie = NULL;
        if ($cookies->has($cookieName))
        {
            $cookie = $cookies->get($cookieName);
        }

        return $cookie;
    }

    static public function setCookie($cookieName, $cookieValue, $expire = 0,$path='/',
            $domain=COOKIE_DOMAIN,$secure=false,$httponly=false)
    {

        $cookie = new Cookie($cookieName, $cookieValue, $expire, $path, $domain, false, false);
        $response = new Response();
        $response->headers->setCookie($cookie);

        return $response->sendHeaders();
    }

    static public function deleteCookie($cookieName)
    {
        $path="/";
        $domain=COOKIE_DOMAIN;
        $response=new Response();
        $response->headers->clearCookie($cookieName,$path,COOKIE_DOMAIN);

        return self::setCookie($cookieName, null, -1, $path, $domain, false, false);
    }
}
