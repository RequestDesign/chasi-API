<?php

namespace Site\Api\Controllers;

use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Site\Api\Prefilters\ApiKey;

class TestController extends Controller
{
    // public function configureActions(): array
    // {
    //     return [
    //         'get' => [
    //             'prefilters' => [
    //                 new ActionFilter\Csrf()
    //             ],
    //             'postfilters' => []
    //         ]
    //     ];
    // }

    public function getDefaultPreFilters()
    {
        return [
            new ApiKey()
        ];
    }

    protected function prepareParams(): bool
    {

        return parent::prepareParams();
    }

    public function getAction(array $params = [])
    {
        
        return ['API work!'];
    }
}
