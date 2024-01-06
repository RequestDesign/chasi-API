<?php

$module_folder = \Bitrix\Main\Application::getDocumentRoot() . '/local/modules/site.api';

\Bitrix\Main\Loader::registerNamespace('Site\\Api\\', $module_folder . '/lib');

// $classes = [
//     'Almat\Su\Controller\ActionFilter\SimpleToken' => 'controller/actionfilter/SimpleToken.php',
// ];

// \Bitrix\Main\Loader::registerAutoLoadClasses(
//     'almat.su',
//     $classes,
// );