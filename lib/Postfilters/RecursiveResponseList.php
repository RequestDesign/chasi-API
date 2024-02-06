<?php

namespace Site\Api\Postfilters;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Event;

class RecursiveResponseList extends Base
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
            foreach ($result as &$value){
                foreach($value as $key=>&$el){
                    $key_recursive = explode("|", $key);
                    if(count($key_recursive)>1){
                        $last_level = &$value;
                        foreach($key_recursive as $key_level){
                            if(!array_key_exists($key_level, $last_level))
                                $last_level[$key_level] = [];
                            $last_level = &$last_level[$key_level];
                        }
                        $last_level = $el;
                        unset($value[$key]);
                    }
                }
            }
            $event->setParameter('result', $result);
        }
    }
}