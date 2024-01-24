<?php

namespace Site\Api\Controllers;

use Bitrix\Main\Engine\Controller;

/**
 * CsrfTokenController class
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class CsrfTokenController extends Controller
{
    protected function prepareParams(): bool
    {
        return parent::prepareParams();
    }

    public function getCsrfAction()
    {
        return ["sessid" => bitrix_sessid()];
    }

    public function getDefaultPreFilters():array
    {
        return [];
    }
}
