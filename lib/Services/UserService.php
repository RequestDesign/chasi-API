<?php
namespace Site\Api\Services;
require_once($_SERVER['DOCUMENT_ROOT']."/ajax/class/RegEmailClass.php");
require_once($_SERVER['DOCUMENT_ROOT']."/ajax/class/RegEmail2Class.php");
require_once($_SERVER['DOCUMENT_ROOT']."/ajax/class/RegEmail3Class.php");

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\FileTable;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UserTable;
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
        $id = RegEmail3Class::RegEmail3Method($this->request["email"], $this->request["password"], $this->request["name"], $this->request["city"], $errors);
        if(!$id) {
            $exception = new RegisterException("Внутренняя ошибка регистрации");
            $exception->setExceptionCode(RegisterException::USER_CREATION_EXCEPTION_CODE);
            throw $exception;
        }
        return $id;
    }

    public function getOne($id)
    {
        $serverHost = (Context::getCurrent()->getRequest()->isHttps()?"https://":"http://").Context::getCurrent()->getServer()->getHttpHost();
        $user = UserTable::getByPrimary($id,
            [
                "select" => [
                    'id',
                    'EMAIL',
                    'ACTIVE',
                    'NAME',
                    'LAST_NAME',
                    'phone'=>'PERSONAL_PHONE',
                    'city'=>'PERSONAL_CITY',
                    "photo" => "FULL_PATH",
                    "birthday" => "PERSONAL_BIRTHDAY",
                    "gender" => "PERSONAL_GENDER"
                ],
                "runtime" => [
                    "photo_alias" => [
                        "data_type" => FileTable::class,
                        "reference" => [
                            "=this.PERSONAL_PHOTO" => "ref.ID"
                        ],
                        ["join_type" => "left"]
                    ],
                    new ExpressionField('FULL_PATH', 'CONCAT("'.$serverHost.'/upload/", %s, "/", %s)', ["photo_alias.SUBDIR", "photo_alias.FILE_NAME"])
                ]
            ]
        )->fetch();

        return $user;
    }
}