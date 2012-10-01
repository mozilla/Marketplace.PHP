Marketplace PHP client
======================

A library to interact with [Marketplace](http://marketplace.mozilla.org)

Allows to validate, create and manipulate webapps and screenshots


Usage
#####

To Test::

    phpunit Tests

Obtain your key and secret from http://marketplace.mozilla.org/developers/api

Instantiate Connection object::

    $connection = new Marketplace\Connection('yourkey', 'yoursecret');

Pass it to the Client::

    $marketplace = Marketplace\Client($connection);

Create webapp if manifest valid::

    // validate manifest
    $response = $marketplace->validateManifest('http://example.com/manifest.webapp');
    echo "\n\nManifest id: ".$response['id'];
    echo "\nManifest is ";
    if ($response["valid"]) {
      echo "valid - creating webapp...";
      // create webapp
      $response = $marketplace->createWebapp($response['id']);
      echo "\n\nWebapp id: ".$response['id'];
    } else {
      echo "invalid";
    }
