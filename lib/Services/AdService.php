<?php

namespace Site\Api\Services;

require_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/SearchDataClass.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/CurrencyRateClass.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/OrderClass.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/ListFavouritesClass.php");

use Bitrix\Iblock\Iblock;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Filter\Condition;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\UserTable;
use Lib\HighloadBlock\WatchHighloadBlock;
use \SearchDataClass;
use CurrencyRateClass;
use OrderClass;
use ListFavouritesClass;


Loader::includeModule('highloadblock');
Loader::includeModule('iblock');

use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use Site\Api\Entity\UserFieldEnumTable;
use Site\Api\Enum\FieldType;
use Site\Api\Enum\FilterType;
use Site\Api\Enum\ModelRules;
use Site\Api\Exceptions\AdNotFoundAuthException;
use Site\Api\Exceptions\CreateException;
use Site\Api\Exceptions\EditException;
use Site\Api\Exceptions\FilterException;
use Site\Api\Exceptions\PublishException;


class AdService extends ServiceBase
{
    public const AD_HL_ID = 1;
    public const POSTED = 44;
    public const MOVING = 45;
    public const MODERATED = 46;
    public const UNPAYED = 43;
    public const CLOSED = 50;
    public const EXPIRED = 49;
    public const DRAFT = 40;
    public const REJECTED = 41;
    protected const FIELDS = [
        "id" => array(
            "type"=> FieldType::SCALAR,
            "rule"=> ModelRules::READ
        ),
        "brand" => array(
            "field"=>"UF_BRAND",
            "type"=> FieldType::IB_EL,
            "ref_id" => 1,
            "ref_field" => "ID",
            "fields" => ["ID", "NAME", "CODE"],
            "filter_type" => FilterType::ARRAY,
            "filter_name" => "ID",
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "model" => array(
            "field"=>"UF_MODEL",
            "type"=> FieldType::SCALAR,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "condition" => array(
            "field"=>"UF_SOST",
            "type"=>FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "gender" => array(
            "field"=>"UF_POL",
            "type"=>FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "year" => array(
            "field"=>"UF_GOD",
            "type"=>FieldType::SCALAR,
            "filter_type" => FilterType::RANGE_INT,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "mechanism" => array(
            "field"=>"UF_MEXANIZM",
            "type"=>FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "frame_color" => array(
            "field"=>"UF_COLOR",
            "type"=> FieldType::IB_EL,
            "ref_id" => 2,
            "ref_field" => "ID",
            "fields" => ["ID","NAME", "COLOR"=>"HEX.VALUE"],
            "filter_type" => FilterType::ARRAY,
            "filter_name" => "ID",
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "dial_color" => array(
            "field"=>"UF_COLOR_CIFER",
            "type"=>FieldType::IB_EL,
            "ref_id" => 2,
            "ref_field" => "ID",
            "fields" => ["ID","NAME", "COLOR"=>"HEX.VALUE"],
            "filter_type" => FilterType::ARRAY,
            "filter_name" => "ID",
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "country" => array(
            "field" => "UF_COUNTRY",
            "type" => FieldType::IB_EL,
            "ref_id" => 3,
            "ref_field" => "ID",
            "fields" => ["ID", "NAME"],
            "filter_type" => FilterType::ARRAY,
            "filter_name" => "ID",
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "seller_type" => array(
            "field" => "UF_PRODAVEC",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "material" => array(
            "field" => "UF_MATERIAL",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "form" => array(
            "field" => "UF_FORMA",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "size" => array(
            "field" => "UF_RAZMER",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "watchband" => array(
            "field" => "UF_REMEN",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "clasp" => array(
            "field" => "UF_ZASTEZHKA",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "dial" => array(
            "field" => "UF_CIFERBLAT",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "water_protection" => array(
            "field" => "UF_VODO",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "description" => array(
            "field" => "UF_DESC",
            "type" => FieldType::SCALAR,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "price" => array(
            "field" => "UF_PRICE",
            "type" => FieldType::SCALAR,
            "filter_type" => FilterType::RANGE_FLOAT,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "photo" => array(
            "field" => "UF_FOTO",
            "type" => FieldType::PHOTO,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "city" => array(
            "field" => "UF_TOWN",
            "type" => FieldType::SCALAR,
            "filter_type" => FilterType::ARRAY,
            "rule" => ModelRules::READ
        ),
        "user" => array(
            "field" => "UF_USER_ID",
            "type" => FieldType::USER,
            "rule" => ModelRules::READ
        ),
        "promotion" => array(
            "field" => "UF_PROMOT",
            "type" => FieldType::BOOL,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "promotion_type" => array(
            "field" => "UF_PROMOTION",
            "type" => FieldType::IB_EL,
            "ref_id" => 6,
            "ref_field" => "ID",
            "fields" => ["ID", "NAME"],
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
        "status" => array(
            "field" => "UF_STATUS",
            "type" => FieldType::ULIST,
            "rule" => ModelRules::READ
        ),
        "date_created" => array(
            "field" => "UF_CREATE_DATE",
            "type" => FieldType::SCALAR,
            "rule" => ModelRules::READ
        ),
        "documents_list" => array(
            "field" => "UF_AVAILABILITY_OF_DOCUMENTS",
            "type" => FieldType::IB_EL,
            "ref_id" => 9,
            "ref_field" => "ID",
            "fields" => ["ID", "NAME"],
            "filter_type" => FilterType::ARRAY,
            "filter_name" => "ID",
            "rule" => ModelRules::CREATE
        ),
        "documents_description" => array(
            "field" => "UF_AVAILABILITY_OF_DOCUMENTS_TXT",
            "type" => FieldType::SCALAR,
            "rule" => ModelRules::CREATE
        ),
        "video" => array(
            "field" => "UF_VIDEO",
            "type" => FieldType::SCALAR,
            "rule" => ModelRules::CREATE | ModelRules::READ
        ),
    ];

    /**
     * @return array
     */
    public function getList($params = []):array
    {
        $request = Application::getInstance()->getContext()->getRequest()->toArray();
        $queryParams = $this->getQueryParams();
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        $queryParams["filter"]->addCondition(ConditionTree::createFromArray([["UF_STATUS", 'in', [self::POSTED, self::MOVING]]]));
        if(isset($params["filter"])){
            $addFilter = ConditionTree::createFromArray($params["filter"]);
            //$queryParams["filter"]->addCondition();
            foreach($addFilter->getConditions() as $condition){
                $hasCondition = false;
                foreach ($queryParams["filter"]->getConditions() as $conditionTree){
                    if($conditionTree instanceof ConditionTree){
                        foreach ($conditionTree->getConditions() as $conditionNested){
                            if($condition->getColumn() == $conditionNested->getColumn()){
                                $conditionTree->replaceCondition($conditionNested, $condition);
                                $hasCondition = true;
                            }
                        }
                    }
                    else if($conditionTree instanceof Condition){
                        if($condition->getColumn() == $conditionTree->getColumn()){
                            $queryParams["filter"]->replaceCondition($conditionTree, $condition);
                            $hasCondition = true;
                        }
                    }
                }
                if(!$hasCondition){
                    $queryParams["filter"]->addCondition($condition);
                }
            }
            unset($params["filter"]);
        }
        $queryParams["filter"] = $this->conditionTreeToArray($queryParams["filter"]);
        if(isset($request["q"]) && $request["q"]){
            $searchFilter = [];
            $searchRuntime = [];
            SearchDataClass::GetSearchClassData($request["q"], $searchFilter, $searchRuntime);
            $arFilter = $queryParams["filter"] ?? [];
            $arRuntime = $queryParams["runtime"] ?? [];
            $arFilter = array_merge($arFilter, $searchFilter);
            $arRuntime = array_merge($arRuntime, $searchRuntime);
            $queryParams["filter"] = $arFilter;
            $queryParams["runtime"] = $arRuntime;
        }
        $queryParams = array_merge($queryParams, $params);
        $dbElements = $entity_data_class::getList($queryParams);
        $elements = $dbElements->fetchAll();
        if(count($elements) && array_key_exists('photo', $elements[0])){
            $allPhotoIds = array_unique(array_reduce($elements, function ($carry, $item){
                return array_merge($carry, $item['photo']);
            }, []));
            $serverHost = (Context::getCurrent()->getRequest()->isHttps()?"https://":"http://").Context::getCurrent()->getServer()->getHttpHost();
            $files = FileTable::getList([
                "select"=>["ID","FULL_PATH"],
                "filter"=>["=ID"=>$allPhotoIds],
                "runtime"=>[
                    new ExpressionField('FULL_PATH', 'CONCAT("'.$serverHost.'/upload/", %s, "/", %s)', ["SUBDIR", "FILE_NAME"])
                ]
            ])->fetchAll();
            $files = array_reduce($files, function($carry, $item){
                $carry[$item["ID"]] = $item['FULL_PATH'];
                return $carry;
            });
            foreach($elements as &$element){
                foreach($element["photo"] as &$photo){
                    $photo = strval($photo);
                    $photo = $files[$photo];
                }
            }
        }
        $this->addPaginationParams($dbElements);
        $favorites = ListFavouritesClass::ListFavouritesClassMethod();
        foreach ($elements as &$element){
            $element["favorite"] = in_array($element["ID"], $favorites);
        }
        return $elements;
    }

    public function create($params = []):\Bitrix\Main\Entity\AddResult
    {
        global $USER;
        $createData = $this->getCreateData();
        $createData["UF_STATUS"] = isset($createData["UF_PROMOT"]) ?
                                        $createData["UF_PROMOT"]        ?
                                            self::UNPAYED               :
                                            self::MODERATED        :
                                        self::MODERATED;
        $createData = array_merge($createData, $params);
        $createData["UF_ACTIVE"] = "Y";
        $city = UserTable::getById($USER->GetID())->fetch()["PERSONAL_CITY"];
        $createData["UF_TOWN"] = $city;
        $wh = new WatchHighloadBlock();
        return $wh->create($createData);
    }

    public function getPayUrl($id){
        global $USER;
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        $el = $entity_data_class::getByPrimary($id, [
            "select" => [
                "ID",
                "UF_PROMOT",
                "promotion_type_id" => "promotion_type_alias.ID",
                "promotion_price" => "promotion_type_alias.PRICE.VALUE"
            ],
            "filter" => [
                "UF_USER_ID" => $USER->GetID()
            ],
            "runtime" => [
                "promotion_type_alias" => [
                    "data_type" => Iblock::wakeUp(6)->getEntityDataClass(),
                    "reference" => [
                        "=this.UF_PROMOTION" => "ref.ID"
                    ],
                    ["join_type"=>"left"]
                ]
            ]
        ])->fetch();
        if(!$el) throw new AdNotFoundAuthException("Объявление не существует");
        if($el["UF_PROMOT"] && $el["promotion_type_id"] && $el["promotion_price"]){
            $errors = [];
            return OrderClass::OrderClassMethod($USER->GetID(), $el["promotion_type_id"], $el["ID"], $errors);
        }
        return null;
    }

    public function edit():\Bitrix\Main\Entity\UpdateResult
    {
        global $USER;
        $createData = $this->getCreateData();
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        $el = $entity_data_class::getByPrimary($this->request['id'], [
            "select" => ["status"=>"status_alias.VALUE"],
            "runtime" => [
                "status_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_STATUS" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ]
            ]
        ])->fetch();
        if(!$el){
            throw new EditException(message: "Не существует элемента с переданным id");
        }
        if(in_array($el["status"], [self::POSTED, self::MOVING])){
            $createData["UF_STATUS"] = self::MODERATED;
        }
        $createData["UF_ACTIVE"] = "Y";
        $wh = new WatchHighloadBlock();
        return $wh->update($this->request["id"], $createData);
    }

    public function getCreateValues(){
        $createValues = [];
        $createValues["brand"] = [];
        $brands = Iblock::wakeUp(self::FIELDS["brand"]["ref_id"])->getEntityDataClass()::getList([
           "select" => self::FIELDS["brand"]["fields"],
           "order" => ["ID"=>"ASC"]
        ])->fetchCollection();
        foreach ($brands as $brand){
            $createValues["brand"][] = [
                "id" => $brand->getId(),
                "name" => $brand->getName(),
                "code" => $brand->getCode()
            ];
        }
        $createValues["condition"] = self::getPropsList("condition");
        $createValues["gender"] = self::getPropsList("gender");
        $createValues["mechanism"] = self::getPropsList("mechanism");

        $createValues["frame_color"] = [];
        $frameColors = Iblock::wakeUp(self::FIELDS["frame_color"]["ref_id"])->getEntityDataClass()::getList([
            "select" => ["ID", "NAME", "HEX"],
            "order" => ["ID"=>"ASC"]
        ])->fetchCollection();
        foreach ($frameColors as $frameColor){
            $createValues["frame_color"][] = [
                "id" => $frameColor->getId(),
                "name" => $frameColor->getName(),
                "color" => $frameColor->getHex()->getValue()
            ];
        }

        $createValues["dial_color"] = $createValues["frame_color"];

        $createValues["country"] = [];
        $countries = Iblock::wakeUp(self::FIELDS["country"]["ref_id"])->getEntityDataClass()::getList([
            "select" => ["ID", "NAME"],
            "order" => ["ID"=>"ASC"]
        ])->fetchCollection();
        foreach ($countries as $country){
            $createValues["country"][] = [
                "id" => $country->getId(),
                "name" => $country->getName()
            ];
        }

        $createValues["seller_type"] = self::getPropsList("seller_type");
        $createValues["material"] = self::getPropsList("material");
        $createValues["form"] = self::getPropsList("form");
        $createValues["size"] = self::getPropsList("size");
        $createValues["watchband"] = self::getPropsList("watchband");
        $createValues["clasp"] = self::getPropsList("clasp");
        $createValues["dial"] = self::getPropsList("dial");
        $createValues["water_protection"] = self::getPropsList("water_protection");

        $createValues["promotion_type"] = [];
        $promotionTypes = Iblock::wakeUp(self::FIELDS["promotion_type"]["ref_id"])->getEntityDataClass()::getList([
            "select" => ["ID", "NAME", "PREVIEW_TEXT", "PRICE", "LIST"],
            "order" => ["ID"=>"ASC"]
        ])->fetchCollection();
        foreach ($promotionTypes as $promotionType){
            $list = [];
            foreach ($promotionType->getList()->getAll() as $value){
                $list[] = $value->getValue();
            }
            $createValues["promotion_type"][] = [
                "id" => $promotionType->getId(),
                "name" => $promotionType->getName(),
                "description" => $promotionType->getPreviewText(),
                "price" => $promotionType->getPrice()?->getValue(),
                "list" => $list
            ];
        }

        $createValues["documents_list"] = [];
        $documentLists = Iblock::wakeUp(self::FIELDS["documents_list"]["ref_id"])->getEntityDataClass()::getList([
            "select" => ["ID", "NAME"],
            "order" => ["ID"=>"ASC"]
        ])->fetchCollection();
        foreach ($documentLists as $documentList){
            $createValues["documents_list"][] = [
                "id" => $documentList->getId(),
                "name" => $documentList->getName()
            ];
        }
        /*foreach(self::FIELDS as $alias => $field){
            if(($field["rule"] & ModelRules::CREATE) > 0){
                switch ($field["type"]){
                    case FieldType::IB_EL:{
                        $createValues[$alias] = Iblock::wakeUp($field["ref_id"])->getEntityDataClass()::getList([
                            "select" => $field["fields"],
                            "order" => ["ID" => "ASC"]
                        ])->fetchAll();
                        break;
                    }
                    case FieldType::ULIST:{
                        $createValues[$alias] = UserFieldEnumTable::getList([
                            "select" => ["ID", "VALUE"],
                            "filter" => ["USER_FIELD.FIELD_NAME" => $field["field"]],
                            "runtime" => [
                                "USER_FIELD" => [
                                    "data_type" => UserFieldTable::class,
                                    "reference" => [
                                        "=this.USER_FIELD_ID" => "ref.ID"
                                    ],
                                    ["join_type"=>"left"]
                                ]
                            ]
                        ])->fetchAll();
                    }
                }
            }
        }*/
        return $createValues;
    }

    public function getFilter(){
        $filter = [];
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        $additionalFilter = ["=UF_STATUS" => [self::POSTED, self::MOVING]];
        foreach(self::FIELDS as $alias => $field){
            if(array_key_exists("filter_type", $field)){
                switch ($field["filter_type"]){
                    case FilterType::ARRAY:{
                        switch ($field["type"]){
                            case FieldType::IB_EL:{
                                $select = [];
                                foreach($field["fields"] as $field_alias=>$field_name){
                                    if(is_int($field_alias)){
                                        $select[$alias."|".$field_name] = $alias.".".$field_name;
                                    }
                                    else{
                                        $select[$alias."|".$field_alias] = $alias.".".$field_name;
                                    }
                                }
                                $filter = array_merge($filter, $entity_data_class::getList([
                                    "select" => $select,
                                    "group" => [$field["field"]],
                                    "filter" => array_merge(["!".$field["field"] => ''], $additionalFilter),
                                    "runtime" => [
                                        $alias => [
                                            'data_type' => Iblock::wakeUp($field["ref_id"])->getEntityDataClass(),
                                            'reference' => [
                                                '=this.'.$field["field"] => 'ref.'.$field["ref_field"]
                                            ],
                                            ['join_type' => 'left']
                                        ]
                                    ],
                                    "order" => [$field["field"] => "ASC"]
                                ])->fetchAll());
                                break;
                            }
                            case FieldType::ULIST:{
                                $filter = array_merge($filter, $entity_data_class::getList([
                                    "select" => [$alias."|ID" => $field["field"], $alias."|NAME"=>$field["field"]."_ALIAS.VALUE"],
                                    "filter" => array_merge(["!".$field["field"] => ''], $additionalFilter),
                                    "order" => [$field["field"] => "ASC"],
                                    "group" => [$field["field"]],
                                    "runtime" => [
                                        $field["field"]."_ALIAS" => [
                                            'data_type' => UserFieldEnumTable::class,
                                            'reference' => [
                                                '=this.'.$field["field"] => 'ref.ID'
                                            ],
                                            ['join_type' => 'left']
                                        ]
                                    ]
                                ])->fetchAll());
                                break;
                            }
                            case FieldType::SCALAR:{
                                $filter = array_merge($filter, $entity_data_class::getList([
                                    "select" => [$alias => $field["field"]],
                                    "filter" => array_merge(["!".$field["field"] => ''], $additionalFilter),
                                    "order" => [$field["field"] => "ASC"],
                                    "group" => [$field["field"]]
                                ])->fetchAll());
                            }
                        }
                        break;
                    }
                    case FilterType::RANGE_INT:case FilterType::RANGE_FLOAT:{
                        switch ($field["type"]){
                            case FieldType::SCALAR:{
                                $filter = array_merge($filter, $entity_data_class::getList([
                                    "select" => [$alias."|MIN"=>"MIN", $alias."|MAX"=>"MAX"],
                                    "filter" => array_merge(["!".$field["field"] => ''], $additionalFilter),
                                    "runtime" => [
                                        new ExpressionField("MIN", "MIN(%s)", [$field["field"]]),
                                        new ExpressionField("MAX", "MAX(%s)", [$field["field"]])
                                    ]
                                ])->fetchAll());
                                break;
                            }
                        }
                        break;
                    }
                }
            }
        }
        return $filter;
    }

    public function getOne(){
        $id = $this->request["id"];
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        $serverHost = (Context::getCurrent()->getRequest()->isHttps()?"https://":"http://").Context::getCurrent()->getServer()->getHttpHost();
        $el = $entity_data_class::getByPrimary($id, [
            "select" => [
                "ID", 'photo'=>"UF_FOTO", "brand|ID"=>"brand_alias.ID",
                "brand|NAME"=>"brand_alias.NAME", "brand_id"=>"brand_alias.ID",
                "model"=>"UF_MODEL", "year"=>"UF_GOD", "price"=>"UF_PRICE",
                "condition|ID"=>"condition_alias.ID", "condition|NAME"=>"condition_alias.VALUE",
                "gender|ID"=>"gender_alias.ID", "gender|NAME"=>"gender_alias.VALUE",
                "mechanism|ID"=>"mechanism_alias.ID", "mechanism|NAME"=>"mechanism_alias.VALUE", "mechanism_id"=>"mechanism_alias.ID",
                "frame_color|ID"=>"color_alias.ID", "frame_color|NAME"=>"color_alias.NAME",
                "country|ID"=>"country_alias.ID", "country|NAME"=>"country_alias.NAME",
                "material|ID"=>"material_alias.ID", "material|NAME"=>"material_alias.VALUE",
                "form|ID"=>"form_alias.ID", "form|NAME"=>"form_alias.VALUE",
                "size|ID"=>"size_alias.ID", "size|NAME"=>"size_alias.VALUE",
                "watchband|ID"=>"watchband_alias.ID", "watchband|NAME"=>"watchband_alias.VALUE",
                "clasp|ID"=>"clasp_alias.ID", "clasp|NAME"=>"clasp_alias.VALUE",
                "dial|ID"=>"dial_alias.ID", "dial|NAME"=>"dial_alias.VALUE",
                "dial_color|ID"=>"dial_color_alias.ID", "dial_color|NAME"=>"dial_color_alias.NAME",
                "water_protection|ID"=>"water_protection_alias.ID", "water_protection|NAME"=>"water_protection_alias.VALUE",
                "description"=>"UF_DESC", "date_created"=>"UF_CREATE_DATE",
                "seller_type|ID"=>"seller_type_alias.ID", "seller_type|NAME"=>"seller_type_alias.VALUE",
                "user|id"=>"user_alias.ID", "user|name"=>"user_alias.NAME",
                "user|city"=>"user_alias.PERSONAL_CITY", "user|photo" => "FULL_PATH",
                "user|phone"=>"user_alias.PERSONAL_MOBILE",
                "documents_list|ID" => "documents_list_alias.ID", "documents_list|NAME" => "documents_list_alias.NAME",
                "documents_description" => "UF_AVAILABILITY_OF_DOCUMENTS_TXT", "video" => "UF_VIDEO",
                "promotion" => "UF_PROMOT",
                "promotion_type|ID" => "promotion_type_alias.ID", "promotion_type|NAME" => "promotion_type_alias.NAME",
                "status|ID" => "status_alias.ID", "status|NAME" => "status_alias.VALUE"],
            "runtime" => [
                "brand_alias" => [
                    "data_type" => Iblock::wakeUp(1)->getEntityDataClass(),
                    "reference" => [
                        "=this.UF_BRAND" => "ref.ID"
                    ],
                    ["join_type"=>"left"]
                ],
                "condition_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_SOST" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "gender_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_POL" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "mechanism_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_MEXANIZM" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "color_alias" => [
                    "data_type" => Iblock::wakeUp(2)->getEntityDataClass(),
                    "reference" => [
                        "=this.UF_COLOR" => "ref.ID"
                    ],
                    ["join_type"=>"left"]
                ],
                "country_alias" => [
                    "data_type" => Iblock::wakeUp(3)->getEntityDataClass(),
                    "reference" => [
                        "=this.UF_COUNTRY" => "ref.ID"
                    ],
                    ["join_type"=>"left"]
                ],
                "material_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_MATERIAL" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "form_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_FORMA" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "size_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_RAZMER" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "watchband_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_REMEN" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "clasp_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_ZASTEZHKA" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "dial_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_CIFERBLAT" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "dial_color_alias" => [
                    "data_type" => Iblock::wakeUp(2)->getEntityDataClass(),
                    "reference" => [
                        "=this.UF_COLOR_CIFER" => "ref.ID"
                    ],
                    ["join_type"=>"left"]
                ],
                "water_protection_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_VODO" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "seller_type_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_PRODAVEC" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
                "user_alias" => [
                    "data_type" => UserTable::class,
                    "reference" => [
                        "=this.UF_USER_ID"=>"ref.ID"
                    ],
                    ["join_type"=>"left"]
                ],
                "documents_list_alias" => [
                    "data_type" => Iblock::wakeUp(9)->getEntityDataClass(),
                    "reference" => [
                        "=this.UF_AVAILABILITY_OF_DOCUMENTS" => "ref.ID"
                    ],
                    ["join_type"=>"left"]
                ],
                "promotion_type_alias" => [
                    "data_type" => Iblock::wakeUp(6)->getEntityDataClass(),
                    "reference" => [
                        "=this.UF_PROMOTION" => "ref.ID"
                    ],
                    ["join_type"=>"left"]
                ],
                "status_alias" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_STATUS" => "ref.ID"
                    ],
                    ["join_type"=>"left"]
                ],
                "user_photo" => [
                    "data_type" => FileTable::class,
                    "reference" => [
                        "=this.user_alias.PERSONAL_PHOTO" => "ref.ID"
                    ],
                    ['join_type' => 'left']
                ],
                new ExpressionField('FULL_PATH', 'CONCAT("'.$serverHost.'/upload/", %s, "/", %s)', ["user_photo.SUBDIR", "user_photo.FILE_NAME"])
            ]
        ])->fetch();
        if(!$el) return null;
        if(count($el['photo'])){
            $files = FileTable::getList([
                "select"=>["ID","FULL_PATH"],
                "filter"=>["=ID"=>$el["photo"]],
                "runtime"=>[
                    new ExpressionField('FULL_PATH', 'CONCAT("'.$serverHost.'/upload/", %s, "/", %s)', ["SUBDIR", "FILE_NAME"])
                ]
            ])->fetchAll();
            $el['photo'] = array_map(function($el){
                    return $el["FULL_PATH"];
                }, $files);
        }
        $el["user|adv_count"] = $entity_data_class::getList([
            'runtime' => [
                new ExpressionField('CNT', 'COUNT(*)'),
            ],
            "select" => ["CNT"],
            "filter" => ["=UF_USER_ID" => $el["user|id"]]
        ])->fetch()["CNT"];
        $moreEls = $entity_data_class::getList([
            "select" => ["ID"],
            "filter" => ["=UF_BRAND"=>$el["brand_id"], "=UF_MEXANIZM"=>$el["mechanism_id"]]
        ])->fetchAll();
        $el["more"] = array_column($moreEls, "ID");
        unset($el["brand_id"], $el["mechanism_id"]);
        $currencies = CurrencyRateClass::GetCurrencyRate();
        $el["currencies"] = [
            "usd" => round(((float)$el["price"] / (float)$currencies["Valute"]["USD"]["Value"]), 2),
            "eur" => round(((float)$el["price"] / (float)$currencies["Valute"]["EUR"]["Value"]), 2)
        ];
        $favorites = ListFavouritesClass::ListFavouritesClassMethod();
        $el["favorite"] = in_array($el["ID"], $favorites);
        return $el;
    }

    public static function getPropsList($propName){
        return UserFieldEnumTable::getList([
            "select" => ["ID", "VALUE"],
            "filter" => ["USER_FIELD.FIELD_NAME" => self::FIELDS[$propName]["field"]],
            "runtime" => [
                "USER_FIELD" => [
                    "data_type" => UserFieldTable::class,
                    "reference" => [
                        "=this.USER_FIELD_ID" => "ref.ID"
                    ],
                    ["join_type"=>"left"]
                ]
            ]
        ])->fetchAll();
    }

    public function publish(){
        global $USER;
        $id = $this->request["id"];
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $el = $entity_data_class::getByPrimary($id, [
            "select" => ["ID", "UF_STATUS", "STATUS_NAME"=>"UF_STATUS_ALIAS.VALUE" ],
            "filter" => ["ID" => $id, "UF_USER_ID"=>$USER->GetID()],
            "runtime" => [
                "UF_STATUS_ALIAS" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_STATUS" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
            ]
        ])->fetch();

        if(!$el){
            throw new AdNotFoundAuthException("Объявление не существует");
        }

        if(!in_array($el["UF_STATUS"], [self::UNPAYED, self::EXPIRED, self::DRAFT])){
            throw new PublishException("Нельзя опубликовать объявление со статусом \"{$el["STATUS_NAME"]}\"");
        }
        $editData = [
            "UF_STATUS" => self::MODERATED
        ];
        if($el["UF_STATUS"] != self::DRAFT){
            $editData["UF_PROMOT"] = 0;
        }
        $wh = new WatchHighloadBlock();
        return $wh->update($this->request["id"], $editData);
    }

    public function archieve(){
        global $USER;
        $id = $this->request["id"];
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $el = $entity_data_class::getByPrimary($id, [
            "select" => ["ID", "UF_STATUS", "STATUS_NAME"=>"UF_STATUS_ALIAS.VALUE" ],
            "filter" => ["ID" => $id, "UF_USER_ID"=>$USER->GetID()],
            "runtime" => [
                "UF_STATUS_ALIAS" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_STATUS" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
            ]
        ])->fetch();

        if(!$el){
            throw new AdNotFoundAuthException("Объявление не существует");
        }

        if(!in_array($el["UF_STATUS"], [self::UNPAYED, self::DRAFT, self::REJECTED, self::POSTED, self::MOVING, self::MODERATED])){
            throw new PublishException("Нельзя добавить в архив объявление со статусом \"{$el["STATUS_NAME"]}\"");
        }
        $editData = [
            "UF_STATUS" => self::CLOSED
        ];
        $wh = new WatchHighloadBlock();
        return $wh->update($this->request["id"], $editData);
    }

    public function promote(){
        global $USER;
        $id = $this->request["id"];
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $el = $entity_data_class::getByPrimary($id, [
            "select" => ["ID", "UF_STATUS", "STATUS_NAME"=>"UF_STATUS_ALIAS.VALUE" ],
            "filter" => ["ID" => $id, "UF_USER_ID"=>$USER->GetID()],
            "runtime" => [
                "UF_STATUS_ALIAS" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_STATUS" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
            ]
        ])->fetch();

        if(!$el){
            throw new AdNotFoundAuthException("Объявление не существует");
        }

        if(!in_array($el["UF_STATUS"], [self::POSTED])){
            throw new PublishException("Нельзя продвигать объявление со статусом \"{$el["STATUS_NAME"]}\"");
        }
        $editData = [
            "UF_STATUS" => self::UNPAYED,
            "UF_PROMOT" => "Y",
            "UF_PROMOTION" => $this->request["promotionType"]
        ];
        $wh = new WatchHighloadBlock();
        return $wh->update($this->request["id"], $editData);
    }

    public function pay(){
        global $USER;
        $id = $this->request["id"];
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $el = $entity_data_class::getByPrimary($id, [
            "select" => ["ID", "UF_STATUS", "STATUS_NAME"=>"UF_STATUS_ALIAS.VALUE" ],
            "filter" => ["ID" => $id, "UF_USER_ID"=>$USER->GetID()],
            "runtime" => [
                "UF_STATUS_ALIAS" => [
                    "data_type" => UserFieldEnumTable::class,
                    "reference" => [
                        "=this.UF_STATUS" => "ref.ID",
                    ],
                    ["join_type"=>"left"]
                ],
            ]
        ])->fetch();

        if(!$el){
            throw new AdNotFoundAuthException("Объявление не существует");
        }

        if(!in_array($el["UF_STATUS"], [self::UNPAYED])){
            throw new PublishException("Нельзя продвигать объявление со статусом \"{$el["STATUS_NAME"]}\"");
        }

        $url = $this->getPayUrl($el["ID"]);
        return [
            "id" => $el["ID"],
            "url" => $url ? $url : null
        ];
    }

    public function delete(){
        global $USER;
        $id = $this->request["id"];
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $el = $entity_data_class::getByPrimary($id, [
            "select" => ["ID"],
            "filter" => ["ID" => $id, "UF_USER_ID"=>$USER->GetID()]
        ])->fetch();

        if(!$el){
            throw new AdNotFoundAuthException("Объявление не существует");
        }

        $watchTable = new WatchHighloadBlock();
        $res = $watchTable->delete($id);
        if ($res->isSuccess()) return $id;
        else return $res;
    }

    public function conditionTreeToArray(ConditionTree $conditionTree)
    {
        $logic = $conditionTree->logic();

        $children = [];
        foreach ($conditionTree->getConditions() as $child) {
            if ($child instanceof Condition) {
                // Если это ExpressionField, добавляем его как условие в массив
                $prefix = "";
                if($child->hasMultiValues()){
                    $prefix = "=";
                }
                if(in_array($child->getOperator(), [">", ">=", "<", "<="])){
                    $prefix = $child->getOperator();
                }
                $children[] = [$prefix.$child->getColumn() => $child->getValue()];
            } elseif ($child instanceof ConditionTree) {
                // Если это другой ConditionTree, рекурсивно вызываем функцию для него
                $children[] = $this->conditionTreeToArray($child);
            }
        }

        return $children;
    }
}