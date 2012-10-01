<?php
require_once 'PHPUnit/Autoload.php';
require_once 'Connection.php';


class MarketplaceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Get stub with _fetch method modified
     */
    private function _getConnectionStub($return_data) 
    {
        $stub = $this->getMockBuilder('Marketplace\Connection')
            ->setConstructorArgs(array('key', 'secret'))
            ->setMethods(array('curl'))
            ->getMock();
        $stub->staticExpects($this->any())
                 ->method('curl')
                 ->will($this->returnValue($return_data));
        $this->assertEquals($stub::curl('a', 'b', 'c'), $return_data);
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
        $stub = $this->_getConnectionStub(
            array('status_code' => 401, 'body' => $e_msg));
        $stub->fetch('GET', 'http://example.com/');
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
        $stub = $this->_getConnectionStub(
            array('status_code' => 204, 'body' => $e_msg));
        $stub->fetch('GET', 'http://example.com/', NULL, 200);
    }

    /**
     * API might response with a non JSON body
     *
     * @expectedException           Exception
     * @expectedExceptionCode       404
     * @expectedExceptionMessage    <html><title>404</title><body>404</body></html>
     */
    public function testFetchWithNonJSONErrorMsg()
    {
        $e_msg = '<html><title>404</title><body>404</body></html>';
        $stub = $this->_getConnectionStub(
            array('status_code' => 404, 'body' => $e_msg));
        $stub->fetch('GET', 'http://example.com/', NULL, 200);
    }

    public function testFetch()
    {
        $msg = '{"data": "somedata"}';
        $stub = $this->_getConnectionStub(
            array('status_code' => 200, 'body' => $msg));
        $result = $stub->fetch('GET', 'http://example.com/', NULL, 200);
        $this->assertEquals($result['body'], $msg);
        $this->assertEquals($result['status_code'], 200);
    }

    public function testFetchJSON()
    {
        $msg = '{"data": "somedata"}';
        $stub = $this->_getConnectionStub(
            array('status_code' => 200, 'body' => $msg));
        $result = $stub->fetchJSON('GET', 'http://example.com/', NULL, 200);
        $this->assertEquals($result['body'], $msg);
        $this->assertEquals($result['json']->data, 'somedata');
        $this->assertEquals($result['status_code'], 200);
    }
}
