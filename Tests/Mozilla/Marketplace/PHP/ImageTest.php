<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julão <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace\Test\PHP;

use Mozilla\Marketplace\PHP\Image;

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
        $this->image = new Image;

        parent::setUp();
    }

    public function testShouldGetMethodList()
    {
        $this->assertEquals(array('getimagesizefromstring'), $this->image->getMethodList());
    }

    public function testShouldRegisterFunction()
    {
        $this->image->getimagesizefromstring();

        $this->assertTrue(function_exists('getimagesizefromstring'));
    }
}
