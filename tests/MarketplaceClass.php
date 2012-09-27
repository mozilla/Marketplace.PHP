<?php
require_once 'PHPUnit/Autoload.php';
require_once 'classes/marketplace.php';
require_once 'classes/curl.php';

// Note: valid manifest url:
// http://mozilla.github.com/MarketplaceClientExample/manifest.webapp

class MarketplaceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Prepare the stub to be injected into marketplace object
     */
    private function getCurlMock() 
    {
        return $this->getMockBuilder('Curl')
            ->setConstructorArgs(array('http://example.com', 'GET', array(), ''))
            ->setMethods(array('fetch'))
            ->getMock();
    }
    private function getCurlMockFetchReturn($return_value)
    {
        $stub = $this->getCurlMock();
        $stub->expects($this->any())
                 ->method('fetch')
                 ->will($this->returnValue($return_value));
        return $stub;
    }

    /**
     * On every Http status code >= 400 Marketplace::fetch is throwing 
     * an exception
     *
     * @expectedException       Exception
     * @expectedExceptionCode   401
     */
    public function testUnauthorizedAccess()
    {
        $e_msg = '{"reason": "Error with OAuth headers"}';
        $stub = $this->getCurlMockFetchReturn(array('status_code' => 401, 'body' => $e_msg));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $result = $marketplace->is_manifest_valid('abc');
    }

    /**
     * invalid manifest should return success == false and list of 
     * validation errors
     */
    public function testInvalidManifest()
    {
        $response_body = '{"id": "abcdefghijklmnopqrstuvwxyz123456", "manifest": "http://example.com", "processed": true, "resource_uri": "/api/apps/validation/abcdefghijklmnopqrstuvwxyz123456/", "valid": false, "validation": {"errors": 1, "messages": [{"message": "No manifest was found at that URL. Check the address and make sure the manifest is served with the HTTP header \"Content-Type: application/x-web-app-manifest+json\".", "tier": 1, "type": "error"}], "success": false}}';

        $stub = $this->getCurlMockFetchReturn(array('status_code' => 201, 'body' => $response_body));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $response = $marketplace->validate_manifest('http://example.com');
        $this->assertEquals($response['success'], true);
        $this->assertEquals($response['valid'], false);
        $this->assertEquals(count($response['errors']), 1);
        $this->assertEquals($response['id'], 'abcdefghijklmnopqrstuvwxyz123456');
        $this->assertEquals($response['resource_uri'], 
            '/api/apps/validation/abcdefghijklmnopqrstuvwxyz123456/');
    }
    public function testValidManifest()
    {
        $response_body = '{"id": "abcdefghijklmnopqrstuvwxyz123456", "manifest": "http://example.com", "processed": true, "resource_uri": "/api/apps/validation/abcdefghijklmnopqrstuvwxyz123456/", "valid": true}';
        $stub = $this->getCurlMockFetchReturn(array('status_code' => 201, 'body' => $response_body));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $response = $marketplace->validate_manifest('http://example.com');
        $this->assertEquals($response['success'], true);
        $this->assertEquals($response['valid'], true);
        $this->assertEquals($response['id'], 'abcdefghijklmnopqrstuvwxyz123456');
        $this->assertEquals($response['resource_uri'], 
            '/api/apps/validation/abcdefghijklmnopqrstuvwxyz123456/');
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
