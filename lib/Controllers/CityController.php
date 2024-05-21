<?php

namespace Site\Api\Controllers;

use \Bitrix\Main\Engine\Controller;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\Csrf;

class CityController extends Controller
{
    public function getListAction() {
        $dataJson = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/ajax/city.json");
        return json_decode($dataJson, true);
    }

    protected function getDefaultPreFilters():array
    {
        return [
            new Csrf()
        ];
    }

    public function configureActions():array
    {
        return [];
    }
}