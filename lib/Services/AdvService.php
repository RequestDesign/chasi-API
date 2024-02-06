<?php

namespace Site\Api\Services;

use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Loader;
use Site\Api\Enum\FieldType;

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

use Bitrix\Iblock\Iblock;

class AdvService extends ServiceBase
{
    protected const FIELDS = [
        'id' => array(
            "type" => FieldType::SCALAR
        ),
        'name' => array(
            'type' => FieldType::SCALAR
        ),
        'photo' => array(
            'field' => 'PREVIEW_PICTURE',
            'type' => FieldType::PHOTO
        ),
        'desc' => array(
            'field' => 'description.VALUE',
            'type' => FieldType::SCALAR
        )
    ];

    public function getList():array
    {
        $queryParams = $this->getQueryParams();
        $dbElements = Iblock::wakeUp(5)->getEntityDataClass()::getList($queryParams);
        $elements = $dbElements->fetchAll();
        foreach ($elements as &$element){
            $element["description"] = unserialize($element["desc"])["TEXT"];
            unset($element["desc"]);
        }
        $this->addPaginationParams($dbElements);
        return $elements;
    }
}