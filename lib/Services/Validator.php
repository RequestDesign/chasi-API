<?php

namespace Site\Api\Services;

class Validator
{
    /**
     * @var string
     */
    protected string $field;

    /**
     * @var array
     */
    protected array $validate_params = array();

    /**
     * Validator constructor.
     *
     * @param string $field
     */
    public function __construct(string $field)
    {
        $this->field = $field;
    }

    /**
     * Делает параметр обязательным
     *
     * @return Validator
     */
    public function required():Validator
    {
        $this->validate_params["required"] = true;
        return $this;
    }

    /**
     * Задает регулярное выражение для параметра
     *
     * @param string $regex
     * @return Validator
     */
    public function regex(string $regex):Validator
    {
        $this->validate_params["regex"] = $regex;
        return $this;
    }

    /**
     * Задает минимальное значение для параметра
     *
     * @param int $min
     * @return Validator
     */
    public function min(int $min):Validator
    {
        $this->validate_params["min"] = $min;
        return $this;
    }

    /**
     * Задает максимальное значение для параметра
     *
     * @param int $max
     * @return Validator
     */
    public function max(int $max):Validator
    {
        $this->validate_params["max"] = $max;
        return $this;
    }

    /**
     * Задает минимальное количество символов для параметра
     *
     * @param int $min
     * @return Validator
     */
    public function minLength(int $min):Validator
    {
        $this->validate_params["minLength"] = $min;
        return $this;
    }

    /**
     * Задает максимальное количество символов для параметра
     *
     * @param int $max
     * @return Validator
     */

    public function maxLength(int $max):Validator
    {
        $this->validate_params["maxLength"] = $max;
        return $this;
    }

    /**
     * Задает проверку почты для параметра
     *
     * @return Validator
     */
    public function email():Validator
    {
        $this->validate_params["email"] = true;
        return $this;
    }

    /**
     * Возвращает параметр
     *
     * @return string
     */
    public function getField():string
    {
        return $this->field;
    }

    /**
     * Возвращает проверки параметра
     *
     * @return array
     */
    public function getParams():array
    {
        return $this->validate_params;
    }
}