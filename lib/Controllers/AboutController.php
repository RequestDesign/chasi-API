<?php

namespace Site\Api\Controllers;

use Bitrix\Main\Loader;

Loader::includeModule('iblock');

use Bitrix\Iblock\Iblock;
use \Bitrix\Main\Engine\Controller;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\Csrf;

class AboutController extends Controller
{
    const IB_ID = 7;

    public function getOneAction() {
        $entityDataClass = Iblock::wakeUp(self::IB_ID)->getEntityDataClass();
        $el = $entityDataClass::getList([
            "select" => ["ID", "description"=>"TEXT.VALUE"],
            "order" => ["sort"=>"asc"]
        ])->fetch();
        if($el){
            $el["description"] = unserialize($el["description"])["TEXT"];
        }
        return $el;
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
            "getOne" => [
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ]
        ];
    }
}