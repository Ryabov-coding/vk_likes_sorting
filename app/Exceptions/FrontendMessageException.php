<?php

namespace App\Exceptions;

/**
 * Class MessageException
 */
class FrontendMessageException extends \Exception
{
    public function errorMessage($message) {
        return $message;
    }
}