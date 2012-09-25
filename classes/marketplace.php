<?php 
/**
 * A class to interact with Mozilla Marketplace API
 *
 * For full spec please read Marketplace API documentation
 * https://github.com/mozilla/zamboni/blob/master/docs/topics/api.rst
 */


class Marketplace {

    private $token;
    private $oauth;
    private $urls = array(
        'validate' => '/apps/validation/',
        'validation_result' => '/apps/validation/%s/',
        'create' => '/apps/app/',
        'app' => '/apps/app/%s/',
        'create_screenshot' => '/apps/preview/?app=%s',
        'screenshot' => '/apps/preview/%s/',
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
        $prefix='') 
    {
        $this->domain = $domain;
        $this->protocol = $protocol;
        $this->port = $port;
        $this->prefix = $prefix;
        $this->oauth = new OAuth($consumer_key, $consumer_secret,
            OAUTH_SIG_METHOD_HMACSHA1);
        $this->oauth->enableDebug();
        $this->oauth->enableRedirects();
        $this->oauth->disableSSLChecks();
    }

    /**
     * Fetch data from the JSON API
     * TODO: make it private
     *         it's not private for testing reasons only
     * 
     * @param    string        $method
     * @param    string        $url    
     * @param    array        $data        data to send to the API, it's gonna
     *                                    be JSON encoded
     * @return  mixed        response from the API
     */
    function fetch($method, $url, $data=NULL) 
    {
        if ($data) {
            $params = array('body' => json_encode($data));
        } else {
            $params = array();
        }
        return $this->oauth->fetch($url, $params, $method, 
            array(
                "Accept" => "application/json", 
                "Content-Type" => "application/json"));
    }

    /**
     * Creates a full URL to the API using urls dict
     */
    private function get_url($key) 
    {
        return $this->protocol.'://'.$this->domain.':'.$this->port
            .'/'.$this->prefix.'/api'.$this->urls[$key];
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
