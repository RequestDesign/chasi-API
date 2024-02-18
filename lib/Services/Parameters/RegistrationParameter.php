<?php

namespace Site\Api\Services\Parameters;

use Bitrix\Main\UserTable;
use Bitrix\Main\Security\Random;

/**
 * RegistrationParameter class
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class RegistrationParameter extends Parameter
{
    protected array $email = [
        'eventType' => 'NEW_USER_CONFIRM',
        'mailTemplateId' => ''
    ];

    protected array $currentParams = [
        'fio' => [
            'name' => 'ФИО',
            'required' => true
        ],
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
        if ($user) {
            $this->setError('email', 'Пользователь с таким email уже существует');
        }
        $this->cleanParams['EMAIL'] = $email;
    }

    /**
     * ФИО
     *
     * @param string $name
     * @return void
     */
    protected function fio(string $name): void
    {
        $nameExplode = explode(' ', $name, 4);
        if (!$nameExplode[0]) {
            $this->setError('fio', 'Поле "ФИО": Укажите фамилию');
        }
        if (!$nameExplode[1]) {
            $this->setError('fio', 'Поле "ФИО": Укажите имя');
        }

        if (!$nameExplode[2]) {
            $this->setError('fio', 'Поле "ФИО": Укажите отчество');
        }

        $this->cleanParams['NAME'] = $nameExplode[1];
        $this->cleanParams['LAST_NAME'] = $nameExplode[0];
        $this->cleanParams['SECOND_NAME'] = $nameExplode[2];
    }


    protected function password(string $password): void
    {
        // if (Validation::charset('UTF-8')->regex('/[!@#$%^&*()_.,0-9]/ium')->validate($password)) {
        //     $this->setError('fio', 'Неверный формат поля "Пароль"');
        // }
        $this->cleanParams['PASSWORD'] = $password;
    }


    protected function successAction(): array
    {
        $reply = [];
        global $USER;
        $user = new \CUser();
        $arFields = [
        'EMAIL'             => $this->cleanParams['EMAIL'],
        'LOGIN'             => $this->cleanParams['EMAIL'],
        'ACTIVE'            => 'Y',
        'GROUP_ID'          => [3,4],
        'LID'               => SITE_ID,
        'PASSWORD'          => $this->cleanParams['PASSWORD'],
        'CONFIRM_PASSWORD'  => $this->cleanParams['PASSWORD'],
        'NAME' => $this->cleanParams['NAME'],
        'LAST_NAME' => $this->cleanParams['LAST_NAME'],
        'SECOND_NAME' => $this->cleanParams['SECOND_NAME'],
        'CONFIRM_CODE' => Random::getString(13)
        ];

        $ID = $user->Add($arFields);
        if ($user->LAST_ERROR) {
            $this->setError('user', $user->LAST_ERROR);
            return $reply;
        }
        $this->cleanParams['CONFIRM_CODE'] = $arFields['CONFIRM_CODE'];
        $this->cleanParams['USER_ID'] = $ID;
        $this->cleanParams['LOGIN'] = $arFields['LOGIN'];
        $this->cleanParams['NAME'] = $arFields['NAME'];
        $this->cleanParams['SECOND_NAME'] = $arFields['SECOND_NAME'];
        $this->cleanParams['CONFIRM_CODE'] = $arFields['CONFIRM_CODE'];

        // Авторизация
        if ($ID) {
            if ($USER->Authorize($ID)) {
                $reply = ['status' => 'registered'];
            }
        } else {
            $this->setError('user', $user->LAST_ERROR);
        }

        return $reply;
    }
}
