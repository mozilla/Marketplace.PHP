<?php
/**
 * This is a demonstration of a falling attempt to authorize in the marketplace
 */

$consumer_key = 'your_key';
$consumer_secret = 'your_secret';
$url = 'https://marketplace-dev.allizom.org:443/api/apps/validation/';
$manifest = 'http://mozilla.github.com/MarketplaceClientExample/manifest.webapp';

$oauth = new OAuth($consumer_key, $consumer_secret, OAUTH_SIG_METHOD_HMACSHA1);

$body = json_encode(array('manifest' => $manifest));

$OA_header = $oauth->getRequestHeader('POST', $url);

// idea to use curl from
// http://api.figshare.com/docs/demo_php.html
$headers = array("Content-Type: application/json", "Authorization: $OA_header");

$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => $url,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_SSL_VERIFYPEER => 0));

$response = curl_exec($ch);

var_dump(curl_getinfo($ch));

curl_close($ch);

var_dump($response);

// old (cleaner) way which for some reason isn't working 
// $headers = array(
//     'Accept' => 'application/json',
//     'Content-Type' => 'application/json');
// 
// $oauth->fetch($url, $params, 'POST', $headers);
