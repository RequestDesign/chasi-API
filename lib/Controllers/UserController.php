<?php

namespace Site\Api\Controllers;

use Site\Api\Prefilters\ApiKey;
use Bitrix\Main\Engine\Controller;
use Site\Api\Parameters\LoginParameter;
use Site\Api\Parameters\SignupParameter;
use Site\Api\Traits\ControllerTrait;

/**
 * UserController
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class UserController extends Controller
{
    use ControllerTrait;

    public function configureActions(): array
    {
        return [
            'signup' => [
                'prefilters' => [
                    new ApiKey()
                ],
                'postfilters' => []
            ],
            'login' => [
                'prefilters' => [
                    new ApiKey()
                ],
                'postfilters' => []
            ]
        ];
    }

    protected function prepareParams(): bool
    {
        return parent::prepareParams();
    }

    public function signupAction()
    {
        return $this->postParameters(new SignupParameter());
    }

    public function loginAction()
    {
        return $this->postParameters(new LoginParameter());
    }
}
