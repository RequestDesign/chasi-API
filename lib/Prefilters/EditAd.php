<?php

namespace Site\Api\Prefilters;

use Bitrix\Main\Application;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Context;
use Bitrix\Main\EventResult;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Loader;

Loader::includeModule('highloadblock');

use Bitrix\Highloadblock as HL;
use Site\Api\Services\AdService;
use Site\Api\Services\Validation;

class EditAd extends Base
{
    protected array $request;
    protected const AD_HL_ID = 1;
    private const DRAFT_STATUS_ID = 40;
    public function __construct(bool $enabled = true, string $tokenName = 'sessid', bool $returnNew = false)
    {
        $this->request = Application::getInstance()->getContext()->getRequest()->toArray();
        parent::__construct();
    }

    /**
     * List allowed values of scopes where the filter should work.
     * @return array
     */
    public function listAllowedScopes()
    {
        return [
            Controller::SCOPE_AJAX,
            Controller::SCOPE_REST
        ];
    }

    public function onBeforeAction(Event $event)
    {
        global $USER;
        if(!$USER->IsAuthorized()){
            $auth = new Authentication();
            $auth->bindAction($this->getAction());
            return $auth->onBeforeAction($event);
        }
        $hlblock = HL\HighloadBlockTable::getById(self::AD_HL_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        $ad = $entity_data_class::getByPrimary($this->request["id"], [
            "select" => ["ID", "UF_STATUS"],
            "filter" => ["UF_USER_ID" => $USER->GetID()]
        ])->fetch();
        if(!$ad){
            $this->addError(new Error(
                'Данное объявление не существует, или на его редактирование у текущего пользователя нет прав',
                "edit_failed",
            ));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
        if($ad["UF_STATUS"] == self::DRAFT_STATUS_ID){
            return (new Validator([
                (new Validation("brand"))->number()->not_empty(),
                (new Validation("model"))->maxLength(255)->not_empty(),
                (new Validation("condition"))->number(),
                (new Validation("gender"))->number(),
                (new Validation("material"))->number(),
                (new Validation("watchband"))->number(),
                (new Validation("sellerType"))->number(),
                (new Validation("documentsList"))->number(),
                (new Validation("documentsDescription"))->maxLength(255),
                (new Validation("year"))->number(),
                (new Validation("mechanism"))->number(),
                (new Validation("frameColor"))->number(),
                (new Validation("country"))->number(),
                (new Validation("form"))->number(),
                (new Validation("size"))->number(),
                (new Validation("clasp"))->number(),
                (new Validation("dial"))->number(),
                (new Validation("dialColor"))->number(),
                (new Validation("waterProtection"))->number(),
                (new Validation("description"))->maxLength(200),
                (new Validation("photo"))->image(3)->maxCount(10),
                (new Validation("price"))->price(),
                (new Validation("promotion"))->bool(),
                (new Validation("promotionType"))->number()
            ]))->onBeforeAction($event);
        }
        else{
            return (new Validator([
                (new Validation("brand"))->number()->not_empty(),
                (new Validation("model"))->maxLength(255)->not_empty(),
                (new Validation("condition"))->number()->not_empty(),
                (new Validation("gender"))->number()->not_empty(),
                (new Validation("material"))->number()->not_empty(),
                (new Validation("watchband"))->number()->not_empty(),
                (new Validation("sellerType"))->number()->not_empty(),
                (new Validation("documentsList"))->number()->not_empty(),
                (new Validation("documentsDescription"))->maxLength(255),
                (new Validation("year"))->number(),
                (new Validation("mechanism"))->number(),
                (new Validation("frameColor"))->number(),
                (new Validation("country"))->number(),
                (new Validation("form"))->number(),
                (new Validation("size"))->number(),
                (new Validation("clasp"))->number(),
                (new Validation("dial"))->number(),
                (new Validation("dialColor"))->number(),
                (new Validation("waterProtection"))->number(),
                (new Validation("description"))->maxLength(200),
                (new Validation("photo"))->image(3)->maxCount(10),
                (new Validation("price"))->price()->not_empty(),
                (new Validation("promotion"))->bool(),
                (new Validation("promotionType"))->number()
            ]))->onBeforeAction($event);
        }
        return null;
    }
}
