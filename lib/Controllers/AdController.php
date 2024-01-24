<?php

namespace Site\Api\Controllers;

use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use \Bitrix\Main\Engine\Controller;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\ApiKey;
use Site\Api\Prefilters\Csrf;

class AdController extends Controller
{
    public function getListAction(): array
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        return $adService->getList();
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
            ]
        ];
    }
}