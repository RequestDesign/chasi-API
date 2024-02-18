<?php

namespace Site\Api\Exceptions;

class CreateException extends \Bitrix\Main\SystemException
{
    public const INVALID_CREATE_DATA = "invalid_create_data";

    private $field;

    public function __construct($message = "", $field = "", $code = 0, $file = "", $line = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $file, $line, $previous);
        $this->field = $field;
    }

    public function getField()
    {
        return $this->field;
    }
}