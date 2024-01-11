<?php

namespace Site\Api\Traits;

use Site\Api\Parameters\Parameter;

trait ControllerTrait
{
    /**
     * Хелпер для правильного формирования вывода ответа с
     * формы
     *
     * @param string $requestType get/post
     * @param Parameter $form
     * @return array
     */
    private function getReplyAction(string $requestType = 'get', Parameter $parameter): array
    {
        /**
         * @var $request \Bitrix\Main\HttpRequest
         */
        $request = $this->getRequest();
        $params = [];
        switch ($requestType) {
            case 'get':
                $params = $request->getQueryList()->toArray();
                break;
            case 'post':
                $params = $request->getPostList()->toArray();
                if ($files = $request->getFileList()->toArray()) {
                    $files = array_merge($params, $files);
                }
                break;
        }
        $parameter
        ->setParams($params)
        ->validation()
        ->action();
        $this->errorCollection = $parameter->getErrorCollection();
        return $parameter->getReplyData();
    }
}
