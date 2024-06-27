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

class PublishFilter extends Base
{
    protected array $request;
    protected const AD_HL_ID = 1;
    public const EXPIRED = 49;
    public const DRAFT = 40;
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
            "select" => [
                "ID",
                "UF_STATUS",
                "UF_BRAND",
                "UF_MODEL",
                "UF_SOST",
                "UF_POL",
                "UF_MATERIAL",
                "UF_REMEN",
                "UF_PRODAVEC",
                "UF_AVAILABILITY_OF_DOCUMENTS",
                "UF_FOTO",
                "UF_PRICE",
            ],
            "filter" => ["UF_USER_ID" => $USER->GetID()]
        ])->fetch();
        if(!$ad){
            $this->addError(new Error(
                'Данное объявление не существует, или на его публикацию у текущего пользователя нет прав',
                "publish_failed",
            ));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
        if($ad["UF_STATUS"] == self::EXPIRED){
            return (new Validator([
                (new Validation("promotionType"))->number()->required()
            ]))->onBeforeAction($event);
        } elseif ($ad["UF_STATUS"] == self::DRAFT) {
            foreach($ad as $key=>$value){
                switch ($key){
                    case "UF_BRAND":
                    case "UF_MODEL":
                    case "UF_SOST":
                    case "UF_POL":
                    case "UF_MATERIAL":
                    case "UF_REMEN":
                    case "UF_PRODAVEC":
                    case "UF_AVAILABILITY_OF_DOCUMENTS":
                    case "UF_FOTO":
                    case "UF_PRICE":
                        if(!$value){
                            $this->addError(new Error(
                                'Черновик нельзя отправить на модерацию, не заполнив обязательные при создании объявления поля',
                                "publish_failed",
                            ));
                            http_response_code(400);
                            return new EventResult(EventResult::ERROR, null, null, $this);
                        }
                        break;
                }

            }
        }
        return null;
    }
}