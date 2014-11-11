<?php

// Make the 't' function globally available.
use Drufony\ContentBundle\Model\Locale;

function t() {
    return call_user_func_array('\Drufony\CoreBundle\Model\Locale::t', func_get_args());
}

function getRouter() {
    global $router;
    return $router;
}

function getMailer() {
    global $mailer;
    return $mailer;
}

function getTemplating() {
    global $templating;
    return $templating;
}

function l($logLevel, $message){
    global $logger;
    $logger->$logLevel($message);
}

function getSession() {
    global $session;
    return $session;
}

function getLang() {
    global $lang;
    return $lang;
}

function getCurrentUser() {
    global $securityContext;
    $user = $securityContext->getToken()->getUser();
    return is_object($user) ? $user : null;
}

