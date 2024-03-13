<?php
namespace Site\Api\Controllers;

require_once($_SERVER["DOCUMENT_ROOT"].'/ajax/class/LoginEmailClass.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/ajax/class/RegEmail4Class.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/ajax/class/ForgotPassEmail1Class.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/ajax/class/ForgotPassEmail2Class.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/ajax/class/ForgotPassEmail3Class.php');

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
use ForgotPassEmail1Class;
use ForgotPassEmail2Class;
use ForgotPassEmail3Class;

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
            'forgot' => [
                '+prefilters' => [
                    new Validator([
                        (new Validation('email'))->required()->email()
                    ])
                ],
                'postfilters' => [
                    new ChangeKeyCase()
                ]
            ],
            'confirmForgot' => [
                '+prefilters' => [
                    new Validator([
                        (new Validation('id'))->required()->number(),
                        (new Validation('code'))->required()
                    ])
                ]
            ],
            'sendConfirmCode' => [
                'postfilters' => [
                    new Validator([
                        (new Validation('id'))->number()
                    ])
                ]
            ],
            'changePassword' => [
                '+prefilters' => [
                    new Validator([
                        (new Validation('id'))->required()->number(),
                        (new Validation('password'))->required()->password(),
                        (new Validation('confirmPassword'))->required()
                    ])
                ]
            ]
        ];
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
            $res = \LoginEmail::LoginEmailMethod($request["email"], $request["password"], $errors);
            if(array_key_exists("IllegalLogin", $errors) || $res !== true){
                $this->addError(new Error(
                    "Неверный логин или пароль",
                    self::ERROR_ILLEGAL_LOGIN_OR_PASSWORD
                ));
                http_response_code(403);
                return new EventResult(EventResult::ERROR, null, 'site.api', $this);
            }
            return ["id"=>$this->getCurrentUser()->getId()];
        }
        else if(array_key_exists("phone", $request)){
            \LoginEmail::LoginEmailMethod($request["phone"], $request["password"], $errors);
            if(array_key_exists("IllegalLogin", $errors)){
                $this->addError(new Error(
                    "Неверный логин или пароль",
                    self::ERROR_ILLEGAL_LOGIN_OR_PASSWORD
                ));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, 'site.api', $this);
            }
            return [];
        }
        $this->addError(new Error(
            "Не указаны данные",
            self::BAD_REQUEST
        ));
        http_response_code(400);
        return null;
    }

    /**
     * Forget Password
     *
     * @return void
     */
    public function forgotAction():array|EventResult
    {
        $request = $this->getRequest()->toArray();
        $errors = [];
        $id = ForgotPassEmail1Class::ForgotPassEmail1Method($request["email"], $errors);
        if(!$id){
            $this->addError(new Error(
                "Пользователь не существует",
                "user_not_found"
            ));
            http_response_code(404);
            return new EventResult(EventResult::ERROR, null, 'site.api', $this);
        }
        return ["id"=>$id];
    }

    public function confirmForgotAction():array|EventResult
    {
        $request = $this->getRequest()->toArray();
        $errors = [];
        $res = ForgotPassEmail2Class::ForgotPassEmail2Method($request["id"], $request["code"], $errors);
        if(!$res){
            foreach($errors as $error_key=>$error_message){
                switch($error_key){
                    case 'resetPasswordCode':{
                        $this->addError(new Error(
                            "Неверный код подтверждения",
                            "illegal_code"
                        ));
                        http_response_code(404);
                        return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                        break;
                    }
                    case 'existUser':{
                        $this->addError(new Error(
                            "Пользователь не существует",
                            "user_not_found"
                        ));
                        http_response_code(404);
                        return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                        break;
                    }
                }
            }
        }
        return [];
    }

    public function changePasswordAction(){
        $request = $this->getRequest()->toArray();
        $errors = [];
        $res = ForgotPassEmail3Class::ForgotPassEmail3Method($request["id"], $request["password"], $request["confirmPassword"], $errors);
        if(!$res){
            foreach ($errors as $error_key => $error_message){
                switch ($error_key){
                    case 'CONFIRM_CODE':{
                        $this->addError(new Error("Неверный код подтверждения", "incorrect_code"));
                        http_response_code(400);
                        return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                    }
                    case 'passConfirm':{
                        $this->addError(new Error("Пароли не сопадают", "incorrect_confirm_password"));
                        http_response_code(400);
                        return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                    }
                    case 'repeatNewPasswordInput':{
                        $this->addError(new Error("Некорректный пароль", "incorrect_password"));
                        http_response_code(400);
                        return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                    }
                }
            }
        }
        return [];
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
        return [];
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
            return [];
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
        return [];
    }

    public function getDefaultPreFilters()
    {
        return [
            new Csrf()
        ];
    }
}
