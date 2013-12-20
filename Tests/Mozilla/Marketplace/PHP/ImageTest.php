<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julão <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace\Test\PHP;

use Mozilla\Marketplace\Test\PHP\Image;

/**
 * Test the Register
 *
 * @group Functional
 */
class ImageTest extends \PHPUnit_Framework_TestCase
{
    private $image;

    public function setUp()
    {

        if (function_exists('getimagesizefromstring')) {
            $this->markTestSkipped('The getimagesizefromstring function already exists');
        }

        $this->image = new Image;

        parent::setUp();
    }

    public function testShouldGetMethodList()
    {
        $this->assertEquals('getimagesizefromstring', $this->image->getMethodList());
    }

    public function testShouldRegisterFunction()
    {
        $this->image->getimagesizefromstring();

        $this->assertTrue(function_exists('getimagesizefromstring'));
    }
}
