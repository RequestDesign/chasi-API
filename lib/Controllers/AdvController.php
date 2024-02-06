<?php

namespace Site\Api\Controllers;

use Bitrix\Main\EventResult;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Response;
use Bitrix\Main\Web\Json;
use Site\Api\Exceptions\FilterException;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\Csrf;

class AdvController extends Controller
{
    private array $navData = [];
    public function getListAction():array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $advService = $serviceLocator->get("site.api.adv");
        $els = $advService->getList();
        $this->navData = $advService->getNavigationData();
        return $els;
    }

    public function configureActions():array
    {
        return [
            'getList' => [
                'postfilters' => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ]
        ];
    }

    protected function getDefaultPreFilters():array
    {
        return [
            new Csrf()
        ];
    }

    public function finalizeResponse(Response $response){
        $data = $response->getContent();
        $data = Json::decode($data);
        $data = array_merge($data, ["_metadata" => $this->navData]);
        $response->setContent(Json::encode($data));
    }
}