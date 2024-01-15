<?php

namespace Site\Api\Controllers;

use Site\Api\Prefilters\Csrf;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Site\Api\Traits\ControllerTrait;
use Site\Api\Parameters\UserParameter;

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
