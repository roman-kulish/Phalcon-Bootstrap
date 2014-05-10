<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Australia/Sydney');

$loader = new Phalcon\Loader();
$loader->registerNamespaces(array(
    'Phalcon' => dirname(__DIR__) . '/src/Phalcon'
))->register();