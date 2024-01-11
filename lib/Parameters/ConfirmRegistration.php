<?php

namespace Site\Api\Parameters;

use Bitrix\Main\UserTable;

/**
 * ConfirmRegistration class
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class ConfirmRegistration extends Parameter
{
    protected array $currentParams = [
        'email' => [
            'name' => 'E-mail',
            'required' => true
        ],
        'confirmCode' => [
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


    protected function confirmCode(string $confirmCode): void
    {
        // if (Validator::charset('UTF-8')->regex('/[!@#$%^&*()_.,0-9]/ium')->validate($password)) {
        //     $this->setError('fio', 'Неверный формат поля "Пароль"');
        // }
        $this->cleanParams['CONFIRM_CODE'] = $confirmCode;
    }

    protected function successAction(): array
    {
        $reply = [];
        global $USER;
        $userEmail = $this->cleanParams['EMAIL'];
        $user = UserTable::query()->addFilter('=EMAIL', $userEmail)->fetch();
        if ($user['CONFIRM_CODE'] === $this->cleanParams['CONFIRM_CODE']) {
            $updateResult = $USER->Update($user['ID'], ['ACTIVE' => 'Y']);
            if ($updateResult) {
                $reply[] = 'Пользователь успешно активирован';
            }
        } else {
            $this->setError('confirmCode', 'Неверный CONFIRM_CODE');
        }
        return $reply;
    }
}
