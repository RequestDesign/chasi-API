<?php

namespace Site\Api\Controllers;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Site\Api\Prefilters\ChangeKeyCase;
use Site\Api\Prefilters\Csrf;
use Site\Api\Services\Parameters\ConfirmRegistration;
use Site\Api\Services\Parameters\ForgetPasswordParameter;
use Site\Api\Services\Parameters\LoginParameter;
use Site\Api\Services\Parameters\RegistrationParameter;
use Site\Api\Services\Traits\ControllerTrait;

/**
 * AuthenticationController
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class AuthenticationController extends Controller
{
    use ControllerTrait;

    public function configureActions(): array
    {
        return [
            'registration' => [
                'prefilters' => [
                    new Csrf()
                ],
                'postfilters' => []
            ],
            'login' => [
                'prefilters' => [
                    new Csrf()
                ],
                'postfilters' => []
            ],
            'logout' => [
                'prefilters' => [
                    new Csrf(),
                    new ActionFilter\Authentication()
                ],
                'postfilters' => []
            ],
            'forgetPassword' => [
                'prefilters' => [
                    new Csrf()
                ],
                'postfilters' => [
                    new ChangeKeyCase()
                ]
            ],
            'confirmRegistration' => [
                'prefilters' => [
                    new Csrf()
                ],
                'postfilters' => []
            ]
        ];
    }

    protected function prepareParams(): bool
    {
        return parent::prepareParams();
    }

    /**
     * Registration
     *
     * @return void
     */
    public function registrationAction()
    {
        return $this->getReplyAction('post', new RegistrationParameter());
    }

    /**
     * Login
     *
     * @return void
     */
    public function loginAction()
    {
        return $this->getReplyAction('post', new LoginParameter());
    }

    /**
     * Forget Password
     *
     * @return void
     */
    public function forgetPasswordAction()
    {
        return $this->getReplyAction('post', new ForgetPasswordParameter());
    }

    /**
     * Confirm Registration
     *
     * @return void
     */
    public function confirmRegistrationAction()
    {
        return $this->getReplyAction('post', new ConfirmRegistration());
    }

    /**
     * Logout
     *
     * @return void
     */
    public function logoutAction()
    {
        global $USER;
        return $USER->Logout();
    }
}
