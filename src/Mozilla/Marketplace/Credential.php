<?php
/**
 * Copyright 2013 - Mozilla
 *
 * @author Kinn Coelho Julião <kinncj@gmail.com>
 */

namespace Mozilla\Marketplace;


class Credential
{
    /**
     * @var String $consumerKey
     */
    private $consumerKey;

    /**
     * @var String $consumerSecret
     */
    private $consumerSecret;

    /**
     * @param String $consumerKey
     */
    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = filter_var($consumerKey, FILTER_SANITIZE_STRING);
    }

    /**
     * @param String $consumerSecret
     */
    public function setConsumerSecret($consumerSecret)
    {
        $this->consumerSecret = filter_var($consumerSecret, FILTER_SANITIZE_STRING);
    }

    /**
     * @return String
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * @return String
     */
    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }
}
