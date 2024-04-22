<?php

namespace Site\Api\Controllers;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Response;
use Bitrix\Main\Web\Json;
use Site\Api\Exceptions\FilterException;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\Csrf;
use Site\Api\Prefilters\Validator;
use Site\Api\Services\Validation;

class ReviewController extends Controller
{
    public function getListAction(): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $reviewService = $serviceLocator->get("site.api.review");
        try{
            $els = $reviewService->getList();
            $this->navData = $reviewService->getNavigationData();
            return $els;
        }
        catch (FilterException $e){
            $this->addError(new Error($e->getMessage(), FilterException::FILTER_EXCEPTION_CODE));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
    }

    public function getUserReviewsAction(): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $reviewService = $serviceLocator->get("site.api.review");
        try{
            $els = $reviewService->getUserReviews();
            $this->navData = $reviewService->getNavigationData();
            return $els;
        }
        catch (FilterException $e){
            $this->addError(new Error($e->getMessage(), FilterException::FILTER_EXCEPTION_CODE));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
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
                "+prefilters" => [
                    new Validator([
                        (new Validation("id"))->required()->number()
                    ])
                ],
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "getUserReviews" => [
                "+prefilters" => [
                    new Authentication()
                ],
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
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