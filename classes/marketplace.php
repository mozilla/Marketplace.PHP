<?php 
/**
 * A class to interact with Mozilla Marketplace API
 *
 * TODO: discuss additional features
   * get user's app list (this might be done by search)
   * order manifest revalidation
   * order webapp download
 *
 * For full spec please read Marketplace API documentation
 * https://github.com/mozilla/zamboni/blob/master/docs/topics/api.rst
 */

require_once 'curl.php';

class Marketplace {

    private $token;
    private $oauth;
    private $urls = array(
        'validate' => '/apps/validation/',
        'validation_result' => '/apps/validation/{id}/',
        'create' => '/apps/app/',
        'app' => '/apps/app/{id}/',
        'create_screenshot' => '/apps/preview/?app={id}',
        'screenshot' => '/apps/preview/{id}/',
        'categories' => '/apps/category/?limit={limit}&offset={offset}'); 
    /**
     * Connect to the Marketplace and get the token
     *
     * @param    string        $domain
     * @param    string        $protocol
     * @param    integer        $port
     * @param    string        $prefix        a prefix to add before url path
     * @param    string        $consumer_key
     * @param    string        $consumer_secret
     */
    function __construct(
        $consumer_key, 
        $consumer_secret,
        $domain='marketplace.mozilla.org', 
        $protocol='https', 
        $port=443, 
        $prefix='',
        $curl=NULL) 
    {
        $this->domain = $domain;
        $this->protocol = $protocol;
        $this->port = $port;
        $this->prefix = $prefix;
        // prepare oAuth to get the authentication header
        $this->oauth = new OAuth($consumer_key, $consumer_secret,
            OAUTH_SIG_METHOD_HMACSHA1);
        // adding a stub to the system
        $this->curl = $curl;
    }

    /**
     * extract the reason from HTTP response
     */ 
    private static function _getErrorReason($response) 
    {
        try {
            // if tastypie gives a reason for error - return it
            $body = json_decode($response['body']);
            $reason = $body->reason;
        } catch (Exception $e) {
            $reason = $response['body'];
        }
        return $reason;
    }
    /**
     * Fetch data from the JSON API
     * 
     * @param    string        $method      uppercase REST method name 
     *                                      (POST,GET,DELETE,UPDATE)
     * @param    string        $url    
     * @param    array        $data         data to send to the API, it's gonna
     *                                      be JSON encoded
     * @param    int         $expected_status_code
     * @return  mixed        response from the API
     */
    private function fetch($method, $url, $data=NULL, $expected_status_code=NULL) 
    {
        if ($data) {
            $body = json_encode($data);
        } else {
            $body = '';
        }
        // TODO: consider not using the OAuth lib as it's used for
        //       getting the header only
        $OA_header = $this->oauth->getRequestHeader($method, $url);
        $headers = array(
            "Content-Type: application/json", 
            "Authorization: $OA_header",
            "Accept: application/json");

        // if stub provided - use it instead of Curl class new instance
        if ($this->curl) {
            $curl = $this->curl;
        } else {
            $curl = new Curl($url, $method, $headers, $body);
        }
        // fetch the response from API
        $response = $curl->fetch();
        // throw on 40x, 50x errors and unexpected status_code
        if ($response['status_code'] >= 400 
            OR $expected_status_code 
                AND $response['status_code'] != $expected_status_code) {
            $reason = $this::_getErrorReason($response);
            // XXX: find if better exception needed
            throw new Exception($reason, $response['status_code']);
        }
        return $response;
    }

    /**
     * Creates a full URL to the API using urls dict and simple 
     * replacement mechanism
     *
     * @param   string      $key
     * @param   array       $replace    replace key with value
     */
    private function getUrl($key, $replace=array()) 
    {
        $url = $this->protocol.'://'.$this->domain.':'.$this->port
            .$this->prefix.'/api'.$this->urls[$key];
        if ($replace) {
            $rep = array();
            // add curly brackets to the keys
            foreach($replace as $key => $value) {
                $rep['{'.$key.'}'] = $value;
            }
            $url = str_replace(
                array_keys($rep), 
                array_values($rep), 
                $url);
        }
        return $url;
    }

    /**
     * Marketplace is downloading the manifest from given location and
     * validates it.
     *
     * @param    string         $manifest_url
     * @return   array          success (bool)
     *                          id (string)
     *                          resource_uri (string)
     */
    public function validateManifest($manifest_url) 
    {
        $url = $this->getUrl('validate');
        $data = array('manifest' => $manifest_url);
        // the expected status_code is 201 as Marketplace is creating
        // a manifest item in the database
        $response = $this->fetch('POST', $url, $data, 201);
        $data = json_decode($response['body']);
        $ret = array(
            'success' => true,
            'valid' => $data->valid,
            'id' => $data->id,
            'resource_uri' => $data->resource_uri);
        // extract validation errors if manifest not valid
        if ($data->valid === false) {
            $errors = array();
            foreach ($data->validation->messages as $msg) {
                if ($msg->type === 'error') {
                    $errors[] = $msg->message;
                }
            }
            $ret['errors'] = $errors;
        }
        return $ret;
    }

    /**
     * Check if manifest issued for validation is valid. However validate 
     * manifest is currently working synchronously, and is giving 
     * the validation message in the response, it might change to 
     * asynchronus as it used to be before. This function will then
     * become necessary.
     *
     * @param    integer        $manifest_id
     * @return   array          processed (bool)
     *                          valid (bool)
     */
    public function isManifestValid($manifest_id) 
    {
        $url = $this->getUrl('validation_result', array('id' => $manifest_id));
        $response = $this->fetch('GET', $url, NULL, 200);
        $data = json_decode($response['body']);
        return array(
            'success' => true, 
            'valid' => $data->valid,
            'processed' => $data->processed);
    }

    /**
     * extract webapp information from data provided in the response 
     * body
     *
     * @param       stdClass    $data
     * #return      array
     */
    private static function _getInfoFromData($data) 
    {
        return array(
            'id' => $data->id,
            'resource_uri' => $data->resource_uri,
            'slug' => $data->slug,
            'name' => $data->name,
            'status' => $data->status,
            'categories' => $data->categories,
            'summary' => $data->summary,
            'description' => $data->description,
            'premium_type' => $data->premium_type,
            'homepage' => $data->homepage,
            'device_types' => $data->device_types,
            'privacy_policy' => $data->privacy_policy,
            'previews' => $data->previews,
            'support_email' => $data->support_email,
            'support_url' => $data->support_url);
    }

    /** 
     * Order webapp creation. Marketplace will download the webapp info from 
     * location provided in the manifest.
     *
     * @param    integer        $manifest_id
     * @return    array         success (bool)
     *                          id (string)                webapp id
     *                          resource_uri (string)    
     *                          slug (string)            unique name
     *                          ... other fields provided by 
     *                          _getInfoFromData
     */
    public function createWebapp($manifest_id) 
    {
        $url = $this->getUrl('create');
        $response = $this->fetch('POST', $url, array('manifest' => $manifest_id), 201);
        $data = json_decode($response['body']);
        $ret = array('success' => true);
        return array_merge($ret, $this::_getInfoFromData($data));
    }

    /**
     * Update webapp
     *
     * @param    string        $webapp_id
     * @param    array         $data some keys are required:
     *                             name          title of the webapp (max 127 char)
     *                             summary       (max 255 char)
     *                             categories    a list of webapp category ids
     *                                           at least 2 are required, use
     *                                           getCategoryList for ids
     *                             support_email    
     *                             device_types  a list of the device types
     *                                           at least on of 'desktop', 'phone',
     *                                           'tablet'
     *                             payment_type  'free'
     * @return    array        success (bool)
     *                         message (string)
     */
    public function updateWebapp($webapp_id, $data) 
    {
        // validate if all keys are in place
        $required = array('name', 'summary', 'categories', 'privacy_policy',
            'support_email', 'device_types', 'payment_type');
        $diff = array_diff($required, array_keys($data));
        if ($diff) {
            throw new InvalidArgumentException(
                'Following keys are required: '. implode(', ', $diff));
        }
        // PUT data
        $url = $this->getUrl('app', array('id' => $webapp_id));
        $response = $this->fetch('PUT', $url, $data, 202);
        return array('success' => true);
    }

    /**
     * Get details of a webapp
     *
     * @param    string        $webapp_id
     * @return    array        success
     *                        other fields defining a webapp
     */
    public function getWebappInfo($webapp_id) 
    {
        $url = $this->getUrl('app', array('id' => $webapp_id));
        $response = $this->fetch('GET', $url, NULL, 200);
        $data = json_decode($response['body']);
        $ret = ;
        return array_merge(
            array('success' => true), 
            $this::_getInfoFromData($data));
    }

    /**
     * Remove webapp from Marketplace
     * Not implemented yet
     *
     * @param    string        $webapp_id
     * @return    array        success (bool)
     *                        message (string)
     */
    public function removeWebapp($webapp_id) 
    {
        throw new Exception("Not Implemented");
    }

    /**
     * extract screenshot information from data provided in the response 
     * body
     *
     * @param       stdClass    $data
     * #return      array
     */
    private static function _getScreeshotInfoFromData($data) 
    {
        return array(
            'id' => $data->id,
            'resource_uri' => $data->resource_uri,
            'image_url' => $data->image_url,
            'thumbnail_url' => $data->thumbnail_url,
            'filetype' => $data->filetype);
    }

    /**
     * Add screenshot to a webapp
     *
     * TODO: add ability to send videos
     *
     * @param    string     $webapp_id
     * @param    resource   $handle     A file system pointer resource that 
     *                                  is typically created using fopen().
     * @param    integer    $position   on which position place the image
     */
    public function addScreenshot($webapp_id, $handle, $position = 1) 
    {
        // handle doesn't have to be a filesystem file.
        $content = stream_get_contents($handle);
        // content needs to be encoded to upload
        $content_encoded = base64_encode($content);
        $imginfo = getimagesizefromstring($content);

        if (!$imginfo) {
            // XXX: here determine the video miemetype before throwing
            throw new Exception("Wrong file");
        }
        
        $url = $this->getUrl('create_screenshot', array('id' => $webapp_id));
        $data = array(
            'position' => $position,
            'file' => array(
                'type' => $imginfo['mime'],
                'data' => $content_encoded));

        $response = $this->fetch('POST', $url, $data, 201);
        $body = json_decode($response['body']);
        return array_merge(
            array('success' => true), 
            $this::_getScreeshotInfoFromData($body));
    }

    /**
     * Get info about screenshot or video
     *
     * @param    string    $screenshot_id
     * @return    array    success (bool)
     *                    other fields defining a screenshot
     */
    public function getScreenshotInfo($screenshot_id) 
    {
        $url = $this->getUrl('screenshot', array('id' => $screenshot_id));
        $response = $this->fetch('GET', $url, NULL, 200);
        $body = json_decode($response['body']);
        return array_merge(
            array('success' => true), 
            $this->_getScreeshotInfoFromData($body));
    }

    /**
     * Remove screenshot from Marketplace
     * TODO: FIX IT! find the reason why it's not working on -dev
     *
     * @param    string    $screenshot_id
     * @return    array        success (bool)
     *                        message (string)
     */
    public function deleteScreenshot($screenshot_id) 
    {
        $url = $this->getUrl('screenshot', array('id' => $screenshot_id));
        $response = $this->fetch('DELETE', $url, NULL, 204);
        $body = json_decode($response['body']);
        return array('success' => true);
    }

    /**
     * Get list of available categories
     *
     * @param       int         $limit
     * @param       int         $offset   
     * @return      array       categories with the ids
     */
    public function getCategoryList($limit=20, $offset=0) 
    {
        $url = $this->getUrl('categories', array('limit' => $limit, 
                                                  'offset' => $offset));
        $response = $this->fetch('GET', $url, NULL, 200);
        $body = json_decode($response['body']);
        $ret = array(
            'success' => true,
            'pager' => array(
                'limit' => $body->meta->limit,
                'offset' => $body->meta->offset,
                'total_count' => $body->meta->total_count,
                'next' => $body->meta->next,
                'previous' => $body->meta->previous),
            'categories' => array());
        foreach ($body->objects as $cat) {
            $ret['categories'][] = array(
                'id' => $cat->id,
                'resource_uri' => $cat->resource_uri,
                'name' => $cat->name);
        }
        return $ret;
    }
}
