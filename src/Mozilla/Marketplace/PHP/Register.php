<?php
/**
 * Copyright 2014 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace\PHP;


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
     * Return all possible extension
     *
     * @return array
     */
    public function getExtensionList()
    {
        return array(
            '\Mozilla\Marketplace\PHP\Image',
        );
    }

    /**
     * Register the extensions
     */
    private function register()
    {
        $extensionList = $this->getExtensionList();

        foreach ($extensionList as $extensionClass) {
            $this->registerMethodList(new $extensionClass);
        }
    }

    /**
     * Register functions for a given extension
     *
     * @param mixed $extension
     */
    private function registerMethodList($extension)
    {

        if ( ! $extension instanceof Extension) {
            return;
        }

        $methodList = $extension->getMethodList();

        foreach ($methodList as $method) {
            $extension->$method();
        }
    }
}