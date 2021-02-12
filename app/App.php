<?php

namespace App;

use App\Exceptions\FrontendMessageException;
use App\System\Controller;
use App\System\Meta;
use Exception;

/**
 * Class App
 * @package App
 */
class App
{
    /** @var Meta */
    private $meta;

    /** @var Controller */
    private $controller;

    /**
     * App constructor.
     * @param Meta $meta
     * @param Controller $controller
     * @internal param Helpers $helpers
     */
    public function __construct(Meta $meta, Controller $controller)
    {
        $this->meta = $meta;
        $this->controller = $controller;
    }

    public function run()
    {
        try {
            if (empty($_GET['action'])) {
                throw new Exception('Не передан action');
            }

            $action = $this->formatActionToMethodName($_GET['action']);
            if (!method_exists($this->controller, $action)) {
                throw new Exception('Требуемого action не существует');
            }

            $this->controller->setActionParams(empty($_GET['action_params']) ? [] : $_GET['action_params']);
            $this->meta->startSession();

            $success = true;
            $result_data = $this->controller->{$action}();
            $error = null;
        } catch (Exception $e) {
            $this->meta->logUserAction('ERROR / ' . $e->getMessage());

            $success = false;
            $result_data = null;
            $error = $e instanceof FrontendMessageException ? $e->getMessage() : 'Ошибка. Попробуйте обновить страницу';
        }

        echo json_encode([
            'success' => $success,
            'data' => $result_data,
            'error' => $error
        ]);
    }

    /**
     * @param $input
     * @return mixed
     */
    private function formatActionToMethodName($input)
    {
        return str_replace('_', '', ucwords($input, '_'));
    }
}