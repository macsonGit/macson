<?php

namespace Drufony\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Drufony\CoreBundle\Model\MailChimpStats;

class MailChimpController extends DrufonyController
{
    /**
     * Catchall controller for any not-found URL in Symfony.
     * The real path for an URL is checked with Drupal.
     */
    public function updateDatabaseAction() {
        $mailchimp = $this->get('zfr_mail_chimp')->getClient();

        $campaigns = MailChimpStats::getCampaignList();
        MailChimpStats::updateDb($mailchimp);
        $campaigns2 = MailChimpStats::getCampaignList();

        foreach(array_keys($campaigns2) as $campaignId){
            $string = 'Bounces: ' . MailChimpStats::getBounces($campaignId) . ' ';
            $string .= 'Open rate:' . MailChimpStats::getOpenRate($campaignId) . ' ';
            $string .= 'Clicks: ' . MailChimpStats::getClicks($campaignId) . ' ';
            $string .= 'Subscriptions: ' . MailChimpStats::getSubscriptions($campaignId) . ' ';
            $string .= 'Unsubscriptions: ' . MailChimpStats::getUnsubscriptions($campaignId) . ' ';
            $string .= 'Abuse reports: ' . MailChimpStats::getAbuseReports($campaignId) . ' ';
            $string .= 'Forwards: ' . MailChimpStats::getForwards($campaignId) . ' ';
            $string .= 'Forwards open: ' . MailChimpStats::getForwardsOpens($campaignId) . ' ';
            $string .= 'Last open date: ' . MailChimpStats::getLastOpenDate($campaignId) . ' ';
            $string .= 'User opens: ' . MailChimpStats::getUserOpens($campaignId) . ' ';
            $string .= 'Last click date: ' . MailChimpStats::getLastClickDate($campaignId) . ' ';
            $string .= 'User clicks: ' . MailChimpStats::getUserClicks($campaignId) . ' ';
            $string .= 'Sent emails: ' . MailChimpStats::getSentEmails($campaignId) . ' ';
        }

        ld(MailChimpStats::getListsInfo());

        $result = MailChimpStats::subscribe($mailchimp, 'c0062246a0', null, 'tests@example.com');

        $response = new Response('MailchimpStats updated successfully', '200');
        return $response;
    }
}
