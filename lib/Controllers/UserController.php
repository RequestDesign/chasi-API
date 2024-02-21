<?php

namespace Site\Api\Controllers;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Site\Api\Exceptions\RegisterException;
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
}
