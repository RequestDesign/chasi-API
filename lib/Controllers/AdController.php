<?php

namespace Site\Api\Controllers;

require_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/ListFavouritesClass.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/FavouritesClass.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/SearchDataClass.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/CountCallsClass.php");

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use \Bitrix\Main\Engine\Controller;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\Response;
use Bitrix\Main\Web\Json;
use Site\Api\Exceptions\AdNotFoundAuthException;
use Site\Api\Exceptions\CreateException;
use Site\Api\Exceptions\EditException;
use Site\Api\Exceptions\FilterException;
use Site\Api\Exceptions\PhoneMissingException;
use Site\Api\Exceptions\PublishException;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\FilterReorder;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\Csrf;
use Site\Api\Prefilters\EditAd;
use Site\Api\Prefilters\Validator;
use Site\Api\Services\Validation;
use ListFavouritesClass;
use FavouritesClass;
use SearchDataClass;
use CountCallsClass;

class AdController extends Controller
{

    private array $navData = [];
    private const DRAFT_STATUS_ID = 40;
    private const REJECTED_STATUS_ID = 41;
    private const UNPAYED_STATUS_ID = 43;
    private const POSTED_STATUS_ID = 44;
    private const MOVING_STATUS_ID = 45;
    private const MODERATED_STATUS_ID = 46;
    private const EXPIRED_STATUS_ID = 49;
    private const CLOSED_STATUS_ID = 50;

    /**
     * @return array
     */
    public function getListAction($params = []): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        try{
            $els = $adService->getList($params);
            $this->navData = $adService->getNavigationData();
            return $els;
        }
        catch (FilterException $e){
            $this->addError(new Error($e->getMessage(), FilterException::FILTER_EXCEPTION_CODE));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
    }

    public function getFilterAction(): array
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        return $adService->getFilter();
    }

    public function createAction($addParams = []): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        try{
            $res = $adService->create($addParams);
            if(!$res->isSuccess()){
                foreach($res->getErrors() as $error){
                    $this->addError($error);
                }
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            $url = $adService->getPayUrl($res->getId());
            return ["id" => $res->getId(), "url" => $url ? $url : null];
        }
        catch (\Exception $e){
            if($e instanceof AdNotFoundAuthException) {
                $this->addError(new Error($e->getMessage(), AdNotFoundAuthException::AD_NOT_FOUND));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            if($e instanceof CreateException){
                $this->addError(new Error($e->getMessage(), CreateException::INVALID_CREATE_DATA, ["field"=>$e->getField()]));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            if($e instanceof PhoneMissingException){
                $this->addError(new Error($e->getMessage(), PhoneMissingException::MISSING_PHONE));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
        }
    }

    public function createDraftAction(): array|EventResult
    {
        $params = ["UF_STATUS" => self::DRAFT_STATUS_ID];
        return $this->createAction($params);
    }

    public function getCreateValuesAction(): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        return $adService->getCreateValues();
    }

    public function getOneAction(): array|EventResult
    {
        $request = $this->getRequest()->toArray();
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        $el = $adService->getOne();
        if($el){
            return $el;
        }
        else{
            $this->addError(new Error(
                "Запрашиваемый ресурс не существует",
                "ad_not_exist"
            ));
            http_response_code(404);
            return new EventResult(EventResult::ERROR, null, "site.api", $this);
        }
    }

    public function editAction(): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        try{
            try{
                $res = $adService->edit();
                if(!$res->isSuccess()){
                    foreach($res->getErrors() as $error){
                        $this->addError($error);
                    }
                    return new EventResult(EventResult::ERROR, null, null, $this);
                }
                return ["id" => $res->getId()];
            }
            catch (CreateException $e){
                $this->addError(new Error($e->getMessage(), EditException::INVALID_EDIT_DATA, ["field"=>$e->getField()]));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
        }
        catch(EditException $e){
            $this->addError(new Error($e->getMessage(), EditException::ELEMENT_DOESNT_EXIST));
            http_response_code(404);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
    }

    public function getWaitingAction(){
        $params = [
            "filter" => [
                ["UF_USER_ID", "=", $this->getCurrentUser()->getId()],
                ["UF_STATUS", "in", [self::REJECTED_STATUS_ID, self::UNPAYED_STATUS_ID, self::EXPIRED_STATUS_ID]]
            ]
        ];
        return $this->getListAction($params);
    }

    public function getDraftsAction(){
        $params = [
            "filter" => [
                ["UF_USER_ID", "=", $this->getCurrentUser()->getId()],
                ["UF_STATUS", "in", [self::DRAFT_STATUS_ID]]
            ]
        ];
        return $this->getListAction($params);
    }

    public function getActiveAction(){
        $params = [
            "filter" => [
                ["UF_USER_ID", "=", $this->getCurrentUser()->getId()],
                ["UF_STATUS", "in", [self::MODERATED_STATUS_ID, self::MOVING_STATUS_ID, self::POSTED_STATUS_ID]]
            ]
        ];
        return $this->getListAction($params);
    }

    public function getArchieveAction(){
        $params = [
            "filter" => [
                ["UF_USER_ID", "=", $this->getCurrentUser()->getId()],
                ["UF_STATUS", "in", [self::CLOSED_STATUS_ID]]
            ]
        ];
        return $this->getListAction($params);
    }

    public function publishAction(){
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        try {
            $res = $adService->publish();
            if(!$res->isSuccess()){
                foreach($res->getErrors() as $error){
                    $this->addError($error);
                }
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            return ["id" => $res->getId()];
        }
        catch (\Exception $e){
            if($e instanceof AdNotFoundAuthException) {
                $this->addError(new Error($e->getMessage(), AdNotFoundAuthException::AD_NOT_FOUND));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            if($e instanceof PublishException){
                $this->addError(new Error($e->getMessage(), PublishException::ILLEGAL_STATUS));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
        }
    }

    public function archieveAction(){
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        try {
            $res = $adService->archieve();
            if(!$res->isSuccess()){
                foreach($res->getErrors() as $error){
                    $this->addError($error);
                }
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            return ["id" => $res->getId()];
        }
        catch (\Exception $e){
            if($e instanceof AdNotFoundAuthException) {
                $this->addError(new Error($e->getMessage(), AdNotFoundAuthException::AD_NOT_FOUND));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            if($e instanceof PublishException){
                $this->addError(new Error($e->getMessage(), PublishException::ILLEGAL_STATUS));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
        }
    }

    public function promoteAction(){
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        try {
            $res = $adService->promote();
            if(!$res->isSuccess()){
                foreach($res->getErrors() as $error){
                    $this->addError($error);
                }
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            $url = $adService->getPayUrl($res->getId());
            return ["id" => $res->getId(), "url" => $url ? $url : null];
        }
        catch (\Exception $e){
            if($e instanceof AdNotFoundAuthException) {
                $this->addError(new Error($e->getMessage(), AdNotFoundAuthException::AD_NOT_FOUND));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            if($e instanceof PublishException){
                $this->addError(new Error($e->getMessage(), PublishException::ILLEGAL_STATUS));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
        }
    }

    public function payAction(){
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        try {
            return $adService->pay();
        }
        catch (\Exception $e){
            if($e instanceof AdNotFoundAuthException) {
                $this->addError(new Error($e->getMessage(), AdNotFoundAuthException::AD_NOT_FOUND));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
        }
    }
    
    public function deleteAction() 
    {
        $serviceLocator = ServiceLocator::getInstance();
        $adService = $serviceLocator->get("site.api.ad");
        try{
            $res = $adService->delete();
            if($res instanceof DeleteResult){
                foreach($res->getErrors() as $error){
                    $this->addError($error);
                }
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
            return ["id" => $res];
        }
        catch (\Exception $e){
            if($e instanceof AdNotFoundAuthException) {
                $this->addError(new Error($e->getMessage(), AdNotFoundAuthException::AD_NOT_FOUND));
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, null, $this);
            }
        }
    }

    public function favoritesAction(){
        $favorites = ListFavouritesClass::ListFavouritesClassMethod();
        if(!$favorites) return [];
        $params = [
            "filter" => [
                ["ID", "in", $favorites]
            ]
        ];
        return $this->getListAction($params);
    }

    public function toggleFavoritesAction(){
        $request = $this->getRequest()->toArray();
        $errors = [];
        $res = FavouritesClass::FavouritesClassMethod($request["id"], $errors);
        if(!$res){
            foreach($errors as $error_key => $error_message){
                switch ($error_key){
                    case 'idExists':{
                        $this->addError(new Error(
                            "Неверный id объявления",
                            "wrong_id"
                        ));
                        break;
                    }
                }
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, "site.api", $this);
            }
        }
        return [];
    }

    public function setSearchRequestsAction(){
        $request = $this->getRequest()->toArray();
        $errors = [];
        SearchDataClass::SaveSearchClassData($request["q"], $errors);
        return [];
    }

    public function getSearchRequestsAction(){
        $res = SearchDataClass::GetSaveSearchData();
        return $res ? $res : [];
    }

    public function addCallAction() {
        $request = $this->getRequest()->toArray();
        $errors = [];
        $res = CountCallsClass::CountCallsClassMethod($request["id"], $errors);
        if(!$res){
            foreach($errors as $error_key => $error_message){
                switch ($error_key){
                    case 'idExists':{
                        $this->addError(new Error(
                            "Неверный id объявления",
                            "wrong_id"
                        ));
                        break;
                    }
                }
                http_response_code(400);
                return new EventResult(EventResult::ERROR, null, "site.api", $this);
            }
        }
        return [];
    }

    public function addViewedAction(){

    }

    protected function getDefaultPreFilters():array
    {
        return [
            new Csrf()
        ];
    }

    public function configureActions():array
    {
        return [
            "getList" => [
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "getFilter" => [
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase(),
                    new FilterReorder()
                ]
            ],
            "getCreateValues" => [
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "create" => [
                "+prefilters" => [
                    new Authentication(),
                    new Validator([
                        (new Validation("brand"))->number()->required(),
                        (new Validation("model"))->required()->maxLength(255),
                        (new Validation("condition"))->required()->number(),
                        (new Validation("gender"))->required()->number(),
                        (new Validation("material"))->required()->number(),
                        (new Validation("watchband"))->required()->number(),
                        (new Validation("sellerType"))->required()->number(),
                        (new Validation("documentsList"))->required()->number(),
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
                        (new Validation("photo"))->required()->image(3)->maxCount(10),
                        (new Validation("price"))->price()->required(),
                        (new Validation("promotion"))->required()->bool(),
                        (new Validation("promotionType"))->number(),
                    ])
                ]
            ],
            "createDraft" => [
                "+prefilters" => [
                    new Authentication(),
                    new Validator([
                        (new Validation("brand"))->number()->required(),
                        (new Validation("model"))->maxLength(255)->required(),
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
                        (new Validation("promotion"))->bool()->required(),
                        (new Validation("promotionType"))->number()
                    ])
                ]
            ],
            "getOne" => [
                "+prefilters" => [
                    new Validator([
                        (new Validation("id"))->required()->number()
                    ])
                ],
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "edit" => [
                "+prefilters" => [
                    new Authentication(),
                    new Validator([
                        (new Validation("id"))->number()->required()
                    ]),
                    new EditAd()
                ]
            ],
            "getWaiting" => [
                "+prefilters" => [
                    new Authentication()
                ],
                "+postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "getDrafts" => [
                "+prefilters" => [
                    new Authentication()
                ],
                "+postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "getActive" => [
                "+prefilters" => [
                    new Authentication()
                ],
                "+postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "getArchieve" => [
                "+prefilters" => [
                    new Authentication()
                ],
                "+postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "publish" => [
                "+prefilters" => [
                    new Authentication(),
                    new Validator([
                        (new Validation("id"))->required()->number()
                    ])
                ]
            ],
            "archieve" => [
                "+prefilters" => [
                    new Authentication(),
                    new Validator([
                        (new Validation("id"))->required()->number()
                    ])
                ]
            ],
            "promote" => [
                "+prefilters" => [
                    new Authentication(),
                    new Validator([
                        (new Validation("id"))->required()->number(),
                        (new Validation("promotionType"))->required()->number()
                    ])
                ]
            ],
            "pay" => [
                "+prefilters" => [
                    new Authentication(),
                    new Validator([
                        (new Validation("id"))->required()->number()
                    ])
                ]
            ],
            "delete" => [
                "+prefilters" => [
                    new Authentication(),
                    new Validator([
                        (new Validation("id"))->required()->number()
                    ])
                ]
            ],
            "favorites" => [
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "toggleFavorites" => [
                "+prefilters" => [
                    new Validator([
                        (new Validation("id"))->number()->required()
                    ])
                ],
            ],
            "setSearchRequests" => [
                "+prefilters" => [
                    new Validator([
                        (new Validation("q"))->required()->not_empty()
                    ])
                ]
            ],
            "addCall" => [
                "+prefilters" => [
                    new Validator([
                        (new Validation("id"))->required()->number()
                    ])
                ]
            ],
            "addViewed" => [
                "+prefilters" => [
                    new Validator([
                        (new Validation("id"))->required()->number()
                    ])
                ]
            ]
        ];
    }

    public function finalizeResponse(Response $response){
        $data = $response->getContent();
        $data = Json::decode($data);
        $data = array_merge($data, ["_metadata" => $this->navData]);
        $response->setContent(Json::encode($data));
    }
}