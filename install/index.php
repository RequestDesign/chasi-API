<?php

class site_api extends CModule
{
    var $MODULE_ID = 'site.api';
    var $MODULE_NAME = 'External Api';
    var $MODULE_DESCRIPTION = "";
    var $MODULE_VERSION = "1.0";
    var $MODULE_VERSION_DATE = "2023-06-13 12:00:00";
    var $PARTNER_NAME = 'Maxim';

    public function DoInstall()
    {
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'onPageStart',
            $this->MODULE_ID,
            '\Almat\Su\EventHandler',
            'disableBitrixAuth'
        );
    }

    public function DoUninstall()
    {
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'onPageStart',
            $this->MODULE_ID,
            '\Almat\Su\EventHandler',
            'disableBitrixAuth'
        );
    }
}
