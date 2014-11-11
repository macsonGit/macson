<?php

namespace Drufony\CoreBundle\Model;

/**
 * MailChimpStats, retrive stats from mailchimp of existing campaings and
 * update database
 */
class MailChimpStats
{

    private static $campaignsInfo = array();
    private static $dataRetrieved= false;

    private function __construct(){}

    private static function _retrieveDataFromDb() {
        if(self::$dataRetrieved)
            return;

        self::_getCampaignsFromDb();
        self::$dataRetrieved = true;
    }

    public static function updateDb($mailchimp) {
        self::_retrieveDataFromDb();
        self::_updateDatabase($mailchimp);
        self::_getCampaignsFromDb();
    }

    /**
     * Retrieves campaign info stored in database
     */
    private static function _getCampaignsFromDb() {
        self::$campaignsInfo = array();

        $query = "SELECT *
            FROM mailchimp";

        if($queryResult = db_executeQuery($query, array(), False)) {
            while($campaign = $queryResult->fetch())
                self::$campaignsInfo[$campaign['campaign_id']] = $campaign;
        }
    }

    /**
     * Updates database with the information retrieved from the mailchimp api
     * Insert the campaign if does not exist in db, update it if exists
     */
    private static function _updateDatabase($mailchimp) {
        $campaignRetrieved = self::_getCampaignListFromApi($mailchimp);

        foreach($campaignRetrieved as $campaign){
            $campaignSummary = self::getSummary($mailchimp, $campaign['id']);

            if(!array_key_exists($campaign['id'], self::$campaignsInfo))
                self::_insertCampaign($campaign['id'], $campaignSummary, $campaign['list_subscriptions']);
            else
                self::_updateCampaign($campaign['id'], $campaignSummary, $campaign['list_subscriptions']);
        }
    }

    /**
     * Set variable to '' if is null
     */
    private static function _reset($string) {
        $result = $string;

        if(!$string)
            $result = '';

        return $result;
    }

    /**
     * Inserts a campaign into database
     */
    private static function _insertCampaign($campaignId, $summary, $list_subscriptions) {
        $insertData = array('campaign_id' => $campaignId, 'syntax_errors' => $summary['syntax_errors'],
            'hard_bounces' => $summary['hard_bounces'], 'soft_bounces' => $summary['soft_bounces'],
            'unsubscribes' => $summary['unsubscribes'], 'abuse_reports' => $summary['abuse_reports'],
            'forwards' => $summary['forwards'], 'forwards_opens' => $summary['forwards_opens'],
            'opens' => $summary['opens'], 'last_open' => self::_reset($summary['last_open']),
            'unique_opens' => $summary['unique_opens'], 'clicks' => $summary['clicks'],
            'last_click' => self::_reset($summary['last_click']), 'users_who_clicked' => $summary['users_who_clicked'],
            'emails_sent' => $summary['emails_sent'], 'list_subscriptions' => $list_subscriptions);

        db_insert('mailchimp', $insertData);
    }

    /**
     * Update a existing campaign database
     */
    private static function _updateCampaign($campaignId, $summary, $list_subscriptions) {
        $updateCriteria = array('campaign_id' => $campaignId);

        $updateData = array('syntax_errors' => $summary['syntax_errors'],
            'hard_bounces' => $summary['hard_bounces'], 'soft_bounces' => $summary['soft_bounces'],
            'unsubscribes' => $summary['unsubscribes'], 'abuse_reports' => $summary['abuse_reports'],
            'forwards' => $summary['forwards'], 'forwards_opens' => $summary['forwards_opens'],
            'opens' => $summary['opens'], 'last_open' => self::_reset($summary['last_open']),
            'unique_opens' => $summary['unique_opens'], 'clicks' => $summary['clicks'],
            'last_click' => self::_reset($summary['last_click']), 'users_who_clicked' => $summary['users_who_clicked'],
            'emails_sent' => $summary['emails_sent'], 'list_subscriptions' => $list_subscriptions);

        db_update('mailchimp', $updateData, $updateCriteria);
    }

    /**
     * Retrieve all campaigns from mailchip api
     */
    private static function _getCampaignListFromApi($mailchimp) {
        $campaignsList = array();

        try {
            $campaignsFromMailChimp = $mailchimp->getCampaigns();
            $campaignsList = $campaignsFromMailChimp['data'];
        }
        catch(\ZfrMailChimp\Exception\ExceptionInterface $e) {
            //TODO: control Exception if conection to mailchimp fail to get campaigns
        }

        foreach($campaignsList as &$campaign) {
            $campaign['list_subscriptions'] = self::_getSubcriptionsByList($mailchimp, $campaign['list_id']);
        }

        return $campaignsList;
    }

    /**
     * Returns all campaigns stored in DB
     */
    public static function getCampaignList() {
        self::_retrieveDataFromDb();
        return self::$campaignsInfo;
    }

    /**
     * Returns the percentage of bounce emails by campaign
     */
    public static function getBounces($campaignId) {
        self::_retrieveDataFromDb();

        $bounceRate = null;

        if(array_key_exists($campaignId, self::$campaignsInfo)) {
            $campaign = self::$campaignsInfo[$campaignId];

            $emailsSent = $campaign['emails_sent'];
            $totalBounces = $campaign['soft_bounces'] +
                            $campaign['hard_bounces'] +
                            $campaign['syntax_errors'];

            $bounceRate = $totalBounces * 100 / $emailsSent;

        }
        return $bounceRate;
    }

    /**
     * Returns the percentage of opened emails
     */
    public static function getOpenRate($campaignId) {
        self::_retrieveDataFromDb();
        $rate = null;
        if(array_key_exists($campaignId, self::$campaignsInfo)) {
            $campaign = self::$campaignsInfo[$campaignId];

            $emailsSent = $campaign['emails_sent'];
            $opens = $campaign['opens'];

            $rate = $opens * 100 / $emailsSent;
        }
        return $rate;
    }

    /**
     * Numbers of clicked links in the sent emails
     */
    public static function getClicks($campaignId) {
        return self::_getCampaignInfo($campaignId, 'clicks');
    }

    /**
     * Retrieved the list_id that a campaign belongs
     */
    private static function _getList($mailchimp, $campaignId) {
        $campaignsFromApi = self::_getCampaignListFromApi($mailchimp);
        $listId = null;
        foreach($campaignsFromApi as $campaign) {
            if($campaign['id'] == $campaignId) {
                $listId = $campaign['list_id'];
                break;
            }
        }
        return $listId;
    }

    /**
     * Returns the number of subscribers of a list
     */
    private static function _getSubcriptionsByList($mailchimp, $listId) {
        $subscriptors = array();
        try {
            $subscriptorsiByList = $mailchimp->getListMembers(array('id' => $listId));
            $subscriptors = count($subscriptorsiByList['data']);
        }
        catch(\ZfrMailChimp\Exception\ExceptionInterface $e) {
            //TODO: control Exception if conection to mailchimp fail to get listmembers
        }
        return $subscriptors;
    }

    /**
     * Returns the number subscribers in a campaign
     */
    private static function _getSubscriptionsByCampign($mailchimp, $campaignId) {
        self::_retrieveDataFromDb();
        $subscritorsByListId = null;
        if(array_key_exists($campaignId, self::$campaignsInfo)) {
            $listId = self::_getList($mailchimp, $campaignId);
            $subscritorsByListId = self::_getSubcriptionsByList($mailchimp, $listId);
        }
        return $subscritorsByListId;
    }

    /**
     * Returns the number subscribers in a campaign
     */
    public static function getSubscriptions($campaignId) {
        return self::_getCampaignInfo($campaignId, 'list_subscriptions');
    }

    /**
     * Returns the number of unsubscritions in a campaign
     */
    public static function getUnsubscriptions($campaignId) {
        return self::_getCampaignInfo($campaignId, 'unsubscribes');
    }

    /**
     * Retrieve the summary of a campaign
     */
    public static function getSummary($mailchimp, $campaignId) {
        self::_retrieveDataFromDb();
        $summary = null;
        try {
            $summary = $mailchimp->getCampaignSummaryReport(array('cid' => $campaignId));
        }
        catch(\ZfrMailChimp\Exception\ExceptionInterface $e) {
            //TODO: control Exception if conection to mailchimp fail to get summary
        }
        return $summary;
    }

    /**
     * Returns the number of complains in a campaign
     */
    public static function getAbuseReports($campaignId) {
        return self::_getCampaignInfo($campaignId, 'abuse_reports');
    }

    /**
     * Number of forwards to a friend
     */
    public static function getForwards($campaignId) {
        return self::_getCampaignInfo($campaignId, 'forwards');
    }

    /**
     * Number of opened forwards
     */
    public static function getForwardsOpens($campaignId) {
        return self::_getCampaignInfo($campaignId, 'forwards_opens');
    }

    /**
     * Date of the last opened email in a campaign
     */
    public static function getLastOpenDate($campaignId) {
        return self::_getCampaignInfo($campaignId, 'last_open');
    }

    /**
     * Number of unique users that have opened an email in a campaign
     */
    public static function getUserOpens($campaignId) {
        return self::_getCampaignInfo($campaignId, 'unique_opens');
    }

    /**
     * Date of the last time that a email link was clicked in a campaign
     */
    public static function getLastClickDate($campaignId) {
        return self::_getCampaignInfo($campaignId, 'last_click');
    }

    /**
     * Number of unique users that have clicked an email link in a campaign
     */
    public static function getUserClicks($campaignId) {
        return self::_getCampaignInfo($campaignId, 'users_who_clicked');
    }

    /**
     * Number of sent emails in a campaign
     */
    public static function getSentEmails($campaignId) {
        return self::_getCampaignInfo($campaignId, 'emails_sent');
    }

    private static function _getCampaignInfo($campaignId, $key) {
        self::_retrieveDataFromDb();
        $result = null;
        if(array_key_exists($campaignId, self::$campaignsInfo)) {
            $campaign = self::$campaignsInfo[$campaignId];
            $result = $campaign[$key];
        }
        return $result;
    }

    /**
     * Subscribe a user into a list given
     *
     * @param string $listId:
     * @param User $user: user to subscribe, null if user is not logged
     * @param string $email: non logged user email to subscribe
     *
     * @return array with email user information in mailchimp
     */
    public static function subscribe($mailchimp, $listId, $user = null, $email) {
        $result = '';
        $sendConfirmation = true;
        $emailToSubscribe = $email;

        if(!is_null($user)) {
            $sendConfirmation = false;
            $emailToSubscribe = $user->getEmail();
        }

        try {
            $result = $mailchimp->subscribe(array('id' => $listId,
                                                        'email' => array('email' => $emailToSubscribe),
                                                        'double_optin' => $sendConfirmation));
        }
        //FIXME: control exception if email is already subscribe in the list in subscribe
        catch(\ZfrMailChimp\Exception\Ls\AlreadySubscribedException $e){}

        return $result;
    }
}
