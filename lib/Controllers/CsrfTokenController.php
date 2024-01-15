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
    public function configureActions(): array
    {
        return [
            'getCsrf' => [
                'prefilters' => [],
                'postfilters' => []
            ],
        ];
    }

    protected function prepareParams(): bool
    {
        return parent::prepareParams();
    }

    public function getCsrfAction()
    {
        return bitrix_sessid();
    }
}
