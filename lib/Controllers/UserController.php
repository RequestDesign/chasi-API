<?php

namespace Site\Api\Controllers;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\UserTable;
use Site\Api\Exceptions\RegisterException;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Prefilters\Csrf;
use Bitrix\Main\Engine\Controller;
use Site\Api\Prefilters\Validator;
use Site\Api\Services\Validation;

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
                        (new Validation("email"))->email()->required(),
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
                    ]),
                    new Authentication()
                ],
                "postfilters" => [
                    new ChangeKeyCase()
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
        catch (RegisterException $e){
            $this->addError(new Error($e->getMessage(), $e->getExceptionCode()));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
    }

    public function getOneAction():array|EventResult
    {
        $request = $this->getRequest()->toArray();
        $user = UserTable::getByPrimary($request["id"],
            ["select" => ['id', 'EMAIL', 'ACTIVE', 'NAME', 'LAST_NAME', 'phone'=>'PERSONAL_PHONE', 'city'=>'PERSONAL_CITY']])->fetch();
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
}
