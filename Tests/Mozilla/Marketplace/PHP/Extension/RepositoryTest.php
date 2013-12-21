<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julão <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace\Test\PHP;

use Mozilla\Marketplace\PHP\Extension\Repository;

/**
 * Test the Repository
 *
 * @group Functional
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    private $repository;

    public function testShouldRetrieveThePackageList()
    {
        $this->assertEquals(array('\Mozilla\Marketplace\PHP\Image'), Repository::getPackageList());
    }
}
