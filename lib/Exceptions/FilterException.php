<?php

namespace Site\Api\Exceptions;

use Bitrix\Main\SystemException;

class FilterException extends SystemException
{
    public const FILTER_EXCEPTION_CODE = "filter_error";
}