<?php

namespace Site\Api\Controllers;

require_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/AddReviewClass.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ajax/class/DeleteReviewClass.php");

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');

use Bitrix\Iblock\Iblock;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Response;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Json;
use Site\Api\Exceptions\FilterException;
use Site\Api\Postfilters\ChangeKeyCase;
use Site\Api\Postfilters\RecursiveResponseList;
use Site\Api\Prefilters\Csrf;
use Site\Api\Prefilters\Validator;
use Site\Api\Services\Validation;
use AddReviewClass;
use DeleteReviewClass;

class ReviewController extends Controller
{
    const QUALITIES = [
        "VEZHLIV" => "polite",
        "DOBRO" => "conscientious",
        "BYSTRO" => "quick_reply",
        "XAMIT" => "rude",
        "NECHES" => "unfair",
        "DOLGO" => "long_reply"
    ];

    public function getListAction(): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $reviewService = $serviceLocator->get("site.api.review");
        try{
            $els = $reviewService->getList();
            $this->navData = $reviewService->getNavigationData();
            return $els;
        }
        catch (FilterException $e){
            $this->addError(new Error($e->getMessage(), FilterException::FILTER_EXCEPTION_CODE));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
    }

    public function getUserReviewsAction(): array|EventResult
    {
        $serviceLocator = ServiceLocator::getInstance();
        $reviewService = $serviceLocator->get("site.api.review");
        try{
            $els = $reviewService->getUserReviews();
            $this->navData = $reviewService->getNavigationData();
            return $els;
        }
        catch (FilterException $e){
            $this->addError(new Error($e->getMessage(), FilterException::FILTER_EXCEPTION_CODE));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, null, $this);
        }
    }

    public function createAction(): array|EventResult
    {
        $request = $this->getRequest()->toArray();
        $errors = [];
        $res = AddReviewClass::AddReviewClassMethod(
            $this->getCurrentUser()->getId(),
            $request["rating"],
            $this->request["to"],
            $this->getCurrentUser()->getId(),
            $this->request["polite"] ?? false,
            $this->request["conscientious"] ?? false,
            $this->request["quick_reply"] ?? false,
            $this->request["rude"] ?? false,
            $this->request["unfair"] ?? false,
            $this->request["long_reply"] ?? false,
            $errors
        );
        if($res) {
            http_response_code(201);
            return ["id" => $res];
        }
        $this->addError(new Error("Не удалось создать отзыв", "review_create_failed"));
        http_response_code(400);
        return new EventResult(EventResult::ERROR, null, null, $this);
    }

    public function getReviewRatingsAction()
    {
        $ratings = Iblock::wakeUp(11)->getEntityDataClass()::getList([
            "select" => ["ID", "NAME", "quality|CODE"=>"quality_alias.CODE", "quality|NAME"=>"quality_alias.NAME"],
            "runtime" => [
                "quality_alias" => [
                    "data_type" => PropertyTable::class,
                    "reference" => [
                        "=this.XARAKTERISTIKA.VALUE" => "ref.ID"
                    ],
                    ["join_type" => "left"]
                ]
            ]
        ])->fetchAll();

        foreach ($ratings as &$rating) {
            $rating["quality|CODE"] = self::QUALITIES[$rating["quality|CODE"]];
        }
        unset($rating);

        $resultRatings = [];
        foreach($ratings as $rating) {
            if(!isset($resultRatings[$rating["ID"]])){
                $resultRatings[$rating["ID"]] = $rating;
                $resultRatings[$rating["ID"]]["qualities"] = [];
                $resultRatings[$rating["ID"]]["qualities"][] = ["code" => $rating["quality|CODE"], "name" => $rating["quality|NAME"]];
                unset($resultRatings[$rating["ID"]]["quality|CODE"], $resultRatings[$rating["ID"]]["quality|NAME"]);
            }
            else {
                $resultRatings[$rating["ID"]]["qualities"][] = ["code" => $rating["quality|CODE"], "name" => $rating["quality|NAME"]];
            }
        }
        return array_values($resultRatings);
    }

    public function deleteAction(){
        $request = $this->getRequest()->toArray();
        $review = Iblock::wakeUp(4)->getEntityDataClass()::getList([
            "select" => ["ID"],
            "filter" => [
                "OT_KOGO.VALUE" => $this->getCurrentUser()->getId(),
                "ID"=>$request["id"]
            ]
        ])->fetch();
        if(!$review){
            $this->addError(new Error("У Вас нет прав для удаления чужих отзывов", "wrong_roots"));
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, "site.api", $this);
        }
        $errors = [];
        $res = DeleteReviewClass::DeleteReviewClassMethod($this->getCurrentUser()->getId(), $request["id"], $errors);
        if(!$res){
            foreach($errors as $error_key => $error_message){
                switch ($error_key){
                    case 'userExists':{
                        $this->addError(new Error(
                            "Пользователь не найден",
                            "illegal_user"
                        ));
                        break;
                    }
                }
            }
            http_response_code(400);
            return new EventResult(EventResult::ERROR, null, "site.api", $this);
        }
        return ["id" => $res];
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
            "getUserReviews" => [
                "+prefilters" => [
                    new Authentication()
                ],
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "create" => [
                "+prefilters" => [
                    new Authentication(),
                    new Validator([
                        (new Validation('to'))->number()->required(),
                        (new Validation('rating'))->number()->required(),
                        (new Validation('polite'))->bool(),
                        (new Validation('conscientious'))->bool(),
                        (new Validation('quick_reply'))->bool(),
                        (new Validation('rude'))->bool(),
                        (new Validation('unfair'))->bool(),
                        (new Validation('long_reply'))->bool()
                    ])
                ]
            ],
            "getReviewRatings" => [
                "postfilters" => [
                    new RecursiveResponseList(),
                    new ChangeKeyCase()
                ]
            ],
            "delete" => [
                "+prefilters" => [
                    new Authentication(),
                    new Validator([
                        (new Validation('id'))->number()->required()
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