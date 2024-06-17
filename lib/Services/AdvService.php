<?php

namespace Site\Api\Services;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\FileTable;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
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
use Site\Api\Enum\ModelRules;

class AdvService extends ServiceBase
{
    protected const FIELDS = [
        'id' => array(
            "type" => FieldType::SCALAR,
            "rule" => ModelRules::READ,
        ),
        'name' => array(
            'type' => FieldType::SCALAR,
            "rule" => ModelRules::READ
        ),
        'photo' => array(
            'field' => 'PREVIEW_PICTURE',
            'type' => FieldType::PHOTO,
            "rule" => ModelRules::READ
        ),
        'desc' => array(
            'field' => 'description.VALUE',
            'type' => FieldType::SCALAR,
            "rule" => ModelRules::READ
        ),
        'adv_link' => array(
            'field' => 'link.VALUE',
            'type' => FieldType::SCALAR,
            "rule" => ModelRules::READ
        )
    ];

    public function getList():array
    {
        $queryParams = $this->getQueryParams();
        $serverHost = (Context::getCurrent()->getRequest()->isHttps()?"https://":"http://").Context::getCurrent()->getServer()->getHttpHost();
        $queryParams["select"]["photo"] = "FULL_PATH";
        $queryParams["runtime"] = [
            "PREVIEW_ALIAS" => [
                "data_type" => FileTable::class,
                "reference" => [
                    "=this.PREVIEW_PICTURE" => "ref.ID"
                ]
            ],
            new ExpressionField('FULL_PATH', 'CONCAT("'.$serverHost.'/upload/", %s, "/", %s)', ["PREVIEW_ALIAS.SUBDIR", "PREVIEW_ALIAS.FILE_NAME"])
        ];
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