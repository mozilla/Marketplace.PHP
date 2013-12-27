<?php
/**
 * Copyright 2014 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace\PHP;

use Mozilla\Marketplace\PHP\Extension\Repository;


/**
 * A class provide backward compatility to some PHP Extensions
 * - Backward compatibility with PHP 5.3
 * - Compatibility with HHVM
 */
class Register
{
    /**
     * Register possible extensions
     */
    public function __construct()
    {
        $this->register();
    }

    /**
     * Register the extensions
     */
    private function register()
    {
        $extensionList = Repository::getPackageList();

        foreach ($extensionList as $extensionClass) {
            $extension = new $extensionClass;

            if ( ! $extension instanceof Extension) {
                continue;
            }

            $extension->load();
        }
    }
}