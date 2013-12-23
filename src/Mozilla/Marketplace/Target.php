<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace;


class Target
{
    const DEFAULT_DOMAIN = "https://marketplace.mozilla.org:443";

    const URL_VALIDATE = 'validate';

    const URL_VALIDATION_RESULT = 'validation_result';

    const URL_CREATE = 'create';

    const URL_APP = 'app';

    const URL_CREATE_SCREENSHOT = 'create_screenshot';

    const URL_SCREENSHOT = 'screenshot';

    const URL_CATEGORIES = 'categories';

    /**
     * @var String $domain
     */
    private $domain = self::DEFAULT_DOMAIN;

    /**
     * @var array $urlList
     */
    private $urlList = array(
        self::URL_VALIDATE          => '/apps/validation/',
        self::URL_VALIDATION_RESULT => '/apps/validation/{id}/',
        self::URL_CREATE            => '/apps/app/',
        self::URL_APP               => '/apps/app/{id}/',
        self::URL_CREATE_SCREENSHOT => '/apps/preview/?app={id}',
        self::URL_SCREENSHOT        => '/apps/preview/{id}/',
        self::URL_CATEGORIES        => '/apps/category/?limit={limit}&offset={offset}'
    );

    /**
     * @param string $domain
     * @throws \InvalidArgumentException
     */
    public function setDomain($domain)
    {
        if( ! filter_var($domain, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("INVALID_DOMAIN");
        }

        $this->domain = $domain;
    }

    /**
     * Creates a full URL to the API using urls dict and simple
     * replacement mechanism
     *
     * @param String $urlKey  The url key
     * @param array  $replace replace key with value
     *
     * @throws \OutOfBoundsException
     *
     * @return String $url
     */
    public function getUrl($urlKey, $replace = array())
    {
        if ( ! isset($this->urlList[$urlKey])) {
            throw new \OutOfBoundsException("INVALID_URL");
        }

        $url = $this->domain.$this->urlList[$urlKey];
        $rep = array();

        foreach ($replace as $key => $value) {
            $rep['{'.$key.'}'] = $value;
        }

        $url = str_replace(array_keys($rep), array_values($rep), $url);

        return $url;
    }
}
