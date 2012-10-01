<?php
require_once 'PHPUnit/Autoload.php';
require_once 'Connection.php';
require_once 'Client.php';

// Note: valid manifest url:
// http://mozilla.github.com/MarketplaceClientExample/manifest.webapp

class ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * Prepare the Connection stub to be injected into Client object
     */
    private function getCurlMockFetchReturn($return_value)
    {
        $stub = $this->getMockBuilder('Marketplace\Connection')
            ->setConstructorArgs(array('key', 'secret'))
            ->setMethods(array('fetch'))
            ->getMock();
        $stub->expects($this->any())
                 ->method('fetch')
                 ->will($this->returnValue($return_value));
        return $stub;
    }
    public function testGetUrls()
    {
        $client = new Marketplace\Client(
            new Marketplace\Connection('key', 'secret'),
            'domain', 'http', 80, '/prefix');
        $get_url = new ReflectionMethod('Marketplace\Client', 'getUrl');
        $get_url->setAccessible(true);
        foreach ($client->urls as $key => $path) {
            $this->assertEquals(
                $get_url->invokeArgs($client, array($key)),
                'http://domain:80/prefix/api'.$client->urls[$key]);
        }
        $url = $get_url->invokeArgs($client,
            array('categories', array( 'limit' => 20, 'offset' => 1)));
        $this->assertEquals(
            'http://domain:80/prefix/api/apps/category/?limit=20&offset=1', 
            $url);
    }

    public function testCreateApp()
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
        $marketplace = new Marketplace\Client($stub, 'key', 'secret', 'domain', 'http', 443, '');
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
        $client = new Marketplace\Client(NULL);
        $client->updateWebapp(123456, array('name' => 'TestName'));
    }

    public function testUpdateWebapp() 
    {
        $stub = $this->getCurlMockFetchReturn(array('status_code' => 202, 'body' => ''));
        $client = new Marketplace\Client($stub, 'domain', 'http', 443, '');
        $response = $client->updateWebapp(123456, array(
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
        $client = new Marketplace\Client($stub, 'domain', 'http', 443, '');
        $response = $client->getWebappInfo(123456);
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
        $marketplace = new Marketplace\Client(NULL);
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
        $img = "Tests/mozilla.png";
        $handle = fopen($img, 'r');
        $stub = $this->getCurlMockFetchReturn(
            array('status_code' => 201, 'body' => $response_body));
        $client = new Marketplace\Client($stub, 'key', 'secret', 'domain', 'http', 443, '');
        $response = $client->addScreenshot(12345, $handle);
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
        $img = "Tests/ClientTest.php";
        $handle = fopen($img, 'r');
        $client = new Marketplace\Client(NULL);
        $response = $client->addScreenshot(12345, $handle);
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
        $client = new Marketplace\Client($stub, 'domain', 'http', 443, '');
        $result = $client->getScreenshotInfo(12345);
        $this->assertEquals($result['id'], 12345);
    }

    public function testDeleteScreenshot() 
    {
        $stub = $this->getCurlMockFetchReturn(array('status_code' => 204, 'body' => ''));
        $client = new Marketplace\Client($stub, 'domain', 'http', 443, '');
        $response = $client->deleteScreenshot(12345);
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
        $client = new Marketplace\Client($stub, 'domain', 'http', 443, '');
        $result = $client->getCategoryList();
        $this->assertTrue($result['success']);
        $this->assertEquals(count($result['categories']), 6);
        $this->assertTrue(array_key_exists('id', $result['categories'][0]));
        $this->assertTrue(array_key_exists('resource_uri', $result['categories'][0]));
        $this->assertTrue(array_key_exists('name', $result['categories'][0]));
    }
}
