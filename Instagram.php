<?php
/**
 * Instagram PHP implementation API
 * URLs: http://www.mauriciocuenca.com/
 *
 * Fixed and updated by Giuliano Iacobelli
 * URLs: http://www.giulianoiacobelli.com/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'CurlHttpClient.php';

class Instagram {

    /**
     * The name of the GET param that holds the authentication code
     * @var string
     */
    const RESPONSE_CODE_PARAM = 'code';

    /**
     * Format for endpoint URL requests
     * @var string
     */
    protected $_endpointUrls = array(
        'reg_subscription'=>'https://api.instagram.com/v1/subscriptions/',
        'list_subscription'=>'https://api.instagram.com/v1/subscriptions?client_secret=%s&client_id=%s',
        'del_subscription'=>'https://api.instagram.com/v1/subscriptions?client_secret=%s&client_id=%s&object=%s',

        'authorize' => 'https://api.instagram.com/oauth/authorize/?client_id=%s&redirect_uri=%s&response_type=%s&scope=likes+comments+relationships',
        'access_token' => 'https://api.instagram.com/oauth/access_token',

        'user' => 'https://api.instagram.com/v1/users/%d/?%s',
        'user_feed' => 'https://api.instagram.com/v1/users/self/feed?%s',
        'user_recent' => 'https://api.instagram.com/v1/users/%s/media/recent/?%s&count=%d&min_timestamp=%s&max_timestamp=%s&min_id=%s&max_id=%s',//
        'user_recentna' => 'https://api.instagram.com/v1/users/%s/media/recent/?client_id=%s',
        'user_search' => 'https://api.instagram.com/v1/users/search?%s&q=%s&count=%s',//
        'user_follows' => 'https://api.instagram.com/v1/users/%d/follows?%s',//
        'user_followed_by' => 'https://api.instagram.com/v1/users/%d/followed-by?%s',//
        'user_requested_by' => 'https://api.instagram.com/v1/users/self/requested-by?access_token=%s',
        'user_relationship' => 'https://api.instagram.com/v1/users/%d/relationship?access_token=%s',
        'modify_user_relationship' => 'https://api.instagram.com/v1/users/%d/relationship?action=%s&access_token=%s',

        'media' => 'https://api.instagram.com/v1/media/%d?%s',//
        'media_short' => 'https://api.instagram.com/v1/media/shortcode/%s?%s',//
        'media_search' => 'https://api.instagram.com/v1/media/search?lat=%s&lng=%s&max_timestamp=%d&min_timestamp=%d&distance=%d&access_token=%s',
        'media_popular' => 'https://api.instagram.com/v1/media/popular?access_token=%s',

        'media_comments' => 'https://api.instagram.com/v1/media/%d/comments?%s',//
        'post_media_comment' => 'https://api.instagram.com/v1/media/%s/comments',
        'delete_media_comment' => 'https://api.instagram.com/v1/media/%d/comments?comment_id=%d&access_token=%s',

        'likes' => 'https://api.instagram.com/v1/media/%d/likes?%s',//
        'post_like' => 'https://api.instagram.com/v1/media/%s/likes',
        'remove_like' => 'https://api.instagram.com/v1/media/%d/likes?access_token=%s',

        'tags' => 'https://api.instagram.com/v1/tags/%s?%s',//
        'tags_recent' => 'https://api.instagram.com/v1/tags/%s/media/recent?%s&max_id=%d&min_id=%d',//
        'tags_search' => 'https://api.instagram.com/v1/tags/search?q=%s&%s',//

        'locations' => 'https://api.instagram.com/v1/locations/%d?%s',//
        'locations_recent' => 'https://api.instagram.com/v1/locations/%d/media/recent/?%s&max_id=%d&min_id=%d&max_timestamp=%d&min_timestamp=%d',
        'locations_search' => 'https://api.instagram.com/v1/locations/search?lat=%s&lng=%s&%s&foursquare_id=%d&distance=%d',

        'geographies_recent'=>'https://api.instagram.com/v1/geographies/%d/media/recent?%s&count=%d&min_id=%d'
    );

    /**
     * Configuration parameter
     */
    protected $_config = array();

    /**
     * Whether all response are sent as JSON or decoded
     */
    protected $_arrayResponses = false;

    /**
     * OAuth token
     * @var string
     */
    protected $_oauthToken = null;

    /**
     * OAuth token
     * @var string
     */
    protected $_accessToken = null;

    /**
     * OAuth user object
     * @var object
     */
    protected $_currentUser = null;

    /**
     * Holds the HTTP client instance
     * @param Zend_Http_Client $httpClient
     */
    protected $_httpClient = null;

    /**
     * Constructor needs to receive the config as an array
     * @param mixed $config
     */
    public function __construct($config = null, $arrayResponses = false) {
        $this->_config = $config;
        $this->_arrayResponses = $arrayResponses;
        if (empty($config)) {
            throw new InstagramException('Configuration params are empty or not an array.');
        }
    }

    /**
     * Instantiates the internal HTTP client
     * @param string $uri
     * @param string $method
     */
    protected function _initHttpClient($uri, $method = CurlHttpClient::GET) {
        if ($this->_httpClient == null) {
            $this->_httpClient = new CurlHttpClient($uri);
        } else {
            $this->_httpClient->setUri($uri);
        }
        $this->_httpClient->setMethod($method);
    }

    /**
     * Returns the body of the HTTP client response
     * @return string
     */
    protected function _getHttpClientResponse() {
        return $this->_httpClient->getResponse();
    }

    /**
     * Return JSON answer from URL request
     * @endpointUrl string. URL
     * @return string. The JSON encoded
     */
    protected function getJsonAnswer($endpointUrl) {
        $this->_initHttpClient($endpointUrl);
        $response = $this->_getHttpClientResponse();
        return $this->parseJson($response);
    }

    /**
     * register Subscription
     * @return string
     */
    public function RegSubscription($url_callback,$object='user',$object_id='',$lat='',$lng='') {
        $this->_initHttpClient($this->_endpointUrls['reg_subscription'], CurlHttpClient::POST);
        $this->_httpClient->setPostParam('client_id', $this->_config['client_id']);
        $this->_httpClient->setPostParam('client_secret', $this->_config['client_secret']);
        $this->_httpClient->setPostParam('object', $object);
//        $this->_httpClient->setPostParam('object_id', $object_id);
//        $this->_httpClient->setPostParam('lat', $lat);
//        $this->_httpClient->setPostParam('lng', $lng);
        $this->_httpClient->setPostParam('aspect', 'media');
        $this->_httpClient->setPostParam('verify_token', 'myVerifyToken');
        $this->_httpClient->setPostParam('callback_url', $url_callback);
        return $this->_getHttpClientResponse();

    }

    /**
     * List current Subscription
     * @return string
     */
    public function ListSubscription() {

        $endpointUrl = sprintf($this->_endpointUrls['list_subscription'], $this->_config['client_secret'], $this->_config['client_id']);
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * delete subscript by object
     * @param string $object
     * @return mixed
     */
    public function DelSubscription($object='all') {
        $endpointUrl = sprintf($this->_endpointUrls['del_subscription'], $this->_config['client_secret'], $this->_config['client_id'], $object);
        $this->_initHttpClient($endpointUrl, CurlHttpClient::DELETE);
        $response = $this->_getHttpClientResponse();
        return $this->parseJson($response);

    }

    /**
     * get info to callback
     * @param $xhub
     * @param $posted_data
     * @return bool|mixed
     */
    public function GetNewSubscription($xhub,$posted_data) {
        if($this->checkXhub($xhub,$posted_data))
            return json_decode($posted_data,true);
        return false;

    }

    /**
     * check data from instagram
     * @param $xhub
     * @param $posted_data
     * @return bool
     */
    protected function checkXhub($xhub,$posted_data){
        $hash = hash_hmac( 'sha1', $posted_data,$this->_config['client_secret']);
        return ($xhub==$hash)? true : false;
    }

    /**
     * Retrieves the authorization code to be used in every request
     * @return string. The JSON encoded OAuth token
     */
    protected function _setOauthToken() {
        $this->_initHttpClient($this->_endpointUrls['access_token'], CurlHttpClient::POST);
        $this->_httpClient->setPostParam('client_id', $this->_config['client_id']);
        $this->_httpClient->setPostParam('client_secret', $this->_config['client_secret']);
        $this->_httpClient->setPostParam('grant_type', $this->_config['grant_type']);
        $this->_httpClient->setPostParam('redirect_uri', $this->_config['redirect_uri']);
        $this->_httpClient->setPostParam('code', $this->getAccessCode());
        $this->_oauthToken = $this->_getHttpClientResponse();

        //set in session token
       // Yii::app()->session['InstagramToken'] = json_decode($this->_oauthToken)->access_token;
    }

    /**
     * Return the decoded plain access token value
     * from the OAuth JSON encoded token.
     * @return string
     */
    public function getAccessToken() {
        if ($this->_accessToken == null) {

            if ($this->_oauthToken == null) {
                $this->_setOauthToken();
            }

            if(isset(json_decode($this->_oauthToken)->error_type))
                print_r(json_decode($this->_oauthToken));

            $this->_accessToken = json_decode($this->_oauthToken)->access_token;
        }

        return $this->_accessToken;
    }

    /**
     * Return param in url
     * @param $auth
     * @return string
     */

    public function getAuthUrlParam($auth) {

        $param=($auth)?'access_token='.$this->getAccessToken():'client_id='.$this->_config['client_id'];

        return $param;
    }

    /**
     * Return the decoded user object
     * from the OAuth JSON encoded token
     * @return object
     */

    public function getCurrentUser() {

        if ($this->_currentUser == null) {

            if ($this->_oauthToken == null) {
                $this->_setOauthToken();
            }
            $this->_currentUser = json_decode($this->_oauthToken)->user;
        }

        return $this->_currentUser;
    }

    /**
     * Gets the code param received during the authorization step
     */
    protected function getAccessCode() {
        return $_GET[self::RESPONSE_CODE_PARAM];
    }

    /**
     * Sets the access token response from OAuth
     * @param string $accessToken
     */
    public function setAccessToken($accessToken) {
        $this->_accessToken = $accessToken;
    }

    /**
     * Surf to Instagram credentials verification page.
     * If the user is already authenticated, redirects to
     * the URI set in the redirect_uri config param.
     * @return string
     */
    public function openAuthorizationUrl() {
        header('Location: ' . $this->getAuthorizationUrl());
        exit(1);
    }

    /**
     * Generate Instagram credentials verification page URL.
     * Usefull for creating a link to the Instagram authentification page.
     * @return string
     */
    public function getAuthorizationUrl() {
        return sprintf($this->_endpointUrls['authorize'],
            $this->_config['client_id'],
            $this->_config['redirect_uri'],
            self::RESPONSE_CODE_PARAM);
    }

    /**
     * Get basic information about a user.
     * @param $id
     */
    public function getUser($id,$auth) {
        $endpointUrl = sprintf($this->_endpointUrls['user'], $id, $this->getAuthUrlParam($auth));
        $this->_initHttpClient($endpointUrl);
        return $this->_getHttpClientResponse();
    }

    /**
     * See the authenticated user's feed.
     * @param integer $maxId. Return media after this maxId.
     * @param integer $minId. Return media before this minId.
     */
    public function getUserFeed($maxId = null, $minId = null, $count = null) {
        $endpointUrl = sprintf($this->_endpointUrls['user_feed'], http_build_query(array('access_token' => $this->getAccessToken(), 'max_id' => $maxId, 'min_id' => $minId, 'count' => $count)));
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Get the most recent media user
     * @param bool $auth
     * @param $id
     * @param string $count
     * @param string $minTimestamp
     * @param string $maxTimestamp
     * @param string $minId
     * @param string $maxId
     * @return string
     */
    public function getUserRecent($auth=false,$id, $count = '', $minTimestamp = '', $maxTimestamp = '', $minId = '', $maxId = '') {

        $endpointUrl = sprintf($this->_endpointUrls['user_recent'], $id, $this->getAuthUrlParam($auth), $count, $minTimestamp, $maxTimestamp, $minId, $maxId);
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Search for a user by name.
     * @param $name
     * @param $count
     * @param bool $auth
     * @return string
     */
    public function searchUser($name,$count,$auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['user_search'], $this->getAuthUrlParam($auth), $name, $count);
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Get the list of users this user follows.
     * @param $id
     * @param bool $auth
     * @return string
     */
    public function getUserFollows($id,$auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['user_follows'], $id, $this->getAuthUrlParam($auth));
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Get the list of users this user is followed by.
     * @param $id
     * @param bool $auth
     * @return string
     */
    public function getUserFollowedBy($id,$auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['user_followed_by'], $id, $this->getAuthUrlParam($auth));
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * List the users who have requested this user's permission to follow
     */
    public function getUserRequestedBy() {
        $endpointUrl = sprintf($this->_endpointUrls['user_requested_by'], $this->getAccessToken());
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Get information about the current user's relationship (follow/following/etc) to another user.
     * @param $id
     * @return string
     */
    public function getUserRelationship($id) {
        $endpointUrl = sprintf($this->_endpointUrls['user_relationship'], $id, $this->getAccessToken());
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Modify the relationship between the current user and the target user
     * In order to perform this action the scope must be set to 'relationships'
     * @param $id
     * @param $action
     * @return object
     */
    public function modifyUserRelationship($id, $action) {
        $endpointUrl = sprintf($this->_endpointUrls['modify_user_relationship'], $id, $action, $this->getAccessToken());
        $this->_initHttpClient($endpointUrl, CurlHttpClient::POST);
        $this->_httpClient->setPostParam("action",$action);
        $this->_httpClient->setPostParam("access_token",$this->getAccessToken());
        $response = $this->_getHttpClientResponse();
        return $this->parseJson($response);
    }

///////////////////MEDIA
    /**
     * Get information about a media object.
     * @param $id
     * @param bool $auth
     * @return string
     */
    public function getMedia($id, $auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['media'], $id, $this->getAuthUrlParam($auth));
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Get information about a media object.
     * @param $mediaShort
     * @param bool $auth
     * @return string
     */
    public function getMediaShort($mediaShort, $auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['media_short'], $mediaShort, $this->getAuthUrlParam($auth));
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Search for media in a given area.
     * @param $lat
     * @param $lng
     * @param string $maxTimestamp
     * @param string $minTimestamp
     * @param string $distance
     * @return string
     */
    public function mediaSearch($lat, $lng, $maxTimestamp = '', $minTimestamp = '', $distance = '') {
        $endpointUrl = sprintf($this->_endpointUrls['media_search'], $lat, $lng, $maxTimestamp, $minTimestamp, $distance, $this->getAccessToken());
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     *  Get a list of what media is most popular at the moment.
     * @param bool $auth
     * @return string
     */
    public function getPopularMedia($auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['media_popular'], $this->getAuthUrlParam($auth));
        return $this->getJsonAnswer($endpointUrl);
    }

////////////COMMENT
    /**
     * Get a full list of comments on a media.
     * @param $id
     * @param bool $auth
     * @return string
     */
    public function getMediaComments($id, $auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['media_comments'], $id, $this->getAuthUrlParam($auth));
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Create a comment on a media.
     * @param $id
     * @param $text
     * @return string
     */
    public function postMediaComment($id, $text) {
        $endpointUrl = sprintf($this->_endpointUrls['post_media_comment'], $id, $text, $this->getAccessToken());
        $this->_initHttpClient($endpointUrl, CurlHttpClient::POST);
        $this->_httpClient->setPostParam('access_token', $this->getAccessToken());
        $this->_httpClient->setPostParam('text', $text);
        $response = $this->_getHttpClientResponse();
        return $response;
    }

    /**
     * Remove a comment either on the authenticated user's media or authored by the authenticated user.
     * @param $mediaId
     * @param $commentId
     * @return object
     */
    public function deleteComment($mediaId, $commentId) {
        $endpointUrl = sprintf($this->_endpointUrls['delete_media_comment'], $mediaId, $commentId, $this->getAccessToken());
        $this->_initHttpClient($endpointUrl, CurlHttpClient::DELETE);
        $response = $this->_getHttpClientResponse();
        return $this->parseJson($response);
    }

//////////////LIKES

    /**
     * Get a list of users who have liked this media.
     * @param $mediaId
     * @param bool $auth
     * @return string
     */
    public function getLikes($mediaId, $auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['likes'], $mediaId, $this->getAuthUrlParam($auth));
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Set a like on this media by the currently authenticated user.
     * @param $mediaId
     * @return object
     */
    public function postLike($mediaId) {
        $endpointUrl = sprintf($this->_endpointUrls['post_like'], $mediaId);
        $this->_initHttpClient($endpointUrl, CurlHttpClient::POST);
        $this->_httpClient->setPostParam('access_token', $this->getAccessToken());
        $response = $this->_getHttpClientResponse();
        return $this->parseJson($response);
    }

    /**
     * Remove a like on this media by the currently authenticated user.
     * @param $mediaId
     * @return object
     */
    public function removeLike($mediaId) {
        $endpointUrl = sprintf($this->_endpointUrls['remove_like'], $mediaId, $this->getAccessToken());
        $this->_initHttpClient($endpointUrl, CurlHttpClient::DELETE);
        $response = $this->_getHttpClientResponse();
        return $this->parseJson($response);
    }

//////////TAGS
    /**
     * Get information about a tag object.
     * @param $tagName
     * @param bool $auth
     * @return string
     */
    public function getTags($tagName, $auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['tags'], $tagName, $this->getAuthUrlParam($auth));
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Get a list of recently tagged media.
     * @param $tagName
     * @param bool $auth
     * @param string $maxId
     * @param string $minId
     * @return string
     */
    public function getRecentTags($tagName, $auth=false, $maxId = '', $minId = '') {
        $endpointUrl = sprintf($this->_endpointUrls['tags_recent'], $tagName, $this->getAuthUrlParam($auth), $maxId, $minId);
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Search for tags by name - results are ordered first as an exact match, then by popularity.
     * @param $tagName
     * @param bool $auth
     * @return string
     */
    public function searchTags($tagName,$auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['tags_search'], urlencode($tagName), $this->getAuthUrlParam($auth));
        return $this->getJsonAnswer($endpointUrl);
    }

//////LOCATION
    /**
     * Get information about a location.
     * @param $id
     * @param bool $auth
     * @return string
     */
    public function getLocation($id,$auth=false) {
        $endpointUrl = sprintf($this->_endpointUrls['locations'], $id, $this->getAuthUrlParam($auth));
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Get a list of recent media objects from a given location.
     * @param $id
     * @param bool $auth
     * @param string $maxId
     * @param string $minId
     * @param string $maxTimestamp
     * @param string $minTimestamp
     * @return string
     */
    public function getLocationRecentMedia($id, $auth=false,$maxId = '', $minId = '', $maxTimestamp = '', $minTimestamp = '') {
        $endpointUrl = sprintf($this->_endpointUrls['locations_recent'], $id, $this->getAuthUrlParam($auth), $maxId, $minId, $maxTimestamp, $minTimestamp);
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Search for a location by name and geographic coordinate.
     * @param $lat
     * @param $lng
     * @param $auth
     * @param string $foursquareId
     * @param string $distance
     * @return string
     */
    public function searchLocation($lat, $lng, $auth, $foursquareId = '', $distance = '') {
        $endpointUrl = sprintf($this->_endpointUrls['locations_search'], $lat, $lng, $this->getAuthUrlParam($auth),$foursquareId, $distance);
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Get recent media from a geography subscription that you created
     * @param $id
     * @param bool $auth
     * @param string $count
     * @param string $min_id
     * @return string
     */

    public function geographiesRecent($id, $auth=false, $count='', $min_id = '') {
        $endpointUrl = sprintf($this->_endpointUrls['geographies_recent'], $id, $this->getAuthUrlParam($auth), $count, $min_id);
        return $this->getJsonAnswer($endpointUrl);
    }

    /**
     * Parse response from {@link makeRequest} in json format and check OAuth errors.
     * @param $response
     * @return mixed
     * @throws CHttpException
     */
    protected function parseJson($response) {
        try {
            //true param for converting elems in associative arrays
            $result = json_decode($response,true);
            $error = $this->fetchJsonError($result);
            if (!isset($result)) {
                throw new CHttpException(400, Yii::t('InstagramApp', 'Invalid response format'));
            }
            else if (isset($error)) {
                throw new CHttpException(500, Yii::t('InstagramApp error:'.$error['code'], $error['message']));
            }
            else
                return $result;
        }
        catch(Exception $e) {
            throw new CHttpException(500, Yii::t('InstagramApp error:'.$e->getCode(), $e->getMessage()));
        }
    }

    /**
     * Returns the error info from json.
     * @param stdClass $json the json response.
     * @return array the error array with 2 keys: code and message. Should be null if no errors.
     */
    protected function fetchJsonError($json) {
        if (isset($json->error)) {
            return array(
                'code' => 500,
                'message' => 'Unknown error occurred.',
            );
        }
        else
            return null;
    }


}
