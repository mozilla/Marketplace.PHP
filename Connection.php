<?php
namespace Marketplace;

class WrongFileException extends \Exception { }

/**
 * Set headers and return results from Marketplace oAuth API
 */
class Connection
{
    private $oauth;

    /**
     * Create Oauth connection to Marketplace
     */
    public function __construct(
        $consumer_key,
        $consumer_secret)
    {
        // prepare oAuth to get the authentication header
        $this->oauth = new \OAuth($consumer_key, $consumer_secret,
            OAUTH_SIG_METHOD_HMACSHA1);
    }

    /**
     * fetch data from the Marketplace API
     *
     */
    public static function curl($url, $method, $headers, $body=NULL)
    {
        // preparring channel
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0));

        if ($method === 'POST') {
            curl_setopt_array($ch, array(
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $body));
        }
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if (!$response) return false;
        return array(
            'status_code' => $info['http_code'],
            'body' => $response);
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
        } catch (\Exception $e) {
            $reason = $response['body'];
        }

        return $reason;
    }

    /**
     * Fetch data from the JSON API
     *
     * @param string $method uppercase REST method name
     *                                      (POST,GET,DELETE,UPDATE)
     * @param string $url
     * @param array  $data data to send to the API, it's gonna
     *                                      be JSON encoded
     * @param  int   $expected_status_code
     * @return mixed response from the API
     */
    public function fetch($method, $url, $data=NULL, $expected_status_code=NULL)
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

        // fetch the response from API
        $response = $this::curl($url, $method, $headers, $body);
        // throw on 40x, 50x errors and unexpected status_code
        if ($response['status_code'] >= 400
            OR $expected_status_code
                AND $response['status_code'] != $expected_status_code) {
            $reason = $this::_getErrorReason($response);
            // XXX: find if better exception needed
            throw new FetchException($reason, $response['status_code']);
        }

        return $response;
    }
    public function fetchJSON($method, $url, $data=NULL, $expected_status_code=NULL)
    {
        $response = $this->fetch($method, $url, $data, $expected_status_code);
        $response['json'] = json_decode($response['body']);

        return $response;
    }
}
