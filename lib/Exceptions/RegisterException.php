<?php

namespace Site\Api\Exceptions;

class RegisterException extends \Bitrix\Main\SystemException
{
    public const EMAIL_EXCEPTION_CODE = "email_exists";
    public const PASSWORD_EXCEPTION_CODE = "passwords_dont_match";
    public const USER_CREATION_EXCEPTION_CODE = "create_user_error";
    private $exceptionCode;

    public function setExceptionCode($code): void
    {
        $this->exceptionCode = $code;
    }

    /**
     * @return mixed
     */
    public function getExceptionCode()
    {
        return $this->exceptionCode;
    }
}