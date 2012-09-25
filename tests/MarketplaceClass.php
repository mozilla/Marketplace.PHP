<?php
require_once 'PHPUnit/Autoload.php';
require_once 'classes/marketplace.php';

class MarketplaceTest extends PHPUnit_Framework_TestCase
{
	private function mockFetchUnauthorized($stab)
	{
		$stab->expects($this->any())
			->method('fetch')
			->will($this->throwException(new OAUTHException(
				'Invalid auth/bad request (got a 401, expected HTTP/1.1 20X or a redirect)',
				10)));
	}

	/**
	 * validate_manifest should throw exception if not authorized
	 *
	 * @expectedException			OAuthException
	 * @expectedExceptionCode		401
	 */
    public function testExceptionInValidateManifest()
    {
		$s_key = 'fakekey';
		$s_secret = 'fake_secret';
		$stab = $this->getMock('OAuth', array('fetch'), array($s_key, $s_secret));
		$this->mockFetchUnauthorized($stab);
		$marketplace = new Marketplace($s_key, $s_secret);
		$marketplace->validate_manifest('http://example.com');
    }

	public function testGetUrls()
	{
		$marketplace = new Marketplace('key', 'secret',
			'domain', 'http', 80, 'prefix');
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
