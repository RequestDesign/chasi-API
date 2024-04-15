<?php

namespace Site\Api\Exceptions;

class PublishException extends \Bitrix\Main\SystemException
{
    const ILLEGAL_STATUS = "illegal_status";
}