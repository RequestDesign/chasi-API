<?php

namespace Site\Api\Forms;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;

/**
 * Класс для работы с данными формы
 * TODO Каждая форма новый класс под общим интерфейсом
 * Запуск через BX.ajax.runComponentAction
 * ajaxAction
 *
 * ToDo Обработку полей формы нужно вынести в отдельные классы
 *
 * @author work-aidsoul@outlook.com
 * https://github.com/aidsoul/bitrix-form
 */
class Form
{
    /**
     * @var ErrorCollection
     */
    protected ErrorCollection $errorCollection;

    /**
     * @var Application
     */
    protected Application $application;

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
     * Неочищенные поля формы
     *
     * @var array
     */
    protected array $dirtyParams = [];

    /**
     * Параметры, которые принадлежат форме
     *
     *  'fio' => [
     * 'name' => 'ФИО',
     * 'required' => true
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

    /**
     * Массив доступен в шаблоне
     * $arResult['DATA']
     *
     * @var array
     */
    protected array $formData = [];

    public function __construct()
    {
        $this->errorCollection = new ErrorCollection();
        $this->application = Application::getInstance();
        $this->context = $this->application->getContext();
    }

    /**
     * Получить POST - параметры
     *
     * @return self
     */
    public function getPostParams(): self
    {
        $request = $this->context->getRequest();
        if ($params = $request->getPostList()->toArray()) {
            $this->params = $params;
        }

        return $this;
    }

    /**
     * Получить файлы
     *
     * @return self
     */
    public function getFileList(): self
    {
        $request = $this->context->getRequest();
        if ($files = $request->getFileList()->toArray()) {
            $this->params = array_merge($this->params, $files);
        }

        return $this;
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
            $this->setError('params', 'There are no fields');
        } else {
            if (!$this->currentParams) {
                $this->setError('requiredParameters', 'Required parameters are not specified');
                return $this;
            }
            $currentParamsCount = count($this->currentParams);
            $paramsCount = count($this->params);
            if ($paramsCount !== $currentParamsCount) {
                $this->setError('parameterCount', 'Min number of parameters = ' . $currentParamsCount);
                return $this;
            }
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
                if (is_array($value)) {
                    if (method_exists($this, $param)) {
                        $this->$param($value);
                    }
                    continue;
                }
                $value = strip_tags(htmlspecialcharsbx($value));
                if (!$value) {
                    $this->setError(
                        $k,
                        'The parameter is empty(' . $k . ')'
                    );
                }
                if (!in_array($param, $this->ignoreFieldArr)) {
                    if (preg_match("/[<>\/]+/ium", $value)) {
                        $this->setError(
                            $param,
                            'В поле "' . $param . '" недопустимые символы!'
                        );
                    }
                }
                if (method_exists($this, $param) && $value) {
                    $this->$param($value);
                }
                $this->dirtyParams[$param] = $value;
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
     * Действие с данными формы
     *
     *
     * @return array
     */
    public function formDataAction(): array
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
            $this->sendMail();
        }
    }

    /**
     * Установить данные для формы
     *
     * $this->formData
     *
     * @param array $formData
     * @return void
     */
    public function setFormData(array $formData): void
    {
        $this->formData = $formData;
    }

    /**
     * Получить данные формы
     *
     * @return array
     */
    public function getFormData(): array
    {
        return $this->formData;
    }

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
        global $USER;
        if (!empty($this->mail)) {
            \CEvent::Send(
                $this->mail['eventType'],
                SITE_ID,
                $this->cleanParams,
                'N',
                $this->mail['mailTemplateId'],
                $this->mailAttachments
            );
        }

        // if (!empty($this->mail['email'])) {
        //     global $USER;
        //     if($USER->IsAdmin()){

        //     }
        //     $headers = implode("\r\n", [
        //         'Content-Type: text/html; charset=utf-8',
        //         'X-Mailer: PHP/' . PHP_VERSION
        //     ]);
            // mail('bitrix.site.test@gmail.com', $subject, $message, $headers);
            // mail();
        // }
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
