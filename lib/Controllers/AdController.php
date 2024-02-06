<?php

namespace Site\Api\Controllers;

use Bitrix\Main\DI\ServiceLocator;
use \Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Response;
use Bitrix\Main\Web\Json;
use Site\Api\Exceptions\FilterException;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\FilterReorder;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\Csrf;

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