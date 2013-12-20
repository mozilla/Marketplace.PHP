<?php
/**
 * Copyright 2014 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace\PHP;


/**
 * A class provide backward compatility to the PHP Image extension (Image.c)
 * - Backward compatibility with PHP 5.3
 * - Compatibility with HHVM
 *
 * For more understanding
 * https://github.com/AsamK/php-src/blob/PHP-5.5/ext/standard/image.c
 */
class Image implements Extension
{
    /**
     * {@inheritdoc}
     */
    public function getMethodList()
    {
        return array("getimagesizefromstring");
    }

    /**
     * Make getimagesizefromstring compatible with HHVM and PHP 5.3 
     *
     * @return string
     */
    public function getimagesizefromstring()
    {
        if ( ! function_exists('getimagesizefromstring')) {
            require_once __DIR__.'/Extension/Zend/Standard/Image.php';
        }
    }
}