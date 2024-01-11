<?php

namespace Site\Api\Parameters;

use Bitrix\Main\UserTable;

/**
 * UserParameter class
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class UserParameter extends Parameter
{
    protected array $currentParams = [
        'email' => [
            'name' => 'E-mail',
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


    protected function successAction(): array
    {
        $reply = [];
        return $this->dirtyParams;
    }
}
