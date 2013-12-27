<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 *
 * @group Unit
 */

namespace Mozilla\Marketplace\Test;


use Mozilla\Marketplace\Target;

class TargetTest extends \PHPUnit_Framework_TestCase
{
    private $target;

    public function setUp()
    {
        $this->target = new Target;

        parent::setUp();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testShouldNotSetDomain()
    {
        $this->target->setDomain("invalid domain name");
    }

    public function testShoultSetDomain()
    {
        $this->target->setDomain("http://www.mozilla.org");
    }

    /**
     * @dataProvider invalidUrlProvider
     *
     * @expectedException OutOfBoundsException
     */
    public function testShouldNotGetUrl($key)
    {
        $this->target->getUrl($key);
    }

    /**
     * @dataProvider validUrlProvider
     */
    public function testShouldGetUrl($key, $expected)
    {
        $actual = $this->target->getUrl($key);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider validIdUrlProvider
     */
    public function testShouldGetReplacedUrl($key, $expected)
    {
        $actual = $this->target->getUrl($key, array("id" => 12));

        $this->assertEquals($expected, $actual);
    }

    public function invalidUrlProvider()
    {
        return array(
            array("titles"),
            array("validation"),
            array("wrong url"),
            array(1),
            array(2),
            array("invalid.url")
        );
    }

    public function validUrlProvider()
    {
        return array(
            array("validate", "https://marketplace.mozilla.org:443/apps/validation/"),
            array("validation_result", "https://marketplace.mozilla.org:443/apps/validation/{id}/"),
            array("create", "https://marketplace.mozilla.org:443/apps/app/"),
            array("app", "https://marketplace.mozilla.org:443/apps/app/{id}/"),
            array("create_screenshot", "https://marketplace.mozilla.org:443/apps/preview/?app={id}"),
            array("screenshot", "https://marketplace.mozilla.org:443/apps/preview/{id}/")
        );
    }

    public function validIdUrlProvider()
    {
        return array(
            array("validate", "https://marketplace.mozilla.org:443/apps/validation/"),
            array("validation_result", "https://marketplace.mozilla.org:443/apps/validation/12/"),
            array("create", "https://marketplace.mozilla.org:443/apps/app/"),
            array("app", "https://marketplace.mozilla.org:443/apps/app/12/"),
            array("create_screenshot", "https://marketplace.mozilla.org:443/apps/preview/?app=12"),
            array("screenshot", "https://marketplace.mozilla.org:443/apps/preview/12/")
        );
    }
}
