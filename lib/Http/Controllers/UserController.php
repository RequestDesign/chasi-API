<?php

namespace Site\Api\Http\Controllers;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Site\Api\Http\Prefilters\Csrf;
use Site\Api\Parameters\SignupParameter;
use Site\Api\Parameters\UserParameter;
use Site\Api\Traits\ControllerTrait;

/**
 * UserController class
 *
 * @author AidSoul <work-aidsoul@outlook.com>
 */
class UserController extends Controller
{
    use ControllerTrait;

    public function configureActions(): array
    {
        return [
            'get' => [
                'prefilters' => [
                    new Csrf(),
                    new ActionFilter\Authentication()
                ],
                'postfilters' => []
            ],
        ];
    }

    protected function prepareParams(): bool
    {
        return parent::prepareParams();
    }

    public function getAction()
    {
        return $this->getReplyAction('post', new UserParameter());
    }
}
