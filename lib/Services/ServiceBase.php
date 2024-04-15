<?php

namespace Site\Api\Services;

use Bitrix\Iblock\ORM\Query;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\FileTable;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Json;
use Site\Api\Entity\UserFieldEnumTable;
use Site\Api\Enum\FieldType;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');
Loader::includeModule('highloadblock');

use Bitrix\Iblock\Iblock;
use Bitrix\Highloadblock\HighloadBlockTable;
use Site\Api\Enum\FilterType;
use Site\Api\Exceptions\CreateException;
use Site\Api\Exceptions\FilterException;
use Site\Api\Enum\ModelRules;

class ServiceBase
{
    protected const FIELDS = [];
    private const SORT_DIRECTIONS = ["ASC", "DESC"];
    protected array $select = [];
    protected array $sort = [];
    protected int $limit = 16;
    protected int $page = 1;
    protected array $filter = [];
    protected array $navData = [];
    private array $queryParams = [];
    /**
     * @var array
     */
    public array $request;
    /**
     * @var Application
     */
    public Application $app;
    public array $files;

    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->request = $this->app->getContext()->getRequest()->toArray();
        $this->files = $this->app->getContext()->getRequest()->getFileList()->toArray();
    }

    final function select():ServiceBase
    {
        $self = get_called_class();
        $fields = [];
        if(array_key_exists('fields', $this->request)){
            $fields = explode(',', $this->request["fields"]);
        }
        if (count($fields)) {
            $this->select = [];
            for ($i = 0; $i < count($fields); $i++) {
                $curField = $fields[$i];
                if (array_key_exists($curField, $self::FIELDS) && (($self::FIELDS[$curField]["rule"] & ModelRules::READ) > 0)){
                    $this->select[$curField] = $self::FIELDS[$curField];
                }
            }
        }
        if (!count($this->select)){
            $this->select = $self::FIELDS;
        }
        return $this;
    }

    final function sort():ServiceBase
    {
        $sort = [];
        $self = get_called_class();
        $sortConventer = new Converter(Converter::TO_SNAKE|Converter::TO_UPPER);
        if(array_key_exists('sort', $this->request)){
            $sortParams = explode(',', $this->request['sort']);
            if(is_array($sortParams) && count($sortParams)){
                for($i = 0; $i < count($sortParams); $i++){
                    if($sortParams[$i]!=="" && strcmp($sortParams[$i], "-") !== 0){
                        if($sortParams[$i][0] === '-'){
                            $sort[substr($sortParams[$i], 1)] = "DESC";
                        }
                        else{
                            $sort[$sortParams[$i]] = "ASC";
                        }
                    }
                }
            }
        }
        foreach($sort as $sort_field=>$direction){
            if(array_key_exists($sort_field, $self::FIELDS) && in_array($direction, self::SORT_DIRECTIONS, true))
            {
                if(array_key_exists("field", $self::FIELDS[$sort_field])){
                    $this->sort[$self::FIELDS[$sort_field]['field']] = $direction;
                }
                else $this->sort[$sortConventer->process($sort_field)] = $direction;
            }
        }
        return $this;
    }

    final function paginate():ServiceBase
    {
        $limit = 0;
        if(array_key_exists('limit', $this->request)){
            if(ctype_digit($this->request['limit'])) $limit = intval($this->request['limit']);
        }
        $page = 1;
        if(array_key_exists('page', $this->request)){
            if(ctype_digit($this->request['page'])) $page = intval($this->request["page"]);
        }
        if($limit > 0) $this->limit = $limit;
        if($page > 1) $this->page = $page;
        return $this;
    }

    final function filter():ServiceBase
    {
        $filter = [];
        $self = get_called_class();
        $converter = new Converter(Converter::TO_SNAKE|Converter::TO_LOWER);
        $rangeSigns = ["<", ">", "<=", ">="];

        if(array_key_exists('filter', $this->request) && !empty($this->request['filter'])){
            try{
                $filter_raw = Json::decode($this->request["filter"]);
            }
            catch (ArgumentException $e){
                throw new FilterException("Не удалось декодировать json строку");
            }
            if(!is_array($filter_raw)){
                throw new FilterException("Параметр filter должен быть объектом");
            }
            foreach ($filter_raw as $key => $filter_value){
                $matches = [];
                $op = null;
                if(preg_match('/^([<>]=?)([a-zA-Z]+)$/', $key, $matches)){
                    $key = $matches[2];
                    $op = $matches[1];
                }
                $filter_key = $converter->process($key);

                if(!array_key_exists($filter_key, $self::FIELDS)){
                    throw new FilterException("Параметр ".$key." не существует");
                }
                switch($self::FIELDS[$filter_key]["filter_type"]){
                    case FilterType::ARRAY:{
                        if(!is_array($filter_value)){
                            throw new FilterException("Тип параметра ".$key." должен быть array");
                        }
                        if(empty($filter_value)){
                            throw new FilterException("Параметр ".$key." не должен быть пустым");
                        }
                        if($op){
                            throw new FilterException("Параметр ".$key." не должен содержать знак ".$op);
                        }
                        $filter[] = [
                            $filter_key, "in", $filter_value
                        ];
                        break;
                    }
                    case FilterType::RANGE_INT:{
                        if(!is_int($filter_value)){
                            throw new FilterException("Тип параметра ".$key." должен быть int");
                        }
                        if(!in_array($op, $rangeSigns)){
                            throw new FilterException("Параметр ".$key." должен содержать знаки <, >, <= или >=");
                        }
                        $filter[] = [
                            $filter_key, $op, $filter_value
                        ];
                        break;
                    }
                    case FilterType::RANGE_FLOAT:{
                        if(!is_int($filter_value) && !is_float($filter_value)){
                            throw new FilterException("Тип параметра ".$key." должен быть float");
                        }
                        if(!in_array($op, $rangeSigns)){
                            throw new FilterException("Параметр ".$key." должен содержать знаки <, >, <= или >=");
                        }
                        $filter[] = [
                            $filter_key, $op, $filter_value
                        ];
                        break;
                    }
                    default:
                        throw new \Exception('Unexpected value');
                }
            }
        }
        $this->filter = $filter;
        return $this;
    }

    final function getQueryParams():array
    {
        $self = get_called_class();
        $upperConverter = new Converter(Converter::TO_UPPER);
        $this->select()->filter()->sort()->paginate();
        $this->queryParams = [];
        $this->queryParams["select"] = [];
        foreach ($this->select as $alias => $selectField){
            if(array_key_exists("type", $selectField)){
                switch ($selectField["type"]){
                    case FieldType::IB_EL:{
                        if(array_key_exists("ref_id", $selectField) &&
                            array_key_exists("ref_field", $selectField) &&
                            array_key_exists("fields", $selectField) &&
                            array_key_exists("field", $selectField)){
                            if(!array_key_exists("runtime", $this->queryParams)) $this->queryParams["runtime"] = [];
                            foreach($selectField["fields"] as $field_alias => $field){
                                if(is_int($field_alias)){
                                    $this->queryParams["select"][$alias."|".$field] = $alias.".".$field;
                                }
                                else{
                                    $this->queryParams["select"][$alias."|".$field_alias] = $alias.".".$field;
                                }
                            }
                            $this->queryParams["runtime"][$alias] = [
                                'data_type' => Iblock::wakeUp($selectField["ref_id"])->getEntityDataClass(),
                                'reference' => [
                                    '=this.'.$selectField["field"] => 'ref.'.$selectField["ref_field"]
                                ],
                                ['join_type' => 'left']
                            ];
                        }
                        break;
                    }
                    case FieldType::HLBL_EL:{
                        if(array_key_exists("ref_id", $selectField) &&
                            array_key_exists("ref_field", $selectField) &&
                            array_key_exists("fields", $selectField) &&
                            array_key_exists("field", $selectField)){
                            if(!array_key_exists("runtime", $this->queryParams)) $this->queryParams["runtime"] = [];
                            foreach($selectField["fields"] as $field_alias => $field){
                                if(is_int($field_alias)){
                                    $this->queryParams["select"][$alias."|".$field] = $alias.".".$field;
                                }
                                else{
                                    $this->queryParams["select"][$alias."|".$field_alias] = $alias.".".$field;
                                }
                            }
                            $this->queryParams["runtime"][$alias] = [
                                'data_type' => HighloadBlockTable::compileEntity(HighloadBlockTable::getById($selectField["ref_id"])->fetch())->getDataClass(),
                                'reference' => [
                                    '=this.'.$selectField["field"] => 'ref.'.$selectField["ref_field"]
                                ],
                                ['join_type' => 'left']
                            ];
                        }
                        break;
                    }
                    case FieldType::ULIST:{
                        if(array_key_exists("field", $selectField)){
                            if(!array_key_exists("runtime", $this->queryParams)) $this->queryParams["runtime"] = [];
                            $this->queryParams["select"][$alias] = $selectField["field"]."_ALIAS.VALUE";
                            $this->queryParams["runtime"][$selectField["field"]."_ALIAS"] = [
                                'data_type' => UserFieldEnumTable::class,
                                'reference' => [
                                    '=this.'.$selectField["field"] => 'ref.ID'
                                ],
                                ['join_type' => 'left']
                            ];
                        }
                        break;
                    }
                    case FieldType::USER:{
                        if(array_key_exists("field", $selectField)){
                            $user_fields = [
                                $alias."|ID"=>$alias."_ALIAS.ID",
                                $alias."|NAME"=>$alias."_ALIAS.NAME",
                                $alias."|LAST_NAME"=>$alias."_ALIAS.LAST_NAME",
                                $alias."|CITY" => $alias."_ALIAS.PERSONAL_CITY",
                                $alias."|PHOTO" => "USER_FULL_PATH"
                            ];
                            foreach ($user_fields as $user_field_key => $user_field_value) {
                                $this->queryParams["select"][$user_field_key] = $user_field_value;
                            }
                            $serverHost = (Context::getCurrent()->getRequest()->isHttps()?"https://":"http://").Context::getCurrent()->getServer()->getHttpHost();

                            $this->queryParams["runtime"][$alias."_ALIAS"] = [
                                'data_type' => UserTable::class,
                                'reference' => [
                                    '=this.'.$selectField["field"] => 'ref.ID'
                                ],
                                ['join_type' => 'left']
                            ];
                            $this->queryParams["runtime"][$alias."_photo"] = [
                                "data_type" => FileTable::class,
                                "reference" => [
                                    "=this.".$alias."_ALIAS.PERSONAL_PHOTO" => "ref.ID"
                                ],
                                ["join_type" => "left"]
                            ];
                            $this->queryParams["runtime"][] = new ExpressionField('USER_FULL_PATH', 'CONCAT("'.$serverHost.'/upload/", %s, "/", %s)', [$alias."_photo.SUBDIR", $alias."_photo.FILE_NAME"]);
                        }
                        break;
                    }
                    case FieldType::PHOTO:{
                        /*if(array_key_exists("field", $selectField)){
                            if(!array_key_exists("runtime", $this->queryParams)) $this->queryParams["runtime"] = [];
                            $this->queryParams["select"][] = $alias;
                            $this->queryParams["runtime"]["PICTURE_ALIAS"] = [
                                'data_type' => FileTable::class,
                                'reference' => [
                                    '=this.'.$selectField["field"] => 'ref.ID'
                                ],
                                ['join_type' => 'left']
                            ];
                            $this->queryParams["runtime"][] = new ExpressionField($alias, 'CONCAT("'.(Context::getCurrent()->getRequest()->isHttps()?"https://":"http://").Context::getCurrent()->getServer()->getHttpHost().'/upload/", %s, "/", %s)', ["PICTURE_ALIAS.SUBDIR", "PICTURE_ALIAS.FILE_NAME"]);
                        }
                        break;*/
                    }
                    default: {
                        if(array_key_exists("field", $selectField)){
                            $this->queryParams["select"][$alias] = $selectField["field"];
                        }
                        else $this->queryParams["select"][] = $alias;
                        break;
                    }
                }
            }
        }
        if(!empty($this->filter)){
            foreach ($this->filter as &$filter_el){
                $field_data = $self::FIELDS[$filter_el[0]];
                switch($field_data["type"]){
                    case FieldType::IB_EL:{
                        if(array_key_exists("field", $field_data) &&
                            array_key_exists("filter_name", $field_data) &&
                            array_key_exists("ref_id", $field_data) &&
                            array_key_exists("ref_field", $field_data)){
                            if(!array_key_exists($filter_el[0], $this->select)){
                                if(!array_key_exists("runtime", $this->queryParams)) $this->queryParams["runtime"] = [];
                                $this->queryParams["runtime"][$filter_el[0]] = [
                                    'data_type' => Iblock::wakeUp($field_data["ref_id"])->getEntityDataClass(),
                                    'reference' => [
                                        '=this.'.$field_data["field"] => 'ref.'.$field_data["ref_field"]
                                    ],
                                    ['join_type' => 'left']
                                ];
                            }
                            $filter_el[0] = $filter_el[0].'.'.$field_data["filter_name"];
                        }
                        break;
                    }
                    case FieldType::ULIST:{
                        if(array_key_exists("field", $field_data)){
                            $filter_el[0] = $field_data["field"];
                        }
                    }
                    default:{
                        if(array_key_exists("field", $field_data)){
                            $filter_el[0] = $field_data["field"];
                        }
                        else {
                            $filter_el[0] = $upperConverter->process($filter_el[0]);
                        }
                        break;
                    }
                }
            }
            $this->queryParams["filter"] = ConditionTree::createFromArray($this->filter);
        }
        else {
            $this->queryParams["filter"] = ConditionTree::createFromArray([]);
        }
        if(count($this->sort)){
            $this->queryParams["order"] = $this->sort;
        }
        if($this->limit > 0){
            $this->queryParams["limit"] = $this->limit;
            $this->queryParams["offset"] = ($this->page - 1) * $this->limit;
        }
        $this->queryParams["count_total"] = true;

        $this->addAdditionalParams($this->queryParams);

        return $this->queryParams;
    }

    protected function addAdditionalParams(&$queryParams):void
    {}

    protected function addPaginationParams(Result &$dbElements):void
    {
        $navData = [
            "total" => $dbElements->getCount()
        ];
        if($this->limit > 0){
            $navData["limit"] = $this->limit;
            $navData["page"] = $this->page;
            $navData["first_page"] = 1;
            $navData["last_page"] = ceil($navData["total"]/$navData["limit"]);
            if($this->page > 1 && $this->page <= $navData["last_page"]){
                $navData["prev_page"] = $this->page - 1;
            }
            else if($this->page > $navData["last_page"]){
                $navData["prev_page"] = $navData["last_page"];
            }
            if($this->page < $navData["last_page"]){
                $navData["next_page"] = $this->page + 1;
            }
        }
        $this->navData = $navData;
    }

    public function getNavigationData():array{
        return $this->navData;
    }

    final function getCreateData():array
    {
        $create = [];
        $self = get_called_class();
        $converter = new Converter(Converter::TO_SNAKE|Converter::TO_LOWER);
        foreach($this->request as $key => $value){
            $convertKey = $converter->process($key);
            $field = $self::FIELDS[$convertKey];
            if(($field["rule"] & ModelRules::CREATE) > 0) {
                switch($self::FIELDS[$convertKey]["type"]){
                    case FieldType::IB_EL:{
                        $res = Iblock::wakeUp($field["ref_id"])->getEntityDataClass()::getById($value);
                        if(!($el = $res->fetch())){
                            throw new CreateException(message: "Не существует элемента с переданным id", field:$key);
                        }
                        $create[$field["field"]] = $value;
                        break;
                    }
                    case FieldType::SCALAR:{
                        $create[$field["field"]] = $value;
                        break;
                    }
                    case FieldType::ULIST:{
                        $res = UserFieldEnumTable::getList([
                            "select" => ["ID"],
                            "filter" => ["ID" => $value, "USER_FIELD.FIELD_NAME" => $field["field"]],
                            "runtime" => [
                                "USER_FIELD" => [
                                    "data_type" => UserFieldTable::class,
                                    "reference" => [
                                        "=this.USER_FIELD_ID" => "ref.ID"
                                    ],
                                    ["join_type"=>"left"]
                                ]
                            ]
                        ]);
                        if (!$res->fetch()){
                            throw new CreateException(message: "Не существует элемента с переданным id", field:$key);
                        }
                        $create[$field["field"]] = $value;
                        break;
                    }
                    case FieldType::BOOL:{
                        $create[$field["field"]] = $value === '1'?1:0;
                    }
                }
            }
        }
        foreach($this->files as $key => $files){
            $convertKey = $converter->process($key);
            $field = $self::FIELDS[$convertKey];
            if($field["rule"] & ModelRules::CREATE > 0) {
                switch($self::FIELDS[$convertKey]["type"]){
                    case FieldType::PHOTO:{
                        $ids = [];
                        $files = $this->reArrayFiles($files);
                        foreach($files as &$file){
                            $imageinfo = getimagesize($file["tmp_name"]);
                            $file["type"] = $imageinfo["mime"];
                            $id = \CFile::SaveFile($file, 'uf');
                            if($id) {
                                $ids[] = \CFile::MakeFileArray($id);
                            }
                        }
                        $create[$field["field"]] = $ids;
                        break;
                    }
                }
            }
        }
        return $create;
    }

    protected function reArrayFiles(&$file_post){
        $isMulti = is_array($file_post['name']);
        $file_count = $isMulti?count($file_post['name']):1;
        $file_keys = array_keys($file_post);

        $file_ary = [];    //Итоговый массив
        for($i=0; $i<$file_count; $i++)
            foreach($file_keys as $key)
                if($isMulti)
                    $file_ary[$i][$key] = $file_post[$key][$i];
                else
                    $file_ary[$i][$key]    = $file_post[$key];

        return $file_ary;
    }
}