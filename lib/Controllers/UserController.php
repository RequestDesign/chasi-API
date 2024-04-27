<?php

namespace Site\Api\Controllers;

include_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/DeleteAccClass.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/EditAvatarClass.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/EditDataClass.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/EditPassClass.php");

use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\FileTable;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UserTable;
use Site\Api\Exceptions\PhoneEmailException;
use Site\Api\Exceptions\RegisterException;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Prefilters\Csrf;
use Bitrix\Main\Engine\Controller;
use Site\Api\Prefilters\Validator;
use Site\Api\Services\Validation;
use DeleteAccClass;
use EditAvatarClass;
use EditDataClass;
use EditPassClass;

/**
 * UserController class
 *
 */
class UserController extends Controller
{

    protected function getDefaultPreFilters():array
    {
        return [
            new Csrf()
        ];
    }

    public function configureActions():array
    {
        return [
            "create" => [
                "+prefilters" => [
                    new Validator([
                        (new Validation("email"))->email(),
                        (new Validation("phone"))->number(),
                        (new Validation("name"))->maxLength(255)->required(),
                        (new Validation("city"))->maxLength(255)->required(),
                        (new Validation("password"))->required()->password(),
                        (new Validation("confirmPassword"))->required()->password(),
                    ])
                ]
            ],
            "getOne" => [
                "+prefilters" => [
                    new Validator([
                        (new Validation("id"))->required()->number()
                    ])
                ],
                "postfilters" => [
                    new ChangeKeyCase()
                ]
            ],
            "delete" => [
                "+prefilters" => [
                    new Validator([
                        (new Validation("id"))->required()->number()
                    ]),
                    new Authentication()
                ]
            ],
            "edit" => [
                "+prefilters" => [
                    new Validator([
                        (new Validation("id"))->required()->number(),
                        (new Validation("newPassword"))->password()
                    ]),
                    new Authentication()
                ]
            ],
            "getCurrentUser" => [
                "+prefilters" => [
                    new Authentication()
                ]
            ]
        ];
    }

    public function createAction():array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $userService = $serviceLocator->get("site.api.user");
        try{
            $id = $userService->register();
            http_response_code(201);
            return ["id" => $id];
        }
        catch (\Exception $e){
            if ($e instanceof RegisterException){
                $this->addError(new Error($e->getMessage(), $e->getExceptionCode()));
            }
            if ($e instanceof PhoneEmailException){
                $this->addError(new Error($e->getMessage(), PhoneEmailException::ERROR_REQUIRED));
            }
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
    }

    public function getOneAction():array|EventResult
    {
        $request = $this->getRequest()->toArray();
        $serviceLocator = ServiceLocator::getInstance();
        $userService = $serviceLocator->get("site.api.user");
        $user = $userService->getOne($request['id']);
        if($user){
            $user["ACTIVE"] = $user["ACTIVE"] === "Y"?1:0;
            return $user;
        }
        else{
            $this->addError(new Error(
                "Пользователь не найден",
                "user_not_found"
            ));
            http_response_code(404);
            return new EventResult(EventResult::ERROR, null, 'site.api', $this);
        }
    }

    public function getCurrentUserAction(): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $userService = $serviceLocator->get("site.api.user");
        $user = $userService->getOne($this->getCurrentUser()->getId());
        if($user){
            $user["ACTIVE"] = $user["ACTIVE"] === "Y"?1:0;
            return $user;
        }
        else{
            $this->addError(new Error(
                "Пользователь не найден",
                "user_not_found"
            ));
            http_response_code(404);
            return new EventResult(EventResult::ERROR, null, 'site.api', $this);
        }
    }

    public function deleteAction(): array|EventResult
    {
        $request = $this->getRequest()->toArray();
        $errors = [];
        $res = DeleteAccClass::DeleteAccClassMethod($request["id"], $errors);
        if($res){
            return [];
        }
        $this->addError(new Error(
           "Невозможно удалить пользователя",
           "illegal_delete_user"
        ));
        http_response_code(400);
        return new EventResult(EventResult::ERROR, null, "site.api", $this);
    }

    public function editAction(): array|EventResult
    {
        $request = $this->getRequest()->toArray();
        $hasErrors = false;
        $user = UserTable::getByPrimary($this->getCurrentUser()->getId(), ["select"=>["NAME", "PERSONAL_CITY", "PERSONAL_BIRTHDAY", "PERSONAL_GENDER"]]);
        if(isset($request["photo"])){
            $errors = [];
            $res = EditAvatarClass::EditAvatarClassMethod($request["id"], $request["photo"], $errors);
            if(!$res){
                foreach($errors as $error_key => $error_message){
                    switch ($error_key){
                        case 'base64':{
                            $this->addError(new Error(
                                "Невозможно распознать изображение",
                                "illegal_photo"
                            ));
                            $hasErrors = true;
                            break;
                        }
                        case 'userExists':{
                            $this->addError(new Error(
                                "Пользователь не найден",
                                "illegal_user"
                            ));
                            $hasErrors = true;
                            break;
                        }
                    }
                }
            }
        }
        if(isset($request["name"]) || isset($request["city"]) || isset($request["birthday"]) || isset($request["gender"])){
            $userRequest = array();
            $userRequest["NAME"] = $request["name"] ?? $user["NAME"];
            $userRequest["PERSONAL_CITY"] = $request["city"] ?? $user["PERSONAL_CITY"];
            $userRequest["PERSONAL_BIRTHDAY"] = $request["birthday"] ?? $user["PERSONAL_BIRTHDAY"];
            $userRequest["PERSONAL_GENDER"] = $request["gender"] ?? $user["PERSONAL_GENDER"];
            $errors = [];
            $res = EditDataClass::EditDataClassMethod($request["id"], $userRequest["NAME"], $userRequest["PERSONAL_CITY"], $userRequest["PERSONAL_BIRTHDAY"], $userRequest["PERSONAL_GENDER"], $errors);
            if(!$res){
                foreach($errors as $error_key => $error_message){
                    switch ($error_key){
                        case 'userExists':{
                            $this->addError(new Error(
                                "Пользователь не найден",
                                "illegal_user"
                            ));
                            $hasErrors = true;
                            break;
                        }
                    }
                }
            }
        }
        if(isset($request["oldPassword"]) || isset($request["newPassword"]) || isset($request["confirmPassword"])){
            if(!isset($request["oldPassword"])){
                $hasErrors = true;
                $this->addError(new Error(
                    "Не указан старый пароль",
                    "old_password_invalid"
                ));
            }
            else if(!isset($request["newPassword"])){
                $hasErrors = true;
                $this->addError(new Error(
                    "Не указан новый пароль",
                    "new_password_invalid"
                ));
            }
            else if(!isset($request["confirmPassword"])){
                $hasErrors = true;
                $this->addError(new Error(
                    "Новый пароль не подтвержден",
                    "confirm_password_invalid"
                ));
            }
            else{
                $errors = [];
                $res = EditPassClass::EditPassClassMethod($request["id"], $request["oldPassword"], $request["newPassword"], $request["confirmPassword"], $errors);
                if(!$res){
                    foreach($errors as $error_key => $error_message){
                        switch ($error_key){
                            case 'NEW_PASSWORD':{
                                $this->addError(new Error(
                                    "Новый пароль не совпадает с подтверждением",
                                    "passwords_illegal"
                                ));
                                $hasErrors = true;
                                break;
                            }
                            case "CURRENT_PASSWORD":{
                                $this->addError(new Error(
                                    "Текущий пароль неверный",
                                    "old_password_illegal"
                                ));
                                $hasErrors = true;
                                break;
                            }
                            case 'userExists':{
                                $this->addError(new Error(
                                    "Пользователь не найден",
                                    "illegal_user"
                                ));
                                $hasErrors = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
        if($hasErrors){
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, "site.api", $this);
        }
        else return [];
    }

    protected function reArrayFiles(&$file_post){
        $isMulti = is_array($file_post['name']);
        $file_count = $isMulti?count($file_post['name']):1;
        $file_keys = array_keys($file_post);

        $file_ary = [];    //Итоговый массив
        for($i=0; $i<$file_count; $i++)
            foreach($file_keys as $key)
                if($isMulti)
                    $file_ary[$i][$key] = $file_post[$key][$i];
                else
                    $file_ary[$i][$key]    = $file_post[$key];

        return $file_ary;
    }
}
