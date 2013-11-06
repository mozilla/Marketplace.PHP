Marketplace PHP client
======================

A library to interact with Marketplace_


Allows to validate, create and manipulate webapps and screenshots


Usage
#####

To Test::

    composer install --dev

    ./vendor/bin/phpunir

Obtain your key and secret from http://marketplace.mozilla.org/developers/api

Instantiate a target object::

    $target = new Target;
    //update the Target URL if necessary with $target->setUrl($url)

Instantiate a credential object::

    $credential = new Credential;
    $credential->setConsumerKey(123);
    $credential->setConsumerSecret(456);

Pass it to the Client::

    $client = new Mozilla\Marketplace\Client;
    $client->setTarget($target);
    $client->setCredential($credential);

Create webapp if manifest valid::

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

Requires
########

composer

Changelog
########

 - Each Object has it own responsibility
 - Each Object can be easily injected in frameworks like SF2 and ZF2
 - Guzzle keeps control of OAuth
 - 100% Coverage

.. _Marketplace: http://marketplace.mozilla.org
