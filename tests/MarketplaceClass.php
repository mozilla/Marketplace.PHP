<?php
require_once 'PHPUnit/Autoload.php';
require_once 'classes/marketplace.php';

class MarketplaceTest extends PHPUnit_Framework_TestCase
{
    /**
     * validate_manifest should throw exception if not authorized
     */
    public function testExceptionInValidateManifest()
    {
        $s_key = 'fake_key';
        $s_secret = 'fake_security';
        $marketplace = new Marketplace($s_key, $s_secret, 'marketplace-dev.allizom.org');
        $response = $marketplace->validate_manifest('http://example.com');
        var_dump($response);
    }

    public function testGetUrls()
    {
        $marketplace = new Marketplace('key', 'secret',
            'domain', 'http', 80, '/prefix');
        $r_urls = new ReflectionProperty('Marketplace', 'urls');
        $r_urls->setAccessible(true);
        $urls = $r_urls->getValue($marketplace);
        $get_urls = new ReflectionMethod('Marketplace', 'get_url');
        $get_urls->setAccessible(true);
        foreach ($urls as $key => $path) {
            $this->assertEquals(
                $get_urls->invokeArgs($marketplace, array($key)),
                'http://domain:80/prefix/api'.$urls[$key]);
        }
    }
}
