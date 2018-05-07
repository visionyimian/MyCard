<?php

namespace VisionShadow\MyCard\Exceptions;

class BaseException extends \Exception
{
    public $response = null;

    public function __construct($message, $code, $response = null)
    {
        parent::__construct($message, intval($code));

        $this->response = $response;
    }
}
