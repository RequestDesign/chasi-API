<?php

namespace Site\Api\Traits;

use Site\Api\Parameters\Parameter;

trait ControllerTrait
{
    /**
     * Хелпер для правильного формирования вывода ответа с
     * формы
     *
     * @param Form $form
     * @return array
     */
    private function allParameters(Parameter $form): array
    {
        $form
        ->getGetParameters()
        ->validation()
        ->action();
        $this->errorCollection = $form->getErrorCollection();
        return $form->getReplyData();
    }

    private function getParameters(Parameter $form): array
    {
        $form
        ->getGetParameters()
        ->validation()
        ->action();
        $this->errorCollection = $form->getErrorCollection();
        return $form->getReplyData();
    }
    
    private function postParameters(Parameter $form): array
    {
        $form
        ->getPostParameters()
        ->getFileList()
        ->validation()
        ->action();
        $this->errorCollection = $form->getErrorCollection();
        return $form->getReplyData();
    }
}
