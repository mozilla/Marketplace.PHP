<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 *
 * @group Unit
 */

namespace Mozilla\Marketplace\Test;


use Mozilla\Marketplace\Credential;

class CredentialTest extends \PHPUnit_Framework_TestCase
{
    private $credential;

    public function setUp()
    {
        $this->credential = new Credential;

        parent::setUp();
    }

    public function testShouldCreateCredential()
    {
        $this->credential = new Credential;
        $this->credential->setConsumerKey('<script>123</script>');
        $this->credential->setConsumerSecret('<script>456</script>');

        $this->assertEquals(123, $this->credential->getConsumerKey());
        $this->assertEquals(456, $this->credential->getConsumerSecret());
    }
}
