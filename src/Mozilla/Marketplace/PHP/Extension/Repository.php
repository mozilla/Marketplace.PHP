<?php
/**
 * Copyright 2014 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace\PHP\Extension;


/**
 * An extension repository c;ass
 */
class Repository
{
    /**
     * Return a list of extensions
     *
     * @return array
     */
    public static function getPackageList()
    {
        return array(
            '\Mozilla\Marketplace\PHP\Image',
        );
    }
}