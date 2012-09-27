<?php 
/**
 * A class to interact with Mozilla Marketplace API
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
        'categories' => '/apps/category/');

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
        $this->oauth = new OAuth($consumer_key, $consumer_secret,
            OAUTH_SIG_METHOD_HMACSHA1);
        // adding a stub to the system
        $this->curl = $curl;
    }

    /**
     * Fetch data from the JSON API
     * 
     * @param    string        $method      uppercase REST method name 
     *                                      (POST,GET,DELETE,UPDATE)
     * @param    string        $url    
     * @param    array        $data         data to send to the API, it's gonna
     *                                      be JSON encoded
     * @return  mixed        response from the API
     */
    private function fetch($method, $url, $data=NULL) 
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

        // to allow testing
        if ($this->curl) {
            $curl = $this->curl;
        } else {
            $curl = new Curl($url, $method, $headers, $body);
        }
        $response = $curl->fetch();
        // throw on 40x and 50x errors
        if ($response['status_code'] >= 400) {
            try {
                $body = json_decode($response['body']);
                $reason = $body->reason;
            } catch (Exception $e) {
                $reason = $response['body'];
            }
            throw new Exception($reason, $response['status_code']);
        }
        return $response;
    }

    /**
     * Creates a full URL to the API using urls dict
     */
    private function get_url($key) 
    {
        return $this->protocol.'://'.$this->domain.':'.$this->port
            .$this->prefix.'/api'.$this->urls[$key];
    }

    /**
     * Order manifest validation
     *
     * @param    string        $manifest_url
     * @return    integer        manifest id     
     */
    public function validate_manifest($manifest_url) 
    {
        $url = $this->get_url('validate');
        $data = array('manifest' => $manifest_url);
        $response = $this->fetch('POST', $url, $data);
        $data = json_decode($response['body']);
        // the expected status code is 201, everything else is an error
        if ($response['status_code'] !== 201) {
            return array(
                'status_code' => $response['status_code'],
                'success' => false,
                'error' => $data->reason);
        } 
        $ret = array(
            'id' => $data->id,
            'resource_uri' => $data->resource_uri);
        if ($data->valid === false) {
            $errors = array();
            foreach ($data->validation->messages as $msg) {
                if ($msg->type === 'error') {
                    $errors[] = $msg->message;
                }
            }
            return array_merge(
                $ret,
                array(
                    'success' => false,
                    'errors' => $errors));
        }
        return array(
            'success' => true);
    }

    /**
     * Check if manifest is valid
     *
     * @param    integer        $manifest_id
     * @return  array        processed => bool
     *                        valid => bool
     */
    public function is_manifest_valid($manifest_id) 
    {
        $url = str_replace('{id}', $manifest_id, $this->get_url('validation_result'));
        $response = $this->fetch('GET', $url);
        $data = json_decode($response['body']);
        if ($response['status_code'] !== 201) {
            return array(
                'status_code' => $response['status_code'],
                'success' => false,
                'error' => $data->reason);
        } 
    }

    /** 
     * Order webapp creation
     *
     * @param    integer        $manifest_id
     * @return    array        success (bool)
     *                        id (string)                webapp id
     *                        resource_uri (string)    
     *                        slug (string)            unique name
     */
    public function create_webapp($manifest_id) 
    {
    }

    /**
     * Update webapp
     *
     * @param    string        $webapp_id
     * @param    array        $data some keys are required:
     *                            name        title of the webapp (max 127 char)
     *                            summary        (max 255 char)
     *                            categories    a list of webapp category ids
     *                                        at least 2 are required
     *                            support_email    
     *                            device_type    a list of the device types
     *                                        at least on of 'desktop', 'phone',
     *                                        'tablet'
     *                            payment_type 'free'
     * @return    array        success (bool)
     *                        message (string)
     */
    public function update_webapp($webapp_id, $data) 
    {
    }

    /**
     * View details of a webapp
     *
     * @param    string        $webapp_id
     * @return    array        success
     *                        other fields defining a webapp
     */
    public function get_webapp_info($webapp_id) 
    {
    }

    /**
     * Remove webapp from Marketplace
     *
     * @param    string        $webapp_id
     * @return    array        success (bool)
     *                        message (string)
     */
    public function remove_webapp($webapp_id) 
    {
    }

    /**
     * Add screenshot to a webapp
     *
     * @param    string        $webapp_id
     * @param    string        $filepath
     * @param    integer        $position        on which position place the image
     */
    public function add_screenshot($webapp_id, $filepath, $position = 1) 
    {
    }

    /**
     * Get info about screenshot or video
     *
     * @param    string    $screenshot_id
     * @return    array    success (bool)
     *                    other fields defining a screenshot
     */
    public function get_screenshot_info($screenshot_id) 
    {
    }

    /**
     * Remove screenshot from Marketplace
     *
     * @param    string    $screenshot_id
     * @return    array        success (bool)
     *                        message (string)
     */
    public function delete_screenshot($screenshot_id) 
    {
    }

    /**
     * Get list of available categories
     *
     * @return    array    categories with the ids
     */
    public function get_category_list() 
    {
    }
};
