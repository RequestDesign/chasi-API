<?php

namespace Site\Api\Postfilters;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Response\Converter;

/**
 * ChangeKeyCase class
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
final class ChangeKeyCase extends Base
{
    public function listAllowedScopes()
    {
        return [
            Controller::SCOPE_AJAX,
            Controller::SCOPE_REST
        ];
    }
    public function onAfterAction(Event $event)
    {
        $result = $event->getParameter('result');
        if ($result) {
            $converter = new Converter(Converter::OUTPUT_JSON_FORMAT);
            $newResult = $converter->process($result);
            $event->setParameter('result', $newResult);
        }
    }
}
