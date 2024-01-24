<?php

namespace Site\Api\Entity;

class UserFieldEnumTable extends \Bitrix\Main\Entity\DataManager
{
    const DEF = 'Y';
    const NOT_DEF = 'N';

    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_user_field_enum';
    }

    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
            ),
            'USER_FIELD_ID' => array(
                'data_type' => 'integer'
            ),
            'VALUE' => array(
                'data_type' => 'string',
                'required' => true
            ),
            'DEF' => array(
                'data_type' => 'boolean',
                'values' => array(self::DEF, self::NOT_DEF),
                'required' => true
            ),
            'SORT' => array(
                'data_type' => 'integer',
                'required' => true
            ),
            'XML_ID' => array(
                'data_type' => 'string',
                'required' => true
            )
        );
    }
}