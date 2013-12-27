<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 * @author Steve Gricci <steve@gricci.org>
 * @author Piotr Zalewa <piotr@zalewa.info>
 * @author Ruslan Bekenev <furyinbox@gmail.com>
 */

namespace Mozilla\Marketplace\Test;

use Mozilla\Marketplace\Client;
use Mozilla\Marketplace\Target;

/**
 * Test the Client
 *
 * @note: valid manifest url: http://mozilla.github.com/MarketplaceClientExample/manifest.webapp
 *
 * @group Functional
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    private $client;

    public function setUp()
    {
        $this->client = new Client;
        $target       = new Target;

        $this->client->setTarget($target);
        $this->client->setCredential($this->getCredentialMock());

        parent::setUp();
    }

    private function getCredentialMock()
    {
        $credential = $this->getMock('Mozilla\Marketplace\Credential');
        $credential->expects($this->any())
            ->method('getConsumerKey')
            ->will($this->returnValue(123));
        $credential->expects($this->any())
            ->method('getConsumerSecret')
            ->will($this->returnValue(456));

        return $credential;
    }

    /**
     * @param $value
     * @return mixed
     */
    private function getConnectionMock($value)
    {
        $connectionMock = $this->getMock('Mozilla\Marketplace\Connection');
        $connectionMock->expects($this->any())
            ->method('fetch')
            ->will($this->returnValue($value));

        return $connectionMock;
    }

    public function testValidateManifest()
    {
        $response               = new \stdClass;
        $response->id           = "abcdefghijklmnopqrstuvwxyz123456";
        $response->processed    = true;
        $response->resource_uri =  "/api/apps/validation/abcdefghijklmnopqrstuvwxyz123456/";
        $response->valid        =  true;
        $response->validation   =  "";

        $this->client->setConnection($this->getConnectionMock($response));


        $response = $this->client->validateManifest('http://example.com/');
        $this->assertTrue($response['success']);
        $this->assertTrue($response['valid']);
        $this->assertEquals($response['id'], 'abcdefghijklmnopqrstuvwxyz123456');
    }

    public function testIsManifestValid()
    {
        $response               = new \stdClass;
        $response->id           = "abcdefghijklmnopqrstuvwxyz123456";
        $response->processed    = true;
        $response->resource_uri =  "/api/apps/validation/abcdefghijklmnopqrstuvwxyz123456/";
        $response->valid        =  true;
        $response->validation   =  "";

        $this->client->setConnection($this->getConnectionMock($response));


        $response = $this->client->isManifestValid('abcdefghijklmnopqrstuvwxyz123456');
        $this->assertTrue($response['success']);
        $this->assertTrue($response['valid']);
    }

    public function testShouldNotValidateManifest()
    {
        $messages                       = array((object) array("type" => "error", "message" => "invalid data"));
        $response                       = new \stdClass;
        $response->id                   = "abcdefghijklmnopqrstuvwxyz123456";
        $response->processed            = true;
        $response->resource_uri         =  "/api/apps/validation/abcdefghijklmnopqrstuvwxyz123456/";
        $response->valid                =  false;
        $response->validation           =  new \stdClass;
        $response->validation->messages = $messages;

        $this->client->setConnection($this->getConnectionMock($response));


        $response = $this->client->validateManifest('http://example.com/');
        $this->assertTrue($response['success']);
        $this->assertFalse($response['valid']);
        $this->assertEquals($response['id'], 'abcdefghijklmnopqrstuvwxyz123456');
    }

    public function testCreateApp()
    {
        $response = new \stdClass;
        $response->categories = array();
        $response->description = null;
        $response->device_types = array();
        $response->homepage = null;
        $response->id = "123456";
        $response->manifest = "abcdefghijklmnopqrstuvwxyz123456";
        $response->name = "MozillaBall";
        $response->premium_type =  "free";
        $response->previews = array();
        $response->privacy_policy = null;
        $response->resource_uri = "/api/apps/app/123456/";
        $response->slug = "mozillaball-1";
        $response->status = 0;
        $response->summary = "Exciting Open Web development action!";
        $response->support_email = null;
        $response->support_url = null;

        $this->client->setConnection($this->getConnectionMock($response));


        $response = $this->client->createWebapp("abcdefghijklmnopqrstuvwxyz123456");
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
        $this->client->updateWebapp(123456, array('name' => 'TestName'));
    }

    public function testUpdateWebapp()
    {
        $this->client->setConnection($this->getConnectionMock(''));

        $response = $this->client->updateWebapp(123456, array(
                'name'           => 'TestName',
                'summary'        => '',// not empty string required for real connection
                'categories'     => '',// array required for real connection
                'privacy_policy' => '',// not empty string required
                'support_email'  => '',// valid email required
                'device_types'   => '',// array required
                'payment_type'   => '',// not empty string required
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

        $this->client->setConnection($this->getConnectionMock(json_decode($response_body)));

        $response = $this->client->getWebappInfo(123456);

        $this->assertEquals($response['success'], true);
        $this->assertEquals($response['id'], '123456');
        $this->assertEquals($response['resource_uri'], '/api/apps/app/123456/');
    }

    /**
     * @expectedException        Exception
     * #expectedExceptionMessage Not Implemented
     */
    public function testRemoveWebappNotImplemented()
    {
        $this->client->setConnection($this->getConnectionMock(''));
        $this->client->removeWebapp(123);
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

        $img    = "Tests/mozilla.png";
        $handle = fopen($img, 'r');

        $this->client->setConnection($this->getConnectionMock(json_decode($response_body)));

        $response = $this->client->addScreenshot(12345, $handle);

        fclose($handle);

        $this->assertEquals($response['id'], 12345);
        $this->assertEquals($response['resource_uri'], "/api/apps/preview/12345/");
    }

    /**
     * @expectedException         Mozilla\Marketplace\WrongFileException
     * @expectedExceptionMessage  Wrong file
     */
    public function testUploadWrongFile()
    {
        $img    = "Tests/Mozilla/Marketplace/ClientTest.php";
        $handle = fopen($img, 'r');

        $this->client->addScreenshot(12345, $handle);

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

        $this->client->setConnection($this->getConnectionMock(json_decode($response_body)));

        $result = $this->client->getScreenshotInfo(12345);
        $this->assertEquals($result['id'], 12345);
    }

    public function testDeleteScreenshot()
    {
        $this->client->setConnection($this->getConnectionMock(''));

        $response = $this->client->deleteScreenshot(12345);
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

        $this->client->setConnection($this->getConnectionMock(json_decode($response_body)));

        $result = $this->client->getCategoryList();

        $this->assertTrue($result['success']);
        $this->assertEquals(count($result['categories']), 6);
        $this->assertTrue(array_key_exists('id', $result['categories'][0]));
        $this->assertTrue(array_key_exists('resource_uri', $result['categories'][0]));
        $this->assertTrue(array_key_exists('name', $result['categories'][0]));
    }
}
