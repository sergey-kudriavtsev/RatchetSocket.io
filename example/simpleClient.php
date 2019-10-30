<<?php

require __DIR__ . '../../vendor/autoload.php';

use Ratchet\SocketIO\Client as SocketClient;

SocketClient::connect()->then(function($connection) {
   var_dump($connection);
});
