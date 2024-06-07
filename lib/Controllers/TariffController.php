<?php

namespace Site\Api\Controllers;

use Bitrix\Main\Loader;

Loader::includeModule('iblock');

use Bitrix\Iblock\Iblock;
use \Bitrix\Main\Engine\Controller;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\Csrf;


class TariffController extends Controller
{
    const IB_ID = 6;

    public function getListAction() {
        $entityDataClass = Iblock::wakeUp(self::IB_ID)->getEntityDataClass();
        $els = $entityDataClass::getList([
            "select" => ["ID", "NAME", "additionalText"=>"LIST.VALUE", "description"=>"PREVIEW_TEXT", "PRICE.VALUE"],
            "order" => ["sort"=>"asc"]
        ])->fetchCollection();
        $resList = [];
        while($el = $els->current()){
            $res = [];
            $res["id"] = strval($el->get('ID'));
            $res["name"] = $el->get("NAME");
            $res["additionalText"] = [];
            foreach ($el->get('LIST')->getAll() as $value){
                $res["additionalText"][] = $value->getValue();
            }
            $res["description"] = $el->get('PREVIEW_TEXT');
            $res["price"] = $el->getPrice()["VALUE"];
            $resList[] = $res;
            $els->next();
        }
        return $resList;
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