<?php

namespace Site\Api\Services;

use Bitrix\Iblock\Iblock;
use Bitrix\Main\Context;
use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;

Loader::includeModule('highloadblock');
Loader::includeModule('iblock');

use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use Site\Api\Entity\UserFieldEnumTable;
use Site\Api\Enum\FieldType;
use Site\Api\Enum\FilterType;
use Site\Api\Exceptions\FilterException;


class AdService extends ServiceBase
{

    private const AD_HL_ID = 1;
    protected const FIELDS = [
        "id" => array(
            "type"=> FieldType::SCALAR
        ),
        "brand" => array(
            "field"=>"UF_BRAND",
            "type"=> FieldType::IB_EL,
            "ref_id" => 1,
            "ref_field" => "ID",
            "fields" => ["ID", "NAME", "CODE"],
            "filter_type" => FilterType::ARRAY,
            "filter_name" => "ID"
        ),
        "model" => array(
            "field"=>"UF_MODEL",
            "type"=> FieldType::SCALAR
        ),
        "condition" => array(
            "field"=>"UF_SOST",
            "type"=>FieldType::ULIST,
            "filter_type" => FilterType::ARRAY
        ),
        "gender" => array(
            "field"=>"UF_POL",
            "type"=>FieldType::ULIST,
            "filter_type" => FilterType::ARRAY
        ),
        "year" => array(
            "field"=>"UF_GOD",
            "type"=>FieldType::SCALAR,
            "filter_type" => FilterType::RANGE_INT
        ),
        "mechanism" => array(
            "field"=>"UF_MEXANIZM",
            "type"=>FieldType::ULIST,
            "filter_type" => FilterType::ARRAY
        ),
        "frame_color" => array(
            "field"=>"UF_COLOR",
            "type"=> FieldType::IB_EL,
            "ref_id" => 2,
            "ref_field" => "ID",
            "fields" => ["ID","NAME"],
            "filter_type" => FilterType::ARRAY,
            "filter_name" => "ID"
        ),
        "dial_color" => array(
            "field"=>"UF_COLOR_CIFER",
            "type"=>FieldType::IB_EL,
            "ref_id" => 2,
            "ref_field" => "ID",
            "fields" => ["ID","NAME"],
            "filter_type" => FilterType::ARRAY,
            "filter_name" => "ID"
        ),
        "country" => array(
            "field" => "UF_COUNTRY",
            "type" => FieldType::IB_EL,
            "ref_id" => 3,
            "ref_field" => "ID",
            "fields" => ["ID", "NAME"],
            "filter_type" => FilterType::ARRAY,
            "filter_name" => "ID"
        ),
        "seller_type" => array(
            "field" => "UF_PRODAVEC",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY
        ),
        "material" => array(
            "field" => "UF_MATERIAL",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY
        ),
        "form" => array(
            "field" => "UF_FORMA",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY
        ),
        "size" => array(
            "field" => "UF_RAZMER",
            "type" => FieldType::ULIST
        ),
        "watchband" => array(
            "field" => "UF_REMEN",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY
        ),
        "clasp" => array(
            "field" => "UF_ZASTEZHKA",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY
        ),
        "dial" => array(
            "field" => "UF_CIFERBLAT",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY
        ),
        "water_protection" => array(
            "field" => "UF_VODO",
            "type" => FieldType::ULIST,
            "filter_type" => FilterType::ARRAY
        ),
        "description" => array(
            "field" => "UF_DESC",
            "type" => FieldType::SCALAR
        ),
        "price" => array(
            "field" => "UF_PRICE",
            "type" => FieldType::SCALAR,
            "filter_type" => FilterType::RANGE_FLOAT
        ),
        "photo" => array(
            "field" => "UF_FOTO",
            "type" => FieldType::SCALAR
        ),
        "city" => array(
            "field" => "UF_TOWN",
            "type" => FieldType::SCALAR,
            "filter_type" => FilterType::ARRAY
        ),
        "user_id" => array(
            "field" => "UF_USER_ID",
            "type" => FieldType::SCALAR
        ),
        "promotion" => array(
            "field" => "UF_PROMOT",
            "type" => FieldType::SCALAR
        ),
        "promotion_type" => array(
            "field" => "UF_PROMOTION",
            "type" => FieldType::IB_EL,
            "ref_id" => 6,
            "ref_field" => "ID",
            "fields" => ["ID", "NAME"]
        ),
        "status" => array(
            "field" => "UF_STATUS",
            "type" => FieldType::ULIST
        ),
        "date_created" => array(
            "field" => "UF_CREATE_DATE",
            "type" => FieldType::SCALAR
        )
    ];

    /**
     * @return array
     */
    public function getList():array
    {
        $queryParams = $this->getQueryParams();
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

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
        return $elements;
    }

    public function getFilter(){
        $filter = [];
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        foreach(self::FIELDS as $alias => $field){
            if(array_key_exists("filter_type", $field)){
                switch ($field["filter_type"]){
                    case FilterType::ARRAY:{
                        switch ($field["type"]){
                            case FieldType::IB_EL:{
                                $filter = array_merge($filter, $entity_data_class::getList([
                                    "select" => [$alias."|ID"=>$field["field"], $alias."|NAME"=>$alias.".NAME"],
                                    "group" => [$field["field"]],
                                    "filter" => ["!".$field["field"] => ''],
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
                                    "filter" => ["!".$field["field"] => ''],
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
                                    "filter" => ["!".$field["field"] => ''],
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
                                    "filter" => ["!".$field["field"] => ''],
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
}