<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julão <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Plugin\Oauth\OauthPlugin;

/**
 * Set headers and return results from Marketplace oAuth API
 */
class Connection
{
    /**
     * @var \Guzzle\Http\Client
     */
    private $httpClient;

    /**
     * @var Credential
     */
    private $credential;

    /**
     * @param Credential $credential
     */
    public function setCredential(Credential $credential)
    {
        $this->credential = $credential;
    }

    /**
     * @param HttpClient $client
     */
    public function setHttpClient(HttpClient $client)
    {
        if ( ! empty($this->credential)) {
            $client->addSubscriber(
                new OauthPlugin(
                    array(
                        'consumer_key'    => $this->credential->getConsumerKey(),
                        'consumer_secret' => $this->credential->getConsumerSecret(),
                    )
                )
            );
        }

        $this->httpClient = $client;
    }

    /**
     * Fetch data from the Marketplace API
     *
     * @param $url
     * @param $method
     * @param null $body
     * @param array $headers
     * @return array|bool
     */
    public function fetch($url, $method,  $body = null, $headers = array())
    {
        if ( ! empty($headers)) {
            $this->httpClient->setDefaultOption('headers', $headers);
        }

        switch ($method) {
            case 'put':
            case 'post':
                $request = $this->httpClient->$method($url, $headers, $body);
                break;
            case 'delete':
            case 'get':
                $request = $this->httpClient->$method($url, $headers);
                break;
            default:
                return false;
                break;
        }

        try {
            $response = $request->send();
        } catch (\Exception $e) {
            return false;
        }

        return array(
            'status_code' => $response->getStatusCode(),
            'body'        => $response->json()
        );
    }
}
