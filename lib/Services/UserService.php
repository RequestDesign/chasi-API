<?php
namespace Site\Api\Services;
require_once($_SERVER['DOCUMENT_ROOT']."/ajax/class/RegEmailClass.php");
require_once($_SERVER['DOCUMENT_ROOT']."/ajax/class/RegEmail2Class.php");
require_once($_SERVER['DOCUMENT_ROOT']."/ajax/class/RegEmail3Class.php");

use Bitrix\Main\Application;
use RegEmailClass;
use RegEmail2Class;
use RegEmail3Class;
use Site\Api\Exceptions\RegisterException;

class UserService
{
    /**
     * @var array
     */
    public array $request;
    /**
     * @var Application
     */
    public Application $app;

    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->request = $this->app->getContext()->getRequest()->toArray();
    }

    /**
     * @throws RegisterException
     */
    public function register()
    {
        $errors = [];
        $res = RegEmailClass::RegEmailMethod($this->request["email"], $errors);
        if(!$res) {
            $exception = new RegisterException("Аккаунт с указанным email уже существует");
            $exception->setExceptionCode(RegisterException::EMAIL_EXCEPTION_CODE);
            throw $exception;
        }
        $res = RegEmail2Class::RegEmail2Method($this->request["password"], $this->request["confirmPassword"], $errors);
        if(!$res){
            $exception = new RegisterException("Пароли не совпадают");
            $exception->setExceptionCode(RegisterException::PASSWORD_EXCEPTION_CODE);
            throw $exception;
        }
        $id = RegEmail3Class::RegEmail3Method($this->request["email"], $this->request["password"], $this->request["name"], $this->request["lastName"], $this->request["city"], $this->request["phone"] ?? "", $errors);
        if(!$id) {
            $exception = new RegisterException("Внутренняя ошибка регистрации");
            $exception->setExceptionCode(RegisterException::USER_CREATION_EXCEPTION_CODE);
            throw $exception;
        }
        return $id;
    }
}