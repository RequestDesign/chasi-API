<?php

namespace Site\Api\Postfilters;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Event;

class FilterReorder extends Base
{
    public function listAllowedScopes()
    {
        return [
            Controller::SCOPE_AJAX,
            Controller::SCOPE_REST
        ];
    }

    public function onAfterAction(Event $event){
        $result = $event->getParameter('result');
        if ($result) {
            $new_result = [];
            foreach ($result as $index=>$filter_item){
                foreach($filter_item as $key => $value){
                    if(!isset($new_result[$key])) $new_result[$key] = [];
                    $new_result[$key][] = $value;
                }
            }
            $event->setParameter('result', $new_result);
        }
    }
}