<?php

namespace Site\Api\Services;

use Bitrix\Main\Application;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\ORM\Query\Result;
use Site\Api\Entity\UserFieldEnumTable;
use Site\Api\Enum\FieldType;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');
Loader::includeModule('highloadblock');

use Bitrix\Iblock\Iblock;
use Bitrix\Highloadblock\HighloadBlockTable;

class ServiceBase
{
    protected const FIELDS = [];
    private const SORT_DIRECTIONS = ["ASC", "DESC"];
    protected array $select = [];
    protected array $sort = [];
    private int $limit = 0;
    private int $page = 1;
    /**
     * @var array
     */
    public array $request;

    public function __construct()
    {
        $this->request = Application::getInstance()->getContext()->getRequest()->toArray();
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
                if (array_key_exists($curField, $self::FIELDS)){
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
                else $this->sort[strtoupper($sort_field)] = $direction;
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

    final function getQueryParams():array
    {
        $this->select()->sort()->paginate();
        $queryParams = [];
        $queryParams["select"] = [];
        foreach ($this->select as $alias => $selectField){
            if(array_key_exists("type", $selectField)){
                switch ($selectField["type"]){
                    case FieldType::IB_EL:{
                        if(array_key_exists("ref_id", $selectField) &&
                            array_key_exists("ref_field", $selectField) &&
                            array_key_exists("fields", $selectField) &&
                            array_key_exists("field", $selectField)){
                            if(!array_key_exists("runtime", $queryParams)) $queryParams["runtime"] = [];
                            foreach($selectField["fields"] as $field_alias => $field){
                                if(is_int($field_alias)){
                                    $queryParams["select"][$alias."|".$field] = $alias.".".$field;
                                }
                                else{
                                    $queryParams["select"][$alias."|".$field_alias] = $alias.".".$field;
                                }
                            }
                            $queryParams["runtime"][$alias] = [
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
                            if(!array_key_exists("runtime", $queryParams)) $queryParams["runtime"] = [];
                            foreach($selectField["fields"] as $field_alias => $field){
                                if(is_int($field_alias)){
                                    $queryParams["select"][$alias."|".$field] = $alias.".".$field;
                                }
                                else{
                                    $queryParams["select"][$alias."|".$field_alias] = $alias.".".$field;
                                }
                            }
                            $queryParams["runtime"][$alias] = [
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
                            if(!array_key_exists("runtime", $queryParams)) $queryParams["runtime"] = [];
                            $queryParams["select"][$alias] = $selectField["field"]."_ALIAS.VALUE";
                            $queryParams["runtime"][$selectField["field"]."_ALIAS"] = [
                                'data_type' => UserFieldEnumTable::class,
                                'reference' => [
                                    '=this.'.$selectField["field"] => 'ref.ID'
                                ],
                                ['join_type' => 'left']
                            ];
                        }
                        break;
                    }
                    default: {
                        if(array_key_exists("field", $selectField)){
                            $queryParams["select"][$alias] = $selectField["field"];
                        }
                        else $queryParams["select"][] = $alias;
                        break;
                    }
                }
            }
        }
        if(count($this->sort)){
            $queryParams["order"] = $this->sort;
        }
        if($this->limit > 0){
            $queryParams["limit"] = $this->limit;
            $queryParams["offset"] = ($this->page - 1) * $this->limit;
        }
        $queryParams["count_total"] = true;

        $this->addAdditionalParams($queryParams);

        return $queryParams;
    }

    protected function addAdditionalParams(&$queryParams):void
    {}

    protected function addPaginationParams(array &$elements, Result &$dbElements):void
    {
        $elements["total"] = count($elements)?$dbElements->getCount():0;
        if($this->limit > 0){
            $elements["limit"] = $this->limit;
            $elements["page"] = $this->page;
            $elements["first_page"] = 1;
            $elements["last_page"] = ceil($elements["total"]/$elements["limit"]);
            if($this->page > 1 && $this->page <= $elements["last_page"]){
                $elements["prev_page"] = $this->page - 1;
            }
            else if($this->page > $elements["last_page"]){
                $elements["prev_page"] = $elements["last_page"];
            }
            if($this->page < $elements["last_page"]){
                $elements["next_page"] = $this->page + 1;
            }
        }
    }
}