<?php
namespace Marketplace;

class FetchException extends \Exception { }

class Client
{
    private $connection;
    public
        $domain,
        $protocol,
        $port,
        $prefix,
        $urls = array(
            'validate' => '/apps/validation/',
            'validation_result' => '/apps/validation/{id}/',
            'create' => '/apps/app/',
            'app' => '/apps/app/{id}/',
            'create_screenshot' => '/apps/preview/?app={id}',
            'screenshot' => '/apps/preview/{id}/',
            'categories' => '/apps/category/?limit={limit}&offset={offset}');

    /**
     * assign Marketplace/Connection class
     *
     * @param Connection $connection
     * @param string     $domain
     * @param string     $protocol
     * @param integer    $port
     * @param string     $prefix          a prefix to add before url path
     * @param string     $consumer_key
     * @param string     $consumer_secret
     */
    public function __construct(
        $connection,
        $domain='marketplace.mozilla.org',
        $protocol='https',
        $port=443,
        $prefix='')
    {
        $this->connection = $connection;
        $this->domain = $domain;
        $this->protocol = $protocol;
        $this->port = $port;
        $this->prefix = $prefix;
    }

    /**
     * Creates a full URL to the API using urls dict and simple
     * replacement mechanism
     *
     * @param string $key
     * @param array  $replace replace key with value
     */
    private function getUrl($key, $replace=array())
    {
        $url = $this->protocol.'://'.$this->domain.':'.$this->port
            .$this->prefix.'/api'.$this->urls[$key];
        if ($replace) {
            $rep = array();
            // add curly brackets to the keys
            foreach ($replace as $key => $value) {
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
     * extract webapp information from data provided in the response
     * body
     *
     * @param stdClass $data
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
     * @param  integer $manifest_id
     * @return array   success (bool)
     *                          id (string) webapp id
     *                          resource_uri (string)
     *                          slug (string) unique name
     *                          ... other fields provided by
     *                          _getInfoFromData
     */
    public function createWebapp($manifest_id)
    {
        $url = $this->getUrl('create');
        $response = $this->connection->fetchJSON('POST', $url, array('manifest' => $manifest_id), 201);
        $ret = array('success' => true);

        return array_merge($ret, $this::_getInfoFromData($response['json']));
    }

    /**
     * Update webapp
     *
     * @param string $webapp_id
     * @param array  $data      some keys are required:
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
     * @return array success (bool)
     *                         message (string)
     */
    public function updateWebapp($webapp_id, $data)
    {
        // validate if all keys are in place
        $required = array('name', 'summary', 'categories', 'privacy_policy',
            'support_email', 'device_types', 'payment_type');
        $diff = array_diff($required, array_keys($data));
        if ($diff) {
            throw new \InvalidArgumentException(
                'Following keys are required: '. implode(', ', $diff));
        }
        // PUT data
        $url = $this->getUrl('app', array('id' => $webapp_id));
        $response = $this->connection->fetch('PUT', $url, $data, 202);

        return array('success' => true);
    }

    /**
     * Get details of a webapp
     *
     * @param  string $webapp_id
     * @return array  success
     *                        other fields defining a webapp
     */
    public function getWebappInfo($webapp_id)
    {
        $url = $this->getUrl('app', array('id' => $webapp_id));
        $response = $this->connection->fetchJSON('GET', $url, NULL, 200);

        return array_merge(
            array('success' => true),
            $this::_getInfoFromData($response['json']));
    }

    /**
     * Remove webapp from Marketplace
     * Not implemented yet
     *
     * @param  string $webapp_id
     * @return array  success (bool)
     *                        message (string)
     */
    public function removeWebapp($webapp_id)
    {
        throw new \Exception("Not Implemented");
    }

    /**
     * extract screenshot information from data provided in the response
     * body
     *
     * @param stdClass $data
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
     * @param string   $webapp_id
     * @param resource $handle    A file system pointer resource that
     *                                  is typically created using fopen().
     * @param integer $position on which position place the image
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
            throw new WrongFileException("Wrong file");
        }

        $url = $this->getUrl('create_screenshot', array('id' => $webapp_id));
        $data = array(
            'position' => $position,
            'file' => array(
                'type' => $imginfo['mime'],
                'data' => $content_encoded));

        $response = $this->connection->fetchJSON('POST', $url, $data, 201);

        return array_merge(
            array('success' => true),
            $this::_getScreeshotInfoFromData($response['json']));
    }

    /**
     * Get info about screenshot or video
     *
     * @param  string $screenshot_id
     * @return array  success (bool)
     *                    other fields defining a screenshot
     */
    public function getScreenshotInfo($screenshot_id)
    {
        $url = $this->getUrl('screenshot', array('id' => $screenshot_id));
        $response = $this->connection->fetchJSON('GET', $url, NULL, 200);

        return array_merge(
            array('success' => true),
            $this->_getScreeshotInfoFromData($response['json']));
    }

    /**
     * Remove screenshot from Marketplace
     * TODO: FIX IT! find the reason why it's not working on -dev
     *
     * @param  string $screenshot_id
     * @return array  success (bool)
     *                        message (string)
     */
    public function deleteScreenshot($screenshot_id)
    {
        $url = $this->getUrl('screenshot', array('id' => $screenshot_id));
        $response = $this->connection->fetch('DELETE', $url, NULL, 204);

        return array('success' => true);
    }

    /**
     * Get list of available categories
     *
     * @param  int   $limit
     * @param  int   $offset
     * @return array categories with the ids
     */
    public function getCategoryList($limit=20, $offset=0)
    {
        $url = $this->getUrl('categories', array('limit' => $limit,
                                                  'offset' => $offset));
        $response = $this->connection->fetchJSON('GET', $url, NULL, 200);
        $body = $response['json'];
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
