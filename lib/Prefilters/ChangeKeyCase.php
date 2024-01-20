<?php

namespace Site\Api\Prefilters;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Error;

/**
 * ChangeKeyCase class
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
final class ChangeKeyCase extends Base
{
    public function onAfterAction(Event $event)
    {
        $result = $event->getParameter('result');
        if ($result) {
            $newResult = array_change_key_case($result);
            $event->setParameter('result', $newResult);
        }
    }
}
