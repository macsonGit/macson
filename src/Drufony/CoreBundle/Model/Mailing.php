<?php
/**
 * Implements the principal mailing class. It defines all the notifications
 * which will be used to advice users about some events. It also defines
 * some methods for sending emails to the users.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Drufony\CoreBundle\Entity\User;
use Drufony\CoreBundle\Model\Order;
use Drufony\CoreBundle\Model\Geo;

/**
 * Provides methods for mailing to users. It defines all the system notifications.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Mailing {
    /**
     * Sends an email on user signup
     *
     * @params string $email
     *   The user email to send the information
     */
    static public function sendRegisterEmail($email) {
        $customParams = self::_getUserEmailParams($email);
        $params     = array();
        $subject    = t('Macson. Welcome', $params, $customParams['lang']);
        $template   = 'email-user-register.html.twig';

        self::sendMail($email, $subject, $template, $customParams);
    }

    /**
     * How define custom subject, body.. for a project?
     */
    static public function sendForgotPassword($email) {
        $customParams         = self::_getUserEmailParams($email);
        $routeName          = 'drufony_user_forgot_pass';
        $forgotPasswordLink = self::_getLoginLink($routeName, $customParams['uid'], $customParams['lang']);
        $customParams['link'] = $forgotPasswordLink;

        $params     = array();
        $subject    = t('Macson. Password recovery', $params, $customParams['lang']);
        $template   = 'email-password-recovery.html.twig';

        self::sendMail($email, $subject, $template, $customParams);
    }

    static public function sendReportAbuse($email, $nid, $pathToReport) {
        $customParams  = array(
            'nid'          => $nid,
            'pathToReport' => $pathToReport,
        );
        $subject    = t('Report Abuse for content with nid: @nid', array('@nid' => $nid));
        $template   = 'email-report-abuse.html.twig';

        self::sendMail($email, $subject, $template, $customParams);
    }

    /**
     * Sends an email to validate user's email
     *
     * @param string $email
     *   The user's email
     */
    static public function sendEmailValidation($email) {
        $customParams         = self::_getUserEmailParams($email);
        $routeName          = 'drufony_user_validate_email';
        $forgotPasswordLink = self::_getLoginLink($routeName, $customParams['uid'], $customParams['lang']);
        $customParams['link'] = $forgotPasswordLink;

        $subject = t('Validate your e-mail address');
        $template = 'email-user-validate.html.twig';

        self::sendMail($email, $subject, $template, $customParams);
    }

    static public function sendUserOrderCompletedEmail($email, $orderId, $paymentPlataform) {
        $customParams         = self::_getUserEmailParams($email);
        $customParams['orderId'] = $orderId;
        $customParams['paymentPlataform'] = $paymentPlataform;

	$customParams['orderInfo']= Order::getOrderInfo($orderId);
 
	$customParams['billing_Info']=unserialize($customParams['orderInfo']['billingInfo']);
	$customParams['shipping_Info']=unserialize($customParams['orderInfo']['shippingInfo']);
	$customParams['shipping_Info']['country']=Geo::getCountryNamebyId($customParams['shipping_Info']['countryId']);

	$customParams['cart']= Order::getProductsFromDB($orderId);
        
	$subject = t('Macson. Order @orderId, completed successfully', array('@orderId'=> $orderId));
        $template = 'email-user-order-completed.html.twig';

        self::sendMail($email, $subject, $template, $customParams);
    }

    static public function sendManagementOrderCompletedEmail($email, $userEmail, $orderId, $paymentPlataform, $paymentHash) {
        $customParams         = self::_getUserEmailParams($userEmail);
        $customParams['orderId'] = $orderId;
        $customParams['paymentPlataform'] = $paymentPlataform;
        $customParams['paymentHash'] = $paymentHash;

	$customParams['orderInfo']= Order::getOrderInfo($orderId);
	
	$customParams['billing_Info']=unserialize($customParams['orderInfo']['billingInfo']);
	$customParams['shipping_Info']=unserialize($customParams['orderInfo']['shippingInfo']);
	$customParams['shipping_Info']['country']=Geo::getCountryNamebyId($customParams['shipping_Info']['countryId']);
 
	$customParams['cart']= Order::getProductsFromDB($orderId);
        $subject = t('Macson. Order @orderId, completed successfully', array('@orderId'=> $orderId));
        $template = 'email-management-order-completed.html.twig';

        self::sendMail($email, $subject, $template, $customParams);
    }

    /**
     * sendMail
     *
     * @description Sends an email using SwiftMailerBundle.
     *
     * @param string $to email address
     * @param string $subject
     * @param twig $template
     * @param array $customParams
     * @param string $from
     * @param string $format
     * @param array $attachments
     *
     * @return void
     */
    public static function sendMail($to, $subject, $template, $customParams = array(),
            $from = DEFAULT_EMAIL_ADDRESS, $format = 'text/html', $attachments = array(), $transport = FALSE) {

        if ($transport) {
            $transport = \Swift_SmtpTransport::newInstance(MANDRILL_SMTP_ADDRESS, MANDRILL_SMTP_PORT)
            ->setUsername(MANDRILL_SMTP_USERNAME)
            ->setPassword(MANDRILL_SMTP_PASSWORD)
            ;
            $mailer = \Swift_Mailer::newInstance($transport);
        }
        else {
            $mailer = getMailer();
        }

        $body = self::_getBody($to, $template, $customParams);

        $mail = \Swift_Message::newInstance();

        $mail->setFrom($from);
        $mail->setDate(time());
        $mail->setTo($to);
        $mail->setSubject($subject);
        $mail->setBody($body);
        $mail->setContentType($format);
        $mail->setCharset('utf-8');

        self::_setAttachments($mail, $attachments);

        $mailer->send($mail, $failure);
    }

    static public function checkLoginLink($uid, $loginToken) {
    }

    static private function _setLoginToken($uid, $timestamp) {
        $token = $uid . $timestamp . DRUFONY_SALT;
        $token = sha1($token);
        UserUtils::addForgotToken($token, $uid);
        return $token;
    }

    static private function _getLoginLink($routeName, $uid, $lang = DEFAULT_LANGUAGE) {
        $router = getRouter();
        $timestamp = time();

        $hash = self::_setLoginToken($uid, $timestamp);

        $routeParams = array(
           'uid'       => $uid,
           'timestamp' => $timestamp,
           'hash'      => $hash,
           'lang'      => $lang,
        );

        $link = $router->generate($routeName, $routeParams, true);

        return $link;
    }

    static private function _getUserEmailParams($email) {
        $uid = UserUtils::getUidByEmail($email);
        $user = new User($uid);

        $customParams = array(
            'lang'          => $user->getLang(),
            'username'      => $user->getUserName(),
            'uid'           => $uid,
            'newsletter'    => $user->getNewsletter(),
        );

        return $customParams;
    }

    static private function _getBody($email, $template, $customParams) {
        $templating = getTemplating();

        $templatePath = $templating->exists("CustomProjectBundle::emails/${template}") ?
            "CustomProjectBundle::emails/${template}" : "DrufonyCoreBundle::emails/${template}";
		
	

	$lang=$customParams['lang'];

        $body = $templating->render($templatePath, array('customParams'    => $customParams,'lang'=>$lang));

        return $body;
    }

    static private function _setAttachments(&$mail, $files) {
        // Attach all files to the mail.
        foreach ($files as $oneFile) {
            if (file_exists($oneFile)) {
                $mail->attach(\Swift_Attachment::fromPath($oneFile));
            }
        }
    }
}
