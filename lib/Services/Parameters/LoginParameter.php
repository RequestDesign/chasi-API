<?php

namespace Site\Api\Services\Parameters;

use Bitrix\Main\UserTable;

/**
 * LoginParameter class
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class LoginParameter extends Parameter
{
    protected array $currentParams = [
        'email' => [
            'name' => 'E-mail',
            'required' => true
        ],
        'password' => [
            'name' => 'Пароль',
            'required' => true
        ]
    ];

    /**
     * Почта
     *
     * @param string $email
     * @return void
     */
    protected function email(string $email): void
    {
        $user = UserTable::query()
        ->setSelect(['ID'])
        ->addFilter('=EMAIL', $email)
        ->fetch();
        if (!$user) {
            $this->setError('email', 'Пользователь с таким email не существует');
        }
        $this->cleanParams['EMAIL'] = $email;
    }

    protected function password(string $password): void
    {
        // if (Validator::charset('UTF-8')->regex('/[!@#$%^&*()_.,0-9]/ium')->validate($password)) {
        //     $this->setError('fio', 'Неверный формат поля "Пароль"');
        // }
        $this->cleanParams['PASSWORD'] = $password;
    }


    protected function successAction(): array
    {
        $reply = [];

        global $USER;
        $user = new \CUser();
        $user = $user->Login($this->cleanParams['EMAIL'], $this->cleanParams['PASSWORD']);
        if (is_array($user)) {
            $this->setError('user', 'Ошибка! Неверное имя пользователя или пароль. Проверьте правильность введенных данных.');
        } else {
            $reply = [$user];
        }

        return $reply
        ;
    }
}
