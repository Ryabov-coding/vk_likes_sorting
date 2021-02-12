<?php

use App\System\Meta;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

return [
    Logger::class => DI\factory(function () {
        $logger = new Logger('Main');
        $logger->pushHandler(new StreamHandler(BASE_DIR . '/logs/main.log', Logger::INFO));
        return $logger;
    }),

    Meta::class => DI\autowire('App\System\Meta')
        ->constructorParameter('config_dir', CONFIG_DIR . '/config.ini'),
];