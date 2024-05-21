<?php

namespace Site\Api\Services;

use Bitrix\Iblock\Iblock;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Site\Api\Enum\FieldType;
use Site\Api\Enum\ModelRules;

class ReviewService extends ServiceBase
{
    const IB_ID = 4;

    protected const FIELDS = [
        "id" => array(
            "type"=> FieldType::SCALAR,
            "rule"=> ModelRules::READ
        ),
        "user" => array(
            "field" => "OT_KOGO.VALUE",
            "type" => FieldType::USER,
            "rule" => ModelRules::READ
        ),
        "to" => array(
            "field" => "KOMU.VALUE",
            "type" => FieldType::USER,
            "rule" => ModelRules::READ
        ),
        "date_created" => array(
            "field" => "DATE_CREATE",
            "type" => FieldType::SCALAR,
            "rule" => ModelRules::READ
        ),
        "rating" => array(
            "field" => "RATE.VALUE",
            "type" => FieldType::SCALAR,
            "rule" => ModelRules::READ
        ),
        "polite" => array(
            "field" => "VEZHLIV.VALUE",
            "type" => FieldType::IB_LIST,
            "rule" => ModelRules::READ
        ),
        "conscientious" => array(
            "field" => "DOBRO.VALUE",
            "type" => FieldType::IB_LIST,
            "rule" => ModelRules::READ
        ),
        "quick_reply" => array(
            "field" => "BYSTRO.VALUE",
            "type" => FieldType::IB_LIST,
            "rule" => ModelRules::READ
        ),
        "rude" => array(
            "field" => "XAMIT.VALUE",
            "type" => FieldType::IB_LIST,
            "rule" => ModelRules::READ
        ),
        "unfair" => array(
            "field" => "NECHES.VALUE",
            "type" => FieldType::IB_LIST,
            "rule" => ModelRules::READ
        ),
        "long_reply" => array(
            "field" => "DOLGO.VALUE",
            "type" => FieldType::IB_LIST,
            "rule" => ModelRules::READ
        )
    ];

    public function getList():array {
        $request = Application::getInstance()->getContext()->getRequest()->toArray();
        $queryParams = $this->getQueryParams();
        $entityDataClass = Iblock::wakeUp(self::IB_ID)->getEntityDataClass();
        $queryParams["filter"]->addCondition(ConditionTree::createFromArray([["KOMU.VALUE", '=', $request["id"]]]));
        $dbElements = $entityDataClass::getList($queryParams);
        $elements = $dbElements->fetchAll();
        $this->addPaginationParams($dbElements);
        return $elements;
    }

    public function getUserReviews():array {
        global $USER;
        $queryParams = $this->getQueryParams();
        $entityDataClass = Iblock::wakeUp(self::IB_ID)->getEntityDataClass();
        $queryParams["filter"]->addCondition(ConditionTree::createFromArray([["OT_KOGO.VALUE", '=', $USER->GetID()]]));
        $dbElements = $entityDataClass::getList($queryParams);
        $elements = $dbElements->fetchAll();
        $this->addPaginationParams($dbElements);
        return $elements;
    }
}