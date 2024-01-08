<?php

namespace Site\Api\Controllers;

use Site\Api\Prefilters\ApiKey;
use Bitrix\Main\Engine\Controller;
use Site\Api\Parameters\SignupParameter;
use Site\Api\Traits\ControllerTrait;

/**
 * SignupController class
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class SignupController extends Controller
{
    use ControllerTrait;

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

    public function getAction()
    {
        return $this->postParameters(new SignupParameter());
    }

}
