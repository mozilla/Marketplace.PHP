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
		$oauth = new Marketplace($s_key, $s_secret);
		$oauth->validate_manifest('http://example.com');
    }
}
