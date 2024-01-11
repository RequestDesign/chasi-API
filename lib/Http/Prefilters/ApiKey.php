<?php

namespace Site\Api\Http\Prefilters;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Error;

/**
 * ApiKey class
 * 
 * @author AidSoul <work-aidsoul@outlook.com>
 */
final class ApiKey extends Base
{
    public function onBeforeAction(Event $event)
    {
        $apiKey = Context::getCurrent()->getRequest()->getHeader('X-API-Key');

        if (!$apiKey) {
            $this->addError(new Error('Wrong api key'));
            return new EventResult(EventResult::ERROR, handler:$this);
        }
    }
}
