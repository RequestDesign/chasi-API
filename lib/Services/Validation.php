<?php

namespace Site\Api\Services;

class Validation
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
     * Validation constructor.
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
     * @return Validation
     */
    public function required():Validation
    {
        $this->validate_params["required"] = true;
        return $this;
    }

    /**
     * Задает регулярное выражение для параметра
     *
     * @param string $regex
     * @return Validation
     */
    public function regex(string $regex):Validation
    {
        $this->validate_params["regex"] = $regex;
        return $this;
    }

    /**
     * Задает минимальное значение для параметра
     *
     * @param int $min
     * @return Validation
     */
    public function min(int $min):Validation
    {
        $this->validate_params["min"] = $min;
        return $this;
    }

    /**
     * Задает максимальное значение для параметра
     *
     * @param int $max
     * @return Validation
     */
    public function max(int $max):Validation
    {
        $this->validate_params["max"] = $max;
        return $this;
    }

    /**
     * Задает минимальное количество символов для параметра
     *
     * @param int $min
     * @return Validation
     */
    public function minLength(int $min):Validation
    {
        $this->validate_params["minLength"] = $min;
        return $this;
    }

    /**
     * Задает максимальное количество символов для параметра
     *
     * @param int $max
     * @return Validation
     */

    public function maxLength(int $max):Validation
    {
        $this->validate_params["maxLength"] = $max;
        return $this;
    }

    /**
     * Задает проверку почты для параметра
     *
     * @return Validation
     */
    public function email():Validation
    {
        $this->validate_params["email"] = true;
        return $this;
    }

    public function password():Validation
    {
        $this->validate_params["password"] = true;
        return $this;
    }
    
    public function number():Validation
    {
        $this->validate_params["number"] = true;
        return $this;
    }

    //megabytes
    public function image($max_size):Validation
    {
        $this->validate_params["image"] = $max_size;
        return $this;
    }

    //работает только для изображений
    public function maxCount($count):Validation
    {
        $this->validate_params["maxCount"] = $count;
        return $this;
    }

    public function price():Validation
    {
        $this->validate_params["price"] = true;
        return $this;
    }

    public function bool():Validation
    {
        $this->validate_params["bool"] = true;
        return $this;
    }

    public function not_empty():Validation
    {
        $this->validate_params["not_empty"] = true;
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