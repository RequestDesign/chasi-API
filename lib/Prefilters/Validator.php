<?php

namespace Site\Api\Prefilters;

use Bitrix\Main\Application;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class Validator extends \Bitrix\Main\Engine\ActionFilter\Base
{
    public const ERROR_INVALID_EMAIL = 'invalid_email';
    public const ERROR_REQUIRED = 'required';
    public const ERROR_MIN = 'min';
    public const ERROR_MAX = 'max';
    public const ERROR_REGULAR = 'regular';
    public const ERROR_MIN_LENGTH = 'min_length';
    public const ERROR_MAX_LENGTH = 'max_length';
    public const ERROR_NOT_NUMBER = 'not_number';
    public const ERROR_NOT_STRING = 'not_string';

    protected array $validators;
    protected array $request;

    /**
     * Validator constructor.
     *
     * @param array $validators
     */
    public function __construct(array $validators = [])
    {
        $this->validators = $validators;
        $this->request = Application::getInstance()->getContext()->getRequest()->toArray();
        parent::__construct();
    }

    /**
     * List allowed values of scopes where the filter should work.
     * @return array
     */
    public function listAllowedScopes():array
    {
        return [
            Controller::SCOPE_AJAX,
            Controller::SCOPE_REST
        ];
    }

    public function onBeforeAction(Event $event): null|EventResult
    {
        if(!count($this->validators)) return null;
        foreach ($this->validators as $validator){
            if ($validator instanceof \Site\Api\Services\Validator){
                $field = $validator->getField();
                $params = $validator->getParams();
                if(array_key_exists('required', $params) && $params["required"] === true){
                    if(!array_key_exists($field, $this->request)){
                        $this->addError(new Error(
                           "Поле ".$field." является обязательным",
                           self::ERROR_REQUIRED
                        ));
                        http_response_code(400);
                        return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                    }
                }
                if(array_key_exists($field, $this->request)){
                    foreach ($params as $param => $value){
                        switch ($param){
                            case 'regex': {
                                if (!preg_match($value, $this->request[$field])){
                                    $this->addError(new Error(
                                        "Параметр ".$field." не соответсвует регулярному выражению ".$value,
                                        self::ERROR_REGULAR
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                break;
                            }
                            case 'min':{
                                if(!is_numeric($this->request[$field])){
                                    $this->addError(new Error(
                                        "Параметр ".$field." не является числом",
                                        self::ERROR_NOT_NUMBER
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                if($this->request[$field] < $value){
                                    $this->addError(new Error(
                                        "Параметр ".$field." не должен быть меньше ".$value,
                                        self::ERROR_MIN
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                break;
                            }
                            case 'max':{
                                if(!is_numeric($this->request[$field])){
                                    $this->addError(new Error(
                                        "Параметр ".$field." не является числом",
                                        self::ERROR_NOT_NUMBER
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                if($this->request[$field] > $value){
                                    $this->addError(new Error(
                                        "Параметр ".$field." не должен быть больше ".$value,
                                        self::ERROR_MAX
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                break;
                            }
                            case 'minLength':{
                                if(!is_string($this->request[$field])){
                                    $this->addError(new Error(
                                        "Параметр ".$field." не является строкой",
                                        self::ERROR_NOT_STRING
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                if(strlen($this->request[$field]) < $value){
                                    $this->addError(new Error(
                                        "Длина параметра ".$field." не должен быть меньше ".$value,
                                        self::ERROR_MIN_LENGTH
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                break;
                            }
                            case 'maxLength':{
                                if(!is_string($this->request[$field])){
                                    $this->addError(new Error(
                                        "Параметр ".$field." не является строкой",
                                        self::ERROR_NOT_STRING
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                if(strlen($this->request[$field]) > $value){
                                    $this->addError(new Error(
                                        "Длина параметра ".$field." не должен быть больше ".$value,
                                        self::ERROR_MAX_LENGTH
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                break;
                            }
                            case 'email':{
                                if(!check_email($this->request[$field])){
                                    $this->addError(new Error(
                                        "Параметр ".$field." должен содержать корректный почтовый адрес",
                                        self::ERROR_INVALID_EMAIL
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }
        return null;
    }
}