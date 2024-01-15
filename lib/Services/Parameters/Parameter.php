<?php

namespace Site\Api\Services\Parameters;

use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;

/**
 * Parameter
 *
 * @author work-aidsoul@outlook.com
 * @license MIT
 *
 * It is taken as a basis https://github.com/aidsoul/bitrix-form
 */
abstract class Parameter
{
    /**
     * @var ErrorCollection
     */
    protected ErrorCollection $errorCollection;

    /**
     * @var Context
     */
    protected Context $context;

    /**
     * Параметры получаемые из GET/POST/FILES
     *
     * @var array
     */
    protected array $params = [];

    /**
     * Игнорировать проверку следующих параметров
     *
     * @var array
     */
    protected array $ignoreFieldArr = [];

    /**
     * Массив полей после очистки
     * @var array
     */
    protected array $cleanParams = [];

    /**
     * Неочищенные параметры
     *
     * @var array
     */
    protected array $dirtyParams = [];

    /**
     * Параметры, которые принадлежат форме
     *
     * 'fio'        => [
     * 'name'       => 'ФИО',
     * 'required'   => true
     * 'min'        => 1,
     * 'max'        => 3,
     * 'regular'    => [
     *      'emailTest' =>  [
     *       'rule'     => '/email@mail/s',
     *        'message' => 'Не email@mail.ru'
     *            ]
     *       ]
     * ],
     * @var array
     */
    protected array $currentParams = [];

    /**
     * Если нужна работа с почтой
     *
     *  'eventType' => 'Тип почтового события',
     *  'mailTemplateId' => 'ID почтового шаблона'
     *
     * @var array
     */
    protected array $mail = [];
    /**
     * Почтовые вложения
     *
     * @var array
     */
    protected array $mailAttachments = [];

    /**
     * Ajax только для авторизированных пользователей
     *
     * @var boolean
     */
    protected bool $ajaxUserAuthorization = false;

    /**
     * success data array
     *
     * @var array
     */
    private array $replyData = [];

    public function __construct()
    {
        $this->errorCollection = new ErrorCollection();
    }

    /**
     * Получить все параметры запроса
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Установка ошибки
     *
     * @param string $message
     * @param string $name
     * @param array $data
     * @return void
     */
    protected function setError(
        string $code,
        string|array $message,
        array $data = []
    ): void {
        if (!$this->errorCollection->getErrorByCode($code)) {
            $this->errorCollection->setError(new Error($message, $code, $data));
        }
    }

    /**
     * Установить параметры
     *
     * @param array $params
     * @return self
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Min/Max string validation function
     *
     * @param string|integer $str
     * @param string $param
     * @param integer|null $min
     * @param integer|null $max
     * @return void
     */
    private function strLengthValidation(string|int $str, string $param, int|null $min, int|null $max): void
    {
        $symbolsValue = mb_strlen($str, "UTF-8");
        if ($min && $symbolsValue < $min) {
            $this->setError($param . 'MinCharacters', 'The minimum number of characters for the field "' . $param . '" is' . $min);
        }
        if ($max && $symbolsValue > $max) {
            $this->setError($param . 'MaxCharacters', 'The maximum number of characters for the field "' . $param . '" is' . $max);
        }
    }

    /**
     * Regular Validation
     *
     * @param array $regularArr
     * @param string $value
     * @param string $param
     * @return void
     */
    private function regularValidation(array $regularArr, string $value, string $param): void
    {
        foreach ($regularArr as $k => $regular) {
            if (!preg_match($regular['rule'], $value)) {
                $message = $regular['message'];
                if (!$message) {
                    $message = 'The field "' . $param . '" does not match the template';
                }
                $this->setError($k, $message);
            }
        }
    }

    /**
     * validateBaseParams function
     *
     * @param string $param
     * @param string $value
     * @return void
     */
    private function validateBaseParams(string $param, string $value): void
    {
        $value = strip_tags(htmlspecialcharsbx($value));
        $currentParams = &$this->currentParams[$param];
        if (!in_array($param, $this->ignoreFieldArr)) {
            if (preg_match("/[<>\/]+/ium", $value)) {
                $this->setError(
                    $param,
                    'В поле "' . $param . '" недопустимые символы!'
                );
            } else {
                $this->strLengthValidation($value, $param, $currentParams['min'], $currentParams['max']);
                $this->regularValidation($currentParams['regular'] ?? [], $value, $param);
                if ($this->errorCollection->isEmpty()) {
                    $method = $param;
                    if ($currentParams['validateMethod']) {
                        $method = $currentParams['validateMethod'];
                    }
                    if (method_exists($this, $method)) {
                        $this->$method($value);
                    }
                }
            }
        }
    }

    /**
     * validateFileParams function
     *
     * @param string $param
     * @param array $value
     * @return void
     */
    private function validateFileParams(string $param, array $value): void
    {
        if (method_exists($this, $param)) {
            $this->$param($value);
        }
    }

    /**
     * Функция валидации полей
     *
     * TODO Разделить на методы
     *
     * @param array $params
     * @return self
     */
    public function validation(): self
    {
        if (!$this->params) {
            $this->setError('noParams', 'There are no fields');
        } else {
            $currentParamsErrors = false;
            foreach ($this->currentParams as $k => $v) {
                if ($v['required'] === true) {
                    if (empty($this->params[$k])) {
                        $this->setError(
                            $k,
                            'The parameter is required(' . $k . ')'
                        );
                        $currentParamsErrors = true;
                    }
                }
            }
            if ($currentParamsErrors === true) {
                return $this;
            }
            foreach ($this->params as $param => $value) {
                if (!$param || !$value) {
                    continue;
                }
                $this->dirtyParams[$param] = $value;
                if (is_array($value)) {
                    $this->validateFileParams($param, $value);
                } else {
                    $this->validateBaseParams($param, $value);
                }
            }
        }
        return $this;
    }

    /**
     * Подготовительное действие
     *
     * @return void
     */
    protected function prepareAction(): void
    {
    }

    /**
     * Если валидация пройдена
     *
     * @return array
     */
    protected function successAction(): array
    {
        return [];
    }

    /**
     * Выполнить главное действие
     *
     * @return void
     */
    public function action(): void
    {
        if ($this->errorCollection->isEmpty()) {
            $this->replyData = $this->successAction();
            if ($this->errorCollection->isEmpty()) {
                if (isset($this->mail['eventType'])) {
                    $this->sendMail();
                }
            }
        }
    }

    /**
     * Получить коллекцию ошибок
     *
     * @return ErrorCollection
     */
    public function getErrorCollection(): ErrorCollection
    {
        return $this->errorCollection;
    }

    /**
     * Отправить сообщение на почту
     *
     * @return void
     */
    protected function sendMail(): void
    {
        \CEvent::Send(
            $this->mail['eventType'],
            SITE_ID,
            $this->cleanParams,
            'N',
            $this->mail['mailTemplateId'],
            $this->mailAttachments
        );
    }

    /**
     * Получить успешный ответ
     *
     * @return array
     */
    public function getReplyData(): array
    {
        return $this->replyData;
    }
}
