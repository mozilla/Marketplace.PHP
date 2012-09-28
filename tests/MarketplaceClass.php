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
        $stub = $this->getCurlMockFetchReturn(
            array('status_code' => 401, 'body' => $e_msg));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $result = $marketplace->isManifestValid('abc');
    }

    /**
     * fetch throws on unexpected status_code
     *
     * @expectedException           Exception
     * @expectedExceptionCode       204
     * @expectedExceptionMessage    Test
     */
    public function testFetchWrongStatusCode()
    {
        $e_msg = '{"reason": "Test"}';
        $stub = $this->getCurlMockFetchReturn(
            array('status_code' => 204, 'body' => $e_msg));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $fetch = new ReflectionMethod('Marketplace', 'fetch');
        $fetch->setAccessible(true);
        // expecting status_code to equal 200, but sending 204 instead
        $fetch->invokeArgs($marketplace, array('GET', 'http://example.com/', NULL, 200));
    }
    /**
     * invalid manifest should return success == false and list of 
     * validation errors
     */
    public function testInvalidManifest()
    {
        $response_body = '{'
            .'"id": "abcdefghijklmnopqrstuvwxyz123456", '
            .'"manifest": "http://example.com", '
            .'"processed": true, '
            .'"resource_uri": "/api/apps/validation/abcdefghijklmnopqrstuvwxyz123456/", '
            .'"valid": false, '
            .'"validation": {'
                .'"errors": 1, '
                .'"messages": ['
                    .'{"message": "No manifest was found at that URL. Check '
                                .'the address and make sure the manifest is '
                                .'served with the HTTP header '
                                .'\"Content-Type: application/x-web-app-manifest+json\".", '
                                .'"tier": 1, "type": "error"}], '
                .'"success": false}}';

        $stub = $this->getCurlMockFetchReturn(
            array('status_code' => 201, 'body' => $response_body));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $response = $marketplace->validateManifest('http://example.com');
        $this->assertEquals($response['success'], true);
        $this->assertEquals($response['valid'], false);
        $this->assertEquals(count($response['errors']), 1);
        $this->assertEquals($response['id'], 'abcdefghijklmnopqrstuvwxyz123456');
        $this->assertEquals($response['resource_uri'], 
            '/api/apps/validation/abcdefghijklmnopqrstuvwxyz123456/');
    }
    public function testValidManifest()
    {
        $response_body = '{'
            .'"id": "abcdefghijklmnopqrstuvwxyz123456", '
            .'"manifest": "http://example.com", '
            .'"processed": true, '
            .'"resource_uri": "/api/apps/validation/abcdefghijklmnopqrstuvwxyz123456/", '
            .'"valid": true}';
        $stub = $this->getCurlMockFetchReturn(
            array('status_code' => 201, 'body' => $response_body));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $response = $marketplace->validateManifest('http://example.com');
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
        $get_urls = new ReflectionMethod('Marketplace', 'getUrl');
        $get_urls->setAccessible(true);
        foreach ($urls as $key => $path) {
            $this->assertEquals(
                $get_urls->invokeArgs($marketplace, array($key)),
                'http://domain:80/prefix/api'.$urls[$key]);
        }
        $url = $get_urls->invokeArgs($marketplace,
            array('categories', array( 'limit' => 20, 'offset' => 1)));
        $this->assertEquals(
            'http://domain:80/prefix/api/apps/category/?limit=20&offset=1', 
            $url);
    }

    public function testcreateApp()
    {
        $response_body = '{'
            .'"categories": [], '
            .'"description": null, '
            .'"device_types": [], '
            .'"homepage": null, '
            .'"id": "123456", '
            .'"manifest": "abcdefghijklmnopqrstuvwxyz123456", '
            .'"name": "MozillaBall", '
            .'"premium_type": "free", '
            .'"previews": [], '
            .'"privacy_policy": null, '
            .'"resource_uri": "/api/apps/app/123456/", '
            .'"slug": "mozillaball-1", '
            .'"status": 0, '
            .'"summary": "Exciting Open Web development action!", '
            .'"support_email": null, '
            .'"support_url": null}';
        $stub = $this->getCurlMockFetchReturn(
            array('status_code' => 201, 'body' => $response_body));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $response = $marketplace->createWebapp("abcdefghijklmnopqrstuvwxyz123456");
        $this->assertEquals($response['success'], true);
        $this->assertEquals($response['id'], '123456');
        $this->assertEquals($response['resource_uri'], '/api/apps/app/123456/');
    }

    /**
     * @expectedException           InvalidArgumentException
     * @expectedExceptionMessage    Following keys are required: summary, categories, privacy_policy, support_email, device_types, payment_type
     */ 
    public function testFailedValidationUpdateWebapp() 
    {
        $marketplace = new Marketplace('key', 'secret');
        $marketplace->updateWebapp(123456, array('name' => 'TestName'));
    }

    public function testUpdateWebapp() 
    {
        $stub = $this->getCurlMockFetchReturn(array('status_code' => 202, 'body' => ''));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $response = $marketplace->updateWebapp(123456, array(
            'name' => 'TestName',
            'summary' => '',        // not empty string required for real connection
            'categories' => '',     // array required for real connection
            'privacy_policy' => '', // not empty string required
            'support_email' => '',  // valid email required 
            'device_types' => '',   // array required
            'payment_type' => '',   // not empty string required
        ));
        $this->assertTrue($response['success']);
    }

    public function testGetInfo() 
    {
        $response_body = '{'
            .'"categories": [], '
            .'"description": null, '
            .'"device_types": [], '
            .'"homepage": null, '
            .'"id": "123456", '
            .'"name": "MozillaBall", '
            .'"premium_type": "free", '
            .'"previews": [], '
            .'"privacy_policy": null, '
            .'"resource_uri": "/api/apps/app/123456/", '
            .'"slug": "mozillaball-6", '
            .'"status": 0, '
            .'"summary": "Exciting Open Web development action!", '
            .'"support_email": null, '
            .'"support_url": null}';
        $stub = $this->getCurlMockFetchReturn(
            array('status_code' => 200, 'body' => $response_body));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $response = $marketplace->getWebappInfo(123456);
        $this->assertEquals($response['success'], true);
        $this->assertEquals($response['id'], '123456');
        $this->assertEquals($response['resource_uri'], '/api/apps/app/123456/');
    }

    /**
     * @expectedException           Exception
     * #expectedExceptionMessage    Not Implemented
     */
    public function testRemoveWebappNotImplemented() 
    {
        $marketplace = new Marketplace('key', 'secret');
        $marketplace->removeWebapp(123);
    }

    public function testUploadScreenshot() 
    {
        $response_body = '{'
            .'"filetype": "image/png", '
            .'"id": "12345", '
            .'"image_url": "https://marketplace-dev-cdn.allizom.org/'
                          .'img/uploads/previews/full/12/12345?modified=1348819526", '
            .'"position": 1, '
            .'"resource_uri": "/api/apps/preview/12345/", '
            .'"thumbnail_url": "https://marketplace-dev-cdn.allizom.org/'
                              .'img/uploads/previews/thumbs/12/12345?modified=1348819526"}';
        $img = "tests/mozilla.png";
        $handle = fopen($img, 'r');
        $stub = $this->getCurlMockFetchReturn(
            array('status_code' => 201, 'body' => $response_body));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $response = $marketplace->addScreenshot(12345, $handle);
        fclose($handle);
        $this->assertEquals($response['id'], 12345);
        $this->assertEquals($response['resource_uri'], "/api/apps/preview/12345/");
    }

    /**
     * @expectedException           Exception
     * @expectedExceptionMessage    Wrong file
     */
    public function testUploadWrongFile() 
    {
        $img = "tests/MarketplaceClass.php";
        $handle = fopen($img, 'r');
        $marketplace = new Marketplace('key', 'secret');
        $response = $marketplace->addScreenshot(12345, $handle);
        fclose($handle);
    }

    public function testGetScreenshotInfo() 
    {
        $response_body = '{'
            .'"filetype": "image/png", '
            .'"id": "12345", '
            .'"image_url": "https://marketplace-dev-cdn.allizom.org/'
                          .'img/uploads/previews/full/12/12345?modified=1348819526", '
            .'"resource_uri": "/api/apps/preview/12345/", '
            .'"thumbnail_url": "https://marketplace-dev-cdn.allizom.org/'
                              .'img/uploads/previews/thumbs/12/12345?modified=1348819526"}';
        $stub = $this->getCurlMockFetchReturn(
            array('status_code' => 200, 'body' => $response_body));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $result = $marketplace->getScreenshotInfo(12345);
        $this->assertEquals($result['id'], 12345);
    }

    public function testDeleteScreenshot() 
    {
        $stub = $this->getCurlMockFetchReturn(array('status_code' => 204, 'body' => ''));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $response = $marketplace->deleteScreenshot(12345);
        $this->assertEquals($response['success'], true);
    }

    public function testGetCategories() 
    {
        $response_body = '{'
            .'"meta": {'
                .'"limit": 20, '
                .'"next": null, '
                .'"offset": 0, '
                .'"previous": null, '
                .'"total_count": 6}, ' 
            .'"objects": ['
                .'{"id": "154", '
                    .'"name": "Business", '
                    .'"resource_uri": "/api/apps/category/154/"}, '
                .'{"id": "155", '
                    .'"name": "Games", '
                    .   '"resource_uri": "/api/apps/category/155/"}, '
                .'{"id": "156", '
                    .'"name": "Music", '
                    .'"resource_uri": "/api/apps/category/156/"}, '
                .'{"id": "160", '
                    .'"name": "Travel", '
                    .'"resource_uri": "/api/apps/category/160/"}, '
                .'{"id": "163", '
                    .'"name": "Education", '
                    .'"resource_uri": "/api/apps/category/163/"}, '
                .'{"id": "169", '
                    .'"name": "Vivo", '
                    .'"resource_uri": "/api/apps/category/169/"}]}';
        $stub = $this->getCurlMockFetchReturn(
            array('status_code' => 200, 'body' => $response_body));
        $marketplace = new Marketplace('key', 'secret', 'domain', 'http', 443, '', $stub);
        $result = $marketplace->getCategoryList();
        $this->assertTrue($result['success']);
        $this->assertEquals(count($result['categories']), 6);
        $this->assertTrue(array_key_exists('id', $result['categories'][0]));
        $this->assertTrue(array_key_exists('resource_uri', $result['categories'][0]));
        $this->assertTrue(array_key_exists('name', $result['categories'][0]));
    }
}
