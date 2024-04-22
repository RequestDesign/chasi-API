<?php

namespace Site\Api\Controllers;

use Bitrix\Main\Loader;

Loader::includeModule('iblock');

use Bitrix\Iblock\Iblock;
use \Bitrix\Main\Engine\Controller;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\Csrf;

class FAQController extends Controller
{
    const IB_ID = 8;

    public function getListAction() {
        $entityDataClass = Iblock::wakeUp(self::IB_ID)->getEntityDataClass();
        $els = $entityDataClass::getList([
            "select" => ["ID", "NAME", "description"=>"PREVIEW_TEXT"],
            "order" => ["sort"=>"asc"]
        ])->fetchAll();
        return $els;
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