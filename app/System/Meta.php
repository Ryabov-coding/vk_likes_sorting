<?php

namespace App\System;

use Monolog\Logger;

/**
 * Class Meta
 * @package App
 */
class Meta
{
    /** @var Logger */
    private $logger;

    /** @var array */
    private $config_params = [];

    /**
     * Config constructor.
     * @param Logger $logger
     * @param string $config_dir
     * @throws \Exception
     */
    public function __construct(Logger $logger, string $config_dir)
    {
        $this->logger = $logger;

        if (!file_exists($config_dir)) {
            throw new \Exception('Не найден файл конфигурации');
        }
        $this->config_params = parse_ini_file($config_dir);
    }

    /**
     * @param string $param
     * @return mixed
     * @throws \Exception
     */
    public function getConfigParam(string $param)
    {
        if (empty($this->config_params[$param])) {
            throw new \Exception('Не найден требуемый параметр конфигурации: ' . $param);
        }
        return $this->config_params[$param];
    }

    /**
     * Начать сессию
     */
    public function startSession()
    {
        session_start();
    }

    /**
     * @param $public_id
     * @param $name
     * @param $data
     */
    public function saveToSession($public_id, $name, $data)
    {
        $_SESSION['public_' . $public_id][$name] = $data;
    }

    /**
     * @param $public_id
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function getFromSession($public_id, $name)
    {
        if (!isset($_SESSION['public_' . $public_id][$name])) {
            throw new \Exception('Не найдена переменная сессии');
        }
        return $_SESSION['public_' . $public_id][$name];
    }

    /**
     * @param $action
     */
    public function logUserAction($action)
    {
        $this->logger->info($_SERVER['REMOTE_ADDR'] . ' / ' . $action);
    }
}