<?php
/**
 * This is a demonstration of a falling attempt to authorize in the marketplace
 */

$consumer_key = 'aaa';
$consumer_secret = 'bbb';
$url = 'https://marketplace-dev.allizom.org:443/en-US/api/apps/validation/';
$manifest = 'http://mozilla.github.com/MarketplaceClientExample/manifest.webapp';

$oauth = new OAuth($consumer_key, $consumer_secret, OAUTH_SIG_METHOD_HMACSHA1);
$oauth->enableDebug();
$oauth->enableRedirects();
$oauth->disableSSLChecks();

$body = json_encode(array('manifest' => $url));
$params = array('body' => $body);

$headers = array(
    'Accept' => 'application/json',
    'Content-Type' => 'application/json');

$oauth->fetch($url, $params, 'POST', $headers);
