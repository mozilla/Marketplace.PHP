<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 * @author Steve Gricci <steve@gricci.org>
 * @author Piotr Zalewa <piotr@zalewa.info>
 * @author Ruslan Bekenev <furyinbox@gmail.com>
 */

namespace Mozilla\Marketplace;

use Mozilla\Marketplace\PHP\Register;

/**
 * A class to interact with Mozilla Marketplace API
 *
 * For API full spec please read Marketplace API documentation
 * https://github.com/mozilla/zamboni/blob/master/docs/topics/api.rst
 */
class Client
{
    /**
     * @var Connection $connection
     */
    private $connection;

    /**
     * @var Target $target
     */
    private $target;

    /**
     * @var Credential $credential
     */
    private $credential;

    public function __construct()
    {
        new Register;
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        if ( ! empty($this->credential)) {
            $connection->setCredential($this->credential);
        }

        $connection->setHttpClient(new \Guzzle\Service\Client);

        $this->connection = $connection;
    }

    /**
     * @param Target $target
     */
    public function setTarget(Target $target)
    {
        $this->target = $target;
    }

    /**
     * @param Credential $credential
     */
    public function setCredential(Credential $credential)
    {
        $this->credential = $credential;
    }

    /**
     * Marketplace is downloading the manifest from given location and
     * validates it.
     *
     * @param  string  $manifest_url
     * @return array   success (bool)
     *                 id (string)
     *                 resource_uri (string)
     */
    public function validateManifest($manifest_url)
    {
        $url  = $this->target->getUrl(Target::URL_VALIDATE);
        $data = array('manifest' => $manifest_url);

        $response = $this->connection->fetch($url, 'post', $data);
        $return   = array(
            'success'      => true,
            'valid'        => $response->valid,
            'id'           => $response->id,
            'resource_uri' => $response->resource_uri
        );

        if ($response->valid === false) {
            $errors = array();

            foreach ($response->validation->messages as $msg) {
                if ($msg->type === 'error') {
                    $errors[] = $msg->message;
                }
            }

            $return['errors'] = $errors;
        }

        return $return;
    }

    /**
     * Check if manifest issued for validation is valid. However validate
     * manifest is currently working synchronously, and is giving
     * the validation message in the response, it might change to
     * asynchronus as it used to be before. This function will then
     * become necessary.
     *
     * @param   integer  $manifest_id
     * @return  array    processed (bool)
     *                   valid (bool)
     */
    public function isManifestValid($manifest_id)
    {
        $url = $this->target->getUrl(Target::URL_VALIDATION_RESULT, array('id' => $manifest_id));
        $response = $this->connection->fetch($url, 'get', null);

        return array(
            'success'   => true,
            'valid'     => $response->valid,
            'processed' => $response->processed);
    }

    /**
     * extract webapp information from data provided in the response
     * body
     *
     * @param $data
     * @return array
     */
    private function getInfoFromData($data)
    {
        return array(
            'id'             => $data->id,
            'resource_uri'   => $data->resource_uri,
            'slug'           => $data->slug,
            'name'           => $data->name,
            'status'         => $data->status,
            'categories'     => $data->categories,
            'summary'        => $data->summary,
            'description'    => $data->description,
            'premium_type'   => $data->premium_type,
            'homepage'       => $data->homepage,
            'device_types'   => $data->device_types,
            'privacy_policy' => $data->privacy_policy,
            'previews'       => $data->previews,
            'support_email'  => $data->support_email,
            'support_url'    => $data->support_url);
    }

    /**
     * Order webapp creation. Marketplace will download the webapp info from
     * location provided in the manifest.
     *
     * @param $manifest_id
     * @return array
     */
    public function createWebapp($manifest_id)
    {
        $url      = $this->target->getUrl(Target::URL_CREATE);
        $response = $this->connection->fetch($url, 'post', array('manifest' => $manifest_id));
        $return   = array('success' => true);

        return array_merge($return, $this->getInfoFromData($response));
    }

    /**
     * Update webapp
     *
     * @param $webapp_id
     * @param $data
     * @return array
     * @throws \InvalidArgumentException
     */
    public function updateWebapp($webapp_id, $data)
    {
        $required = array('name', 'summary', 'categories', 'privacy_policy',
            'support_email', 'device_types', 'payment_type');

        $diff = array_diff($required, array_keys($data));

        if ($diff) {
            throw new \InvalidArgumentException(
                'Following keys are required: '. implode(', ', $diff));
        }

        $url = $this->target->getUrl(Target::URL_APP, array('id' => $webapp_id));

        $this->connection->fetch($url, 'out', $data);

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
        $url      = $this->target->getUrl(Target::URL_APP, array('id' => $webapp_id));
        $response = $this->connection->fetch($url, 'get', null);

        return array_merge(
            array('success' => true),
            $this->getInfoFromData($response)
        );
    }

    /**
     * Remove webapp from Marketplace
     * Not implemented yet
     *
     * @param $webapp_id
     * @throws \Exception
     */
    public function removeWebapp($webapp_id)
    {
        throw new \Exception("Not Implemented");
    }

    /**
     * extract screenshot information from data provided in the response
     * body
     *
     * @param $data
     * @return array
     */
    private function getScreeshotInfoFromData($data)
    {
        return array(
            'id'            => $data->id,
            'resource_uri'  => $data->resource_uri,
            'image_url'     => $data->image_url,
            'thumbnail_url' => $data->thumbnail_url,
            'filetype'      => $data->filetype);
    }

    /**
     * Add screenshot to a webapp
     *
     * @param $webapp_id
     * @param $handle
     * @param int $position
     * @return array
     * @throws WrongFileException
     */
    public function addScreenshot($webapp_id, $handle, $position = 1)
    {
        $content         = stream_get_contents($handle);
        $content_encoded = base64_encode($content);
        $imginfo         = \getimagesizefromstring($content);

        if ( ! $imginfo) {
            throw new WrongFileException("Wrong file");
        }

        $url  = $this->target->getUrl(Target::URL_CREATE_SCREENSHOT, array('id' => $webapp_id));
        $data = array(
            'position' => $position,
            'file'     => array(
                'type' => $imginfo['mime'],
                'data' => $content_encoded));

        $response = $this->connection->fetch($url, 'post', $data);

        return array_merge(
            array('success' => true),
            $this->getScreeshotInfoFromData($response)
        );
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
        $url = $this->target->getUrl(Target::URL_SCREENSHOT, array('id' => $screenshot_id));
        $response = $this->connection->fetch($url, 'get', null);

        return array_merge(
            array('success' => true),
            $this->getScreeshotInfoFromData($response)
        );
    }

    /**
     * Remove screenshot from Marketplace
     *
     * @param  string $screenshot_id
     * @return array  success (bool)
     *                        message (string)
     */
    public function deleteScreenshot($screenshot_id)
    {
        $url = $this->target->getUrl(Target::URL_SCREENSHOT, array('id' => $screenshot_id));

        $this->connection->fetch($url, 'delete', null);

        return array('success' => true);
    }

    /**
     * Get list of available categories
     *
     * @param  int   $limit
     * @param  int   $offset
     * @return array categories with the ids
     */
    public function getCategoryList($limit = 20, $offset = 0)
    {
        $url = $this->target->getUrl(Target::URL_CATEGORIES, array(
                'limit' => $limit,
                'offset' => $offset)
        );

        $response = $this->connection->fetch($url, 'get', null);

        $return = array(
            'success' => true,
            'pager' => array(
                'limit' => $response->meta->limit,
                'offset' => $response->meta->offset,
                'total_count' => $response->meta->total_count,
                'next' => $response->meta->next,
                'previous' => $response->meta->previous),
            'categories' => array()
        );

        foreach ($response->objects as $category) {
            $return['categories'][] = array(
                'id'           => $category->id,
                'resource_uri' => $category->resource_uri,
                'name'         => $category->name
            );
        }

        return $return;
    }
}
