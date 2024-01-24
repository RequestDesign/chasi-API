<?php

namespace Site\Api\Services;

use Bitrix\Main\Application;
use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;

Loader::includeModule('highloadblock');
Loader::includeModule('iblock');

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Site\Api\Entity\UserFieldEnumTable;
use Site\Api\Enum\FieldType;


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
            "fields" => ["ID", "NAME", "CODE"]
        ),
        "model" => array(
            "field"=>"UF_MODEL",
            "type"=> FieldType::SCALAR
        ),
        "condition" => array(
            "field"=>"UF_SOST",
            "type"=>FieldType::ULIST
        ),
        "gender" => array(
            "field"=>"UF_POL",
            "type"=>FieldType::ULIST
        ),
        "year" => array(
            "field"=>"UF_GOD",
            "type"=>FieldType::SCALAR
        ),
        "mechanism" => array(
            "field"=>"UF_MEXANIZM",
            "type"=>FieldType::ULIST
        ),
        "frame_color" => array(
            "field"=>"UF_MAIN_COLOR_COMP",
            "type"=> FieldType::HLBL_EL,
            "ref_id" => 2,
            "ref_field" => "ID",
            "fields" => ["ID","NAME"=>"UF_NAME"]
        ),
        "dial_color" => array(
            "field"=>"UF_CIFER_COLOR_COMP",
            "type"=>FieldType::HLBL_EL,
            "ref_id" => 2,
            "ref_field" => "ID",
            "fields" => ["ID","NAME"=>"UF_NAME"]
        ),
        "country" => array(
            "field" => "UF_COUNTRY",
            "type" => FieldType::IB_EL,
            "ref_id" => 3,
            "ref_field" => "ID",
            "fields" => ["ID", "NAME"]
        ),
        "seller_type" => array(
            "field" => "UF_PRODAVEC",
            "type" => FieldType::ULIST
        ),
        "material" => array(
            "field" => "UF_MATERIAL",
            "type" => FieldType::ULIST
        ),
        "form" => array(
            "field" => "UF_FORMA",
            "type" => FieldType::ULIST
        ),
        "size" => array(
            "field" => "UF_RAZMER",
            "type" => FieldType::ULIST
        ),
        "watchband" => array(
            "field" => "UF_REMEN",
            "type" => FieldType::ULIST
        ),
        "clasp" => array(
            "field" => "UF_ZASTEZHKA",
            "type" => FieldType::ULIST
        ),
        "dial" => array(
            "field" => "UF_CIFERBLAT",
            "type" => FieldType::ULIST
        ),
        "water_protection" => array(
            "field" => "UF_VODO",
            "type" => FieldType::ULIST
        ),
        "description" => array(
            "field" => "UF_DESC",
            "type" => FieldType::SCALAR
        ),
        "price" => array(
            "field" => "UF_PRICE",
            "type" => FieldType::SCALAR
        ),
        "photo" => array(
            "field" => "UF_FOTO",
            "type" => FieldType::SCALAR
        ),
        "city" => array(
            "field" => "UF_TOWN",
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
            $files = FileTable::getList([
                "select"=>["ID","FULL_PATH"],
                "filter"=>["=ID"=>$allPhotoIds],
                "runtime"=>[
                    new ExpressionField('FULL_PATH', 'CONCAT("/upload/", %s, "/", %s)', ["SUBDIR", "FILE_NAME"])
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
        $this->addPaginationParams($elements, $dbElements);
        return $elements;
    }
}