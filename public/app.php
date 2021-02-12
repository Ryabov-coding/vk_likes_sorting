<?php

header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use DI\ContainerBuilder;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../constants.php';

$container_builder = new ContainerBuilder();
$container_builder->addDefinitions(CONFIG_DIR . '/containers/main.php');
$container = $container_builder->build();

$app = $container->get('App\App');
$app->run();
