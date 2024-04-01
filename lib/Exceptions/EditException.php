<?php

namespace Site\Api\Exceptions;

class EditException extends \Bitrix\Main\SystemException
{
    const INVALID_EDIT_DATA = "invalid_edit_data";
    const ELEMENT_DOESNT_EXIST = "inavlid_element_id";

    public function __construct($message = "", $code = 0, $file = "", $line = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $file, $line, $previous);
    }
}