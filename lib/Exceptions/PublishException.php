<?php

namespace Site\Api\Exceptions;

class PublishException extends \Bitrix\Main\SystemException
{
    const ILLEGAL_STATUS = "illegal_status";

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