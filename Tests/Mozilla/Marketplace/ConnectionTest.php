<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 *
 * @group Unit
 */

namespace Mozilla\Marketplace\Test;


use Mozilla\Marketplace\Connection;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    private $connection;

    public function setUp()
    {
        $this->connection = new Connection;
        $this->connection->setCredential($this->getCredentialMock());
        $this->connection->setHttpClient($this->getHttpClientMock());

        parent::setUp();
    }

    public function testShouldPutFetch()
    {
        $response = $this->connection->fetch("http://www.google.com", 'put', null, array('cache-control' => 'private, max-age=0, no-cache'));

        $this->assertEquals(204, $response['status_code']);
        $this->assertEquals('foo', $response['body']);
    }

    public function testShouldPostFetch()
    {
        $response = $this->connection->fetch("http://www.google.com", 'post');

        $this->assertEquals(201, $response['status_code']);
        $this->assertEquals('bar', $response['body']);
    }

    public function testShouldGetFetch()
    {
        $response = $this->connection->fetch("http://www.google.com", 'get');

        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals('baz', $response['body']);
    }

    public function testShouldDeleteFetch()
    {
        $response = $this->connection->fetch("http://www.google.com", 'delete');

        $this->assertEquals(202, $response['status_code']);
        $this->assertEquals('foobar', $response['body']);
    }

    public function testShouldNotFetch()
    {
        $response = $this->connection->fetch("http://www.google.com", 'invalid');

        $this->assertFalse($response);
    }

    public function testShouldCatchException()
    {
        $this->connection->setHttpClient($this->getFailHttpClientMock());

        $response = $this->connection->fetch("http://www.google.com", 'get');

        $this->assertFalse($response);
    }

    private function getCredentialMock()
    {
        $credentialMock = $this->getMock('Mozilla\Marketplace\Credential', array('getConsumerKey', 'getConsumerSecret'));
        $credentialMock->expects($this->any())
            ->method('getConsumerKey')
            ->will($this->returnValue('123'));
        $credentialMock->expects($this->any())
            ->method('getConsumerSecret')
            ->will($this->returnValue('456'));

        return $credentialMock;
    }

    private function getHttpClientMock()
    {
        $guzzleMock = $this->getMock('Guzzle\Http\Client', array('put', 'post', 'get', 'delete'));
        $guzzleMock->expects($this->any())
            ->method('put')
            ->will($this->returnValue($this->getRequestMock(204, 'foo')));
        $guzzleMock->expects($this->any())
            ->method('post')
            ->will($this->returnValue($this->getRequestMock(201, 'bar')));
        $guzzleMock->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->getRequestMock(200, 'baz')));
        $guzzleMock->expects($this->any())
            ->method('delete')
            ->will($this->returnValue($this->getRequestMock(202, 'foobar')));

        return $guzzleMock;
    }

    private function getFailHttpClientMock()
    {
        $guzzleMock = $this->getMock('Guzzle\Http\Client', array('put', 'post', 'get', 'delete'));
        $guzzleMock->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->getFailRequestMock(200, 'baz')));

        return $guzzleMock;
    }

    private function getRequestMock($code, $value)
    {
        $requestMock = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->any())
            ->method('send')
            ->will($this->returnValue($this->getResponseMock($code, $value)));

        return $requestMock;
    }

    private function getFailRequestMock($code, $value)
    {
        $requestMock = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock->expects($this->any())
            ->method('send')
            ->will($this->throwException(new \Exception("Invalid")));

        return $requestMock;
    }

    private function getResponseMock($code, $value)
    {
        $responseMock = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $responseMock->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue($code));
        $responseMock->expects($this->any())
            ->method('json')
            ->will($this->returnValue($value));

        return $responseMock;
    }
}
