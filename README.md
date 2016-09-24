Marketplace PHP client
======================

[![Build Status](https://travis-ci.org/kinncj/Marketplace.PHP.png?branch=master)](https://travis-ci.org/kinncj/Marketplace.PHP)

A library to interact with [Marketplace](https://marketplace.firefox.com/)

Allows to validate, create and manipulate webapps and screenshots.

------------------------------------------------------
##Usage

#####To Test:
```
    composer install

    ./vendor/bin/phpunir
```

First go and obtain your key and secret from the [Api](https://marketplace.firefox.com/developers/api)

#####Instantiate a target object

```php
$target = new Target;
//update the Target URL if necessary with $target->setUrl($url)
```

#####Instantiate a credential object

```php
$credential = new Credential;
$credential->setConsumerKey(123);
$credential->setConsumerSecret(456);
```

#####Pass it to the Client

```php
$client = new Mozilla\Marketplace\Client;
$client->setTarget($target);
$client->setCredential($credential);
```

#####Create webapp if the manifest is valid

```php
// validate manifest
$response = $client->validateManifest('http://example.com/manifest.webapp');
echo "\n\nManifest id: ".$response['id'];
echo "\nManifest is ";
if ($response["valid"]) {
  echo "valid - creating webapp...";
  // create webapp
  $response = $client->createWebapp($response['id']);
  echo "\n\nWebapp id: ".$response['id'];
} else {
  echo "invalid";
}
```

##Requires

- composer

##Changelog

 - Each Object has it own responsibility
 - Each Object can be easily injected in frameworks like SF2 and ZF2
 - Guzzle keeps control of OAuth
 - 100% Coverage
