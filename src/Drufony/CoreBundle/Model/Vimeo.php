<?php
/**
 * Implements the VideoInterface for Vimeo video provider.
 */

namespace Drufony\CoreBundle\Model;

// Class dependencies
use Drufony\CoreBundle\Resources\libs\vimeo\Vimeophp;

/**
 * Implements VideoInterface. It's an specific class for Vimeo video proovider.
 *
 * @package Drufony
 * @author Drufony Team <drufony@crononauta.com>
 * @version $Id$
 */
class Vimeo implements VideoInterface {
    private $consumerKey = '';
    private $consumerSecret = '';
    private $accessToken = '';
    private $accessTokenSecret = '';
    private $videoID = false;
    private $title = false;
    private $vimeoObj = false;

    /**
     * Contructs the object.
     */
    public function __construct($videoID = false) {
        //Initialize the video object with its VimeoID
        if($videoID) {
            $this->videoID = $videoID;
        }
    }

    public function upload($file, $title, $description=NULL) {
        $this->initAuth(VIMEO_CONSUMER_KEY, VIMEO_CONSUMER_SECRET, VIMEO_ACCESS_TOKEN, VIMEO_ACCESS_SECRET);
        $videoId = $this->uploadVideo($file->getPathname(), $title, $description);

        return $videoId;
    }

    public function get($videoToken) {
        //Get extra info about video
        $curl_url = 'http://vimeo.com/api/v2/video/' . $videoToken .'.json';
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $curl_url
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($response);

        return !is_null($data) ? reset($data) : array();
    }

    /**
     * Inits the vimeo Oath Parameters.
     * @param $consKey - Vimeo Oauth Consumer Key
     * @param $consSecret - Vimeo Oauth Consumer Secret
     * @param $token - Vimeo Oauth Access Token
     * @param $tokenSecret - Vimeo Oauth Access Token Secret
     */
    public function initAuth($consKey, $consSecret, $token, $tokenSecret)
    {
        $this->consumerKey = $consKey;
        $this->consumerSecret = $consSecret;
        $this->accessToken = $token;
        $this->accessTokenSecret = $tokenSecret;
    }

    /**
     * Instances the vimeo object
     */
    private function createInstance()
    {
        $this->vimeoObj = new Vimeophp($this->consumerKey, $this->consumerSecret, $this->accessToken, $this->accessTokenSecret);
    }

    /**
     * Uploads a video to Vimeo
     * @param $videoFile the bynary data of the video to upload
     * @param $title the title for this video
     * @param $description the optional description
     * @return the vimeoID of the uploaded video or FALSE on failure
     */
    public function uploadVideo($videoFile, $title, $description)
    {
        if(!$this->vimeoObj) {
            $this->createInstance();
        }
        try {
            $this->videoID = $this->vimeoObj->upload($videoFile);
            $this->vimeoObj->call('vimeo.videos.setTitle', array('title' => $title, 'video_id' => $this->videoID));
            if (!empty($description)) {
                $this->vimeoObj->call('vimeo.videos.setDescription', array('description' => $description, 'video_id' => $this->videoID));
            }

        }catch(VimeoAPIException $e) {

            return "Encountered an API error -- code {$e->getCode()} - {$e->getMessage()}";

        }
        return $this->videoID;
    }


    /**
     * Sets the title of a video
     * @param $videoTitle - The title of the video
     * @return true or false on failure;
     */
    public function setTitle($videoTitle)
    {
         if(!$this->vimeoObj) {
             $this->createInstance();
         }
        $this->vimeoObj->setTitle($this->videoID, $videoTitle);
        return true;
    }

    /**
     * Deletes a video
     * @param $videoID the id of the video
     * @return true
     */
    public function delete($videoID)
    {
        if(!$this->vimeoObj) {
            $this->createInstance();
        }

        $this->vimeoObj->delete($videoID);
        return true;
    }

    /**
     * Generates the Embed of a video... Use oembed API instead: look in Resources/public/js/vimeo.js ;)
     */
    public function getEmbeddedVideo($url, $videoID)
    {
    }
}
