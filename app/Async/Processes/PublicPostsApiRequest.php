<?php

use DI\ContainerBuilder;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../constants.php';

/**
 * Для асинхронного запроса постов пабликов по АПИ контакта
 */

$options = getopt('', ['public_id:', 'offset:']);

$container_builder = new ContainerBuilder();
$container_builder->addDefinitions(CONFIG_DIR . '/containers/main.php');
$container = $container_builder->build();

$vk_api = $container->get('App\Api\Vk');
echo json_encode($vk_api->executeGetPostsApiScript($options['public_id'], $options['offset']));