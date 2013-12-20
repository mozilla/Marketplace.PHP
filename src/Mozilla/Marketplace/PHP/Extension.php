<?php
/**
 * Copyright 2014 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace\PHP;

interface Extension 
{
    /**
     * Return a list of methods to register
     *
     * @return array
     */
    public function getMethodList();
}