<?php

namespace Site\Api\Exceptions;

class PhoneMissingException extends \Bitrix\Main\SystemException
{
    const MISSING_PHONE = "phone_not_found";
}