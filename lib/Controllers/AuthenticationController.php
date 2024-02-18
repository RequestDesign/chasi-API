<?php
namespace Site\Api\Controllers;

require_once($_SERVER["DOCUMENT_ROOT"].'/ajax/class/LoginEmailClass.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/ajax/class/RegEmail4Class.php');

use Bitrix\Main\EventResult;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Prefilters\Csrf;
use Site\Api\Prefilters\Validator;
use RegEmail4Class;
use Site\Api\Services\Validation;

/**
 * AuthenticationController
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class AuthenticationController extends Controller
{
    public const ERROR_ILLEGAL_LOGIN_OR_PASSWORD = 'illegal_login_or_password';
    public const BAD_REQUEST = 'bad_request';

    public const ERROR_WRONG_CONFIRMATION_CODE = 'wrong_confirmation_code';

    public const INTERNAL_ERROR = 'internal_error';

    public function configureActions(): array
    {
        return [
            'login' => [
                '+prefilters' => [
                    new Validator([
                        (new \Site\Api\Services\Validation("email"))->email(),
                        (new \Site\Api\Services\Validation("password"))->required()->minLength(8)
                    ])
                ]
            ],
            'logout' => [
                '+prefilters' => [
                    new ActionFilter\Authentication()
                ],
            ],
            'confirmRegistration' => [
                '+prefilters' => [
                    new Validator([
                        (new Validation("id"))->number()->required(),
                        (new Validation("code"))->required()
                    ])
                ]
            ],
            'forgetPassword' => [
                'postfilters' => [
                    new ChangeKeyCase()
                ]
            ],
            'sendConfirmCode' => [
                'postfilters' => [
                    new Validator([
                        (new Validation('id'))->number()
                    ])
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
                http_response_code(403);
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
        $errors = [];
        $res = RegEmail4Class::RegEmail4Method($this->request['id'], $this->request['code'], $errors);
        if(!$res){
            foreach($errors as $error_key => $error_value){
                switch($error_key){
                    case 'regCode':{
                        $this->addError(new Error(
                            "Неверный код подтверждения",
                            self::ERROR_WRONG_CONFIRMATION_CODE
                        ));
                        break;
                    }
                    default:{
                        $this->addError(new Error(
                            "Внутренняя ошибка сервера",
                            self::INTERNAL_ERROR
                        ));
                        break;
                    }
                }
            }
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
        http_response_code(204);
    }

    public function sendConfirmCodeAction(){
        global $USER;
        $request = $this->getRequest()->toArray();
        $user = $USER->GetByID($request["id"])->Fetch();
        if ($user){
            $confirmationCode = randString(8);
            $USER->Update($user["ID"], array("CONFIRM_CODE" => $confirmationCode));

            // Отправка шаблона письма - Подтверждение регистрации нового пользователя [NEW_USER_CONFIRM]
            \CEvent::Send("NEW_USER_CONFIRM", "s1", array("EMAIL" => $user["EMAIL"], "ID" => $user["ID"], "CONFIRM_CODE" => $confirmationCode));
            http_response_code(204);
        }
        $this->addError(new Error(
            "Пользователь не существует",
            "user_does_not_exist"
        ));
        http_response_code(400);
        return new EventResult(EventResult::ERROR, null, 'site.api', $this);
    }

    /**
     * Logout
     *
     * @return void
     */
    public function logoutAction()
    {
        global $USER;
        $USER->Logout();
        http_response_code(204);
    }

    public function getDefaultPreFilters()
    {
        return [
            new Csrf()
        ];
    }
}
