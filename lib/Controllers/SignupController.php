<?php

namespace Site\Api\Controllers;

use Site\Api\Forms\SignupForm;
use Site\Api\Prefilters\ApiKey;
use Bitrix\Main\Engine\Controller;

/**
 * SignupController class
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class SignupController extends Controller
{
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
        $form = new SignupForm();
        $form
        ->getPostParams()
        ->validation()
        ->action();
        $this->errorCollection = $form->getErrorCollection();
        return $form->getReplyData();
    }
}
