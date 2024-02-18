<?php

namespace Site\Api\Controllers;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use \Bitrix\Main\Engine\Controller;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Response;
use Bitrix\Main\Web\Json;
use Site\Api\Exceptions\CreateException;
use Site\Api\Exceptions\FilterException;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\FilterReorder;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\Csrf;
use Site\Api\Prefilters\Validator;
use Site\Api\Services\Validation;

class AdController extends Controller
{

    private array $navData = [];
    /**
     * @return array
     */
    public function getListAction(): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        try{
            $els = $adService->getList();
            $this->navData = $adService->getNavigationData();
            return $els;
        }
        catch (FilterException $e){
            $this->addError(new Error($e->getMessage(), FilterException::FILTER_EXCEPTION_CODE));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
    }

    public function getFilterAction(): array
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        return $adService->getFilter();
    }

    public function createAction(): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        try{
            $res = $adService->create();
            if(!$res->isSuccess()){
                foreach($res->getErrors() as $error){
                    $this->addError($error);
                }
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            return ["id" => $res->getId()];
        }
        catch (CreateException $e){
            $this->addError(new Error($e->getMessage(), CreateException::INVALID_CREATE_DATA, ["field"=>$e->getField()]));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
    }

    public function getCreateValuesAction(): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        return $adService->getCreateValues();
    }

    protected function getDefaultPreFilters():array
    {
        return [
            new Csrf()
        ];
    }

    public function configureActions():array
    {
        return [
            "getList" => [
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "getFilter" => [
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase(),
                    new FilterReorder()
                ]
            ],
            "getCreateValues" => [
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "create" => [
                "+prefilters" => [
                    new Authentication(),
                    //отсутствует наличие документов
                    new Validator([
                        (new Validation("brand"))->number()->required(),
                        (new Validation("model"))->required()->maxLength(255),
                        (new Validation("condition"))->required()->number(),
                        (new Validation("gender"))->required()->number(),
                        (new Validation("material"))->required()->number(),
                        (new Validation("watchband"))->required()->number(),
                        (new Validation("sellerType"))->required()->number(),
                        (new Validation("year"))->required()->number(),
                        (new Validation("mechanism"))->required()->number(),
                        (new Validation("frameColor"))->required()->number(),
                        (new Validation("country"))->required()->number(),
                        (new Validation("form"))->required()->number(),
                        (new Validation("size"))->required()->number(),
                        (new Validation("clasp"))->required()->number(),
                        (new Validation("dial"))->required()->number(),
                        (new Validation("dialColor"))->required()->number(),
                        (new Validation("waterProtection"))->required()->number(),
                        (new Validation("description"))->required()->maxLength(200),
                        (new Validation("photo"))->required()->image(3)->maxCount(10),
                        (new Validation("price"))->required()->price(),
                        (new Validation("promotion"))->required()->bool(),
                        (new Validation("promotionType"))->number()
                    ])
                ]
            ]
        ];
    }

    public function finalizeResponse(Response $response){
        $data = $response->getContent();
        $data = Json::decode($data);
        $data = array_merge($data, ["_metadata" => $this->navData]);
        $response->setContent(Json::encode($data));
    }
}