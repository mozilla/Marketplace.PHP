<?php
/**
 * curl functions are global - we need to mock them for testing
 */

class Curl {
    private $ch;

    function __construct($url, $method, $headers, $body=NULL) 
    {
        $this->ch = curl_init();
        curl_setopt_array($this->ch, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0));

        if ($method === 'POST') {
            curl_setopt_array($this->ch, array(
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $body));
        }
    }

    function fetch()
    {
        $response = curl_exec($this->ch);
        $this->info = curl_getinfo($this->ch);
        curl_close($this->ch);
        if (!$response) return false;
        return array(
            'status_code' => $this->info['http_code'], 
            'body' => $response);
    }
}
