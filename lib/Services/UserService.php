<?php
namespace Site\Api\Services;
require_once($_SERVER['DOCUMENT_ROOT']."/ajax/class/RegEmailClass.php");
require_once($_SERVER['DOCUMENT_ROOT']."/ajax/class/RegEmail2Class.php");
require_once($_SERVER['DOCUMENT_ROOT']."/ajax/class/RegEmail3Class.php");

use Bitrix\Main\Loader;

Loader::includeModule('highloadblock');
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\FileTable;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UserTable;
use RegEmailClass;
use RegEmail2Class;
use RegEmail3Class;
use Site\Api\Exceptions\PhoneEmailException;
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
        if(empty($this->request["email"]) && empty($this->request["phone"])){
            throw new PhoneEmailException("Одно из полей - 'email' или 'phone' должно быть обязательным");
        }
        $res = RegEmailClass::RegEmailMethod($this->request["email"] ?? null, $this->request["phone"] ?? null ,$errors);
        if(!$res) {
            $exception = null;
            if(!empty($this->request["email"])){
                $exception = new RegisterException("Аккаунт с указанным email уже существует");
                $exception->setExceptionCode(RegisterException::EMAIL_EXCEPTION_CODE);
            }
            else {
                $exception = new RegisterException("Аккаунт с указанным номер телефона уже существует");
                $exception->setExceptionCode(RegisterException::PHONE_EXCEPTION_CODE);
            }
            throw $exception;
        }
        $res = RegEmail2Class::RegEmail2Method($this->request["password"], $this->request["confirmPassword"], $errors);
        if(!$res){
            $exception = new RegisterException("Пароли не совпадают");
            $exception->setExceptionCode(RegisterException::PASSWORD_EXCEPTION_CODE);
            throw $exception;
        }
        $id = RegEmail3Class::RegEmail3Method($this->request["email"] ?? null, $this->request["phone"] ?? null, $this->request["password"], $this->request["name"], $this->request["city"], $errors);
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
                    'phone'=>'PERSONAL_MOBILE',
                    'city'=>'PERSONAL_CITY',
                    "photo" => "FULL_PATH",
                    "birthday" => "PERSONAL_BIRTHDAY",
                    "gender" => "PERSONAL_GENDER",
                    "date_created" => "DATE_REGISTER",
                ],
                "runtime" => [
                    "photo_alias" => [
                        "data_type" => FileTable::class,
                        "reference" => [
                            "=this.PERSONAL_PHOTO" => "ref.ID"
                        ],
                        ["join_type" => "left"]
                    ],
                    new ExpressionField('FULL_PATH', 'CONCAT("'.$serverHost.'/upload/", %s, "/", %s)', ["photo_alias.SUBDIR", "photo_alias.FILE_NAME"]),
                ]
            ]
        )->fetch();
        if ($user) {
            $hlblock = HighloadBlockTable::getById(AdService::AD_HL_ID)->fetch();
            $entity = HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();
            $res = $entity_data_class::getList([
                "select" => ["CNT"],
                "filter" => [
                    "UF_USER_ID" => $user["ID"],
                    "=UF_STATUS" => [AdService::POSTED, AdService::MOVING]
                ],
                "runtime" => [
                    new ExpressionField("CNT", "COUNT(*)")
                ]
            ])->fetch();
            $user["ads_count"] = $res["CNT"];
        }
        return $user;
    }
}