<?php

namespace Site\Api\Controllers;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\LoaderException;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Prefilters\Csrf;
use Bitrix\Main\Loader;

try {
    Loader::includeModule('iblock');
} catch (LoaderException $e) {
    $response = new Json(
        [
            "status"=>"error",
            "data"=>null,
            "errors"=>[
                "message" => "500 Internal error",
                "code" => "internal_error"
            ]
        ]
    );
    http_response_code(500);
    $response->send();
    exit();
}

class BrandController extends Controller
{
    public function getListAction():array
    {
        $elements = \Bitrix\Iblock\Elements\ElementBrandTable::getList([
            'select' => ['id', 'name', 'code'],
            'filter' => ['=ACTIVE'=>'Y']
        ])->fetchAll();
        return $elements;
    }

    public function configureActions():array
    {
        return [
            'getList' => [
                'postfilters' => [
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
}