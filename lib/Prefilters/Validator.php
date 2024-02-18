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

    public const ERROR_PASSWORD = 'invalid_password';
    public const ERROR_FILE_UPLOAD = 'invalid_upload_files';
    public const ERROR_FILE_EXT = 'invalid_files_ext';
    public const ERROR_FILES_CNT = 'invalid_files_count';
    public const ERROR_NOT_PRICE = 'not_price';
    public const ERROR_NOT_BOOL = 'not_bool';
    public const ERROR_FILE_SIZE = 'invalid_file_size';

    protected array $validators;
    protected array $request;
    private array $files;

    /**
     * Validation constructor.
     *
     * @param array $validators
     */
    public function __construct(array $validators = [])
    {
        $this->validators = $validators;
        $this->request = Application::getInstance()->getContext()->getRequest()->toArray();
        $this->files = Application::getInstance()->getContext()->getRequest()->getFileList()->toArray();
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
            if ($validator instanceof \Site\Api\Services\Validation){
                $field = $validator->getField();
                $params = $validator->getParams();
                if(array_key_exists('required', $params) && $params["required"] === true){
                    if(!array_key_exists($field, $this->request) && !array_key_exists($field, $this->files)){
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
                                        self::ERROR_REGULAR,
                                        ["field"=>$field]
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
                                        self::ERROR_NOT_NUMBER,
                                        ["field"=>$field]
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                if($this->request[$field] < $value){
                                    $this->addError(new Error(
                                        "Параметр ".$field." не должен быть меньше ".$value,
                                        self::ERROR_MIN,
                                        ["field"=>$field]
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
                                        self::ERROR_NOT_NUMBER,
                                        ["field"=>$field]
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                if($this->request[$field] > $value){
                                    $this->addError(new Error(
                                        "Параметр ".$field." не должен быть больше ".$value,
                                        self::ERROR_MAX,
                                        ["field"=>$field]
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
                                        self::ERROR_NOT_STRING,
                                        ["field"=>$field]
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                if(strlen($this->request[$field]) < $value){
                                    $this->addError(new Error(
                                        "Длина параметра ".$field." не должен быть меньше ".$value,
                                        self::ERROR_MIN_LENGTH,
                                        ["field"=>$field]
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
                                        self::ERROR_NOT_STRING,
                                        ["field"=>$field]
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                if(strlen($this->request[$field]) > $value){
                                    $this->addError(new Error(
                                        "Длина параметра ".$field." не должен быть больше ".$value,
                                        self::ERROR_MAX_LENGTH,
                                        ["field"=>$field]
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
                                        self::ERROR_INVALID_EMAIL,
                                        ["field"=>$field]
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                break;
                            }
                            case 'password':{
                                if(strlen($this->request[$field]) < 8){
                                    $this->addError(new Error(
                                        "Пароль должен содержать минимум 8 символов",
                                        self::ERROR_PASSWORD,
                                        ["field"=>$field]
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                else if(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $this->request[$field])){
                                    $this->addError(new Error(
                                        "Пароль должен содержать как минимум 1 латинскую букву в нижнем регистре, верхнем регистре и 1 цифру",
                                        self::ERROR_PASSWORD,
                                        ["field"=>$field]
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                break;
                            }
                            case 'number':{
                                if(!is_numeric($this->request[$field])){
                                    $this->addError(new Error(
                                        "Поле должно содержать только цифры",
                                        self::ERROR_NOT_NUMBER,
                                        ["field"=>$field]
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                break;
                            }
                            case 'price':{
                                if(!preg_match('/^\d+(\.\d{1,2})?$/', $this->request[$field])){
                                    $this->addError(new Error(
                                        "Некорректный ценовой формат",
                                        self::ERROR_NOT_PRICE,
                                        ["field"=>$field]
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                                break;
                            }
                            case 'bool':{
                                if($this->request[$field] !== '0' && $this->request[$field] !== '1'){
                                    $this->addError(new Error(
                                        "Значение поля должно быть логического типа('0' или '1')",
                                        self::ERROR_NOT_BOOL,
                                        ["field"=>$field]
                                    ));
                                    http_response_code(400);
                                    return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                }
                            }
                        }
                    }
                }
                if(array_key_exists($field, $this->files)){
                    foreach ($params as $param => $value){
                        switch ($param){
                            case 'image':{
                                $files = $this->reArrayFiles($this->files[$field]);
                                foreach($files as $file){
                                    if($file["error"] !== 0){
                                        $this->addError(new Error(
                                            "Ошибка загрузки файлов",
                                            self::ERROR_FILE_UPLOAD,
                                            ["field"=>$field]
                                        ));
                                        http_response_code(400);
                                        return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                    }
                                }
                                $allow_mimes = ["image/jpg", "image/jpeg", "image/png"];
                                foreach ($files as $file){
                                    $imageinfo = getimagesize($file["tmp_name"]);
                                    if(!$imageinfo || !in_array($imageinfo["mime"], $allow_mimes)){
                                        $this->addError(new Error(
                                            "Разрешены только форматы jpeg и png",
                                            self::ERROR_FILE_EXT,
                                            ["field"=>$field]
                                        ));
                                        http_response_code(400);
                                        return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                    }
                                    $filesize = filesize($file["tmp_name"]);
                                    if($filesize > $value*1024*1024){
                                        $this->addError(new Error(
                                            "Разрешено загружать изображения размером не более ".$value."Мб",
                                            self::ERROR_FILE_SIZE,
                                            ["field"=>$field]
                                        ));
                                        http_response_code(400);
                                        return new EventResult(EventResult::ERROR, null, 'site.api', $this);
                                    }
                                }
                                break;
                            }
                            case 'maxCount':{
                                $files = $this->reArrayFiles($this->files[$field]);
                                if(count($files) > $value) {
                                    $this->addError(new Error(
                                        "Разрешено загружать не более ".$value." изображений",
                                        self::ERROR_FILES_CNT,
                                        ["field"=>$field]
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

    protected function reArrayFiles(&$file_post){
        $isMulti = is_array($file_post['name']);
        $file_count = $isMulti?count($file_post['name']):1;
        $file_keys = array_keys($file_post);

        $file_ary = [];    //Итоговый массив
        for($i=0; $i<$file_count; $i++)
            foreach($file_keys as $key)
                if($isMulti)
                    $file_ary[$i][$key] = $file_post[$key][$i];
                else
                    $file_ary[$i][$key]    = $file_post[$key];

        return $file_ary;
    }
}