<?php
namespace Site\Api\Controllers;

require_once($_SERVER["DOCUMENT_ROOT"].'/ajax/class/LoginEmailClass.php');

use Bitrix\Main\Application;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Prefilters\Csrf;
use Site\Api\Prefilters\Validator;

/**
 * AuthenticationController
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class AuthenticationController extends Controller
{
    public const ERROR_ILLEGAL_LOGIN_OR_PASSWORD = 'illegal_login_or_password';
    public const BAD_REQUEST = 'bad_request';

    public function configureActions(): array
    {
        return [
            'login' => [
                '+prefilters' => [
                    new Validator([
                        (new \Site\Api\Services\Validator("email"))->email(),
                        (new \Site\Api\Services\Validator("password"))->required()->minLength(8)
                    ])
                ]
            ],
            'logout' => [
                '+prefilters' => [
                    new ActionFilter\Authentication()
                ],
            ],
            'forgetPassword' => [
                'postfilters' => [
                    new ChangeKeyCase()
                ]
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
        //return $this->getReplyAction('post', new RegistrationParameter());
    }

    /**
     * Login
     *
     * @return void
     */
    public function loginAction()
    {
        $request = Application::getInstance()->getContext()->getRequest()->toArray();
        $errors = [];
        if(array_key_exists("email", $request)){
            \LoginEmail::LoginEmailMethod($request["email"], $request["password"], $errors);
            if(array_key_exists("IllegalLogin", $errors)){
                $this->addError(new Error(
                    "Неверный логин или пароль",
                    self::ERROR_ILLEGAL_LOGIN_OR_PASSWORD
                ));
                http_response_code(400);
            }
            else{
                http_response_code(204);
            }
            return null;
        }
        else if(array_key_exists("phone", $request)){
            \LoginEmail::LoginEmailMethod($request["phone"], $request["password"], $errors);
            if(array_key_exists("IllegalLogin", $errors)){
                $this->addError(new Error(
                    "Неверный логин или пароль",
                    self::ERROR_ILLEGAL_LOGIN_OR_PASSWORD
                ));
                http_response_code(400);
            }
            else{
                http_response_code(204);
            }
            return null;
        }
        $this->addError(new Error(
            "Не указаны данные",
            self::BAD_REQUEST
        ));
        http_response_code(400);
        return null;
        //return $this->getReplyAction('post', new LoginParameter());
    }

    /**
     * Forget Password
     *
     * @return void
     */
    public function forgetPasswordAction()
    {
        //return $this->getReplyAction('post', new ForgetPasswordParameter());
    }

    /**
     * Confirm Registration
     *
     * @return void
     */
    public function confirmRegistrationAction()
    {
        //return $this->getReplyAction('post', new ConfirmRegistration());
    }

    /**
     * Logout
     *
     * @return void
     */
    public function logoutAction()
    {
        //global $USER;
        //return $USER->Logout();
    }

    public function getDefaultPreFilters()
    {
        return [
            new Csrf()
        ];
    }
}
