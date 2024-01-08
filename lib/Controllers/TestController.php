<?php

namespace Site\Api\Controllers;

use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Site\Api\Forms\SignupForm;
use Site\Api\Prefilters\ApiKey;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Site\Api\Traits\ControllerTrait;

class TestController extends Controller
{
    use ControllerTrait;

    // public function configureActions(): array
    // {
    //     return [
    //         'get' => [
    //             'prefilters' => [
    //                 new ActionFilter\Csrf()
    //             ],
    //             'postfilters' => []
    //         ]
    //     ];
    // }

    public function getDefaultPreFilters()
    {
        return [
            new ApiKey()
        ];
    }

    protected function prepareParams(): bool
    {

        return parent::prepareParams();
    }

    public function getAction()
    {
        /**
         * Можно вывести результат с формы таким образом
         *  return $this->fromPostParameters(new SignupForm());
         * или таким
         *
         */
        $form = new SignupForm();
        $form
        ->getPostParameters()
        ->validation()
        ->action();
        $this->errorCollection = $form->getErrorCollection();
        return $form->getReplyData();
    }
}
