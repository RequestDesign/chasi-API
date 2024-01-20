<?php
$module_folder = \Bitrix\Main\Application::getDocumentRoot() . '/local/modules/site.api';

\Bitrix\Main\Loader::registerNamespace('Site\\Api\\', $module_folder . '/lib');


// $classes = [
//     'Site\Api\Routes\Routes' => $module_folder . '/lib/Routes/Routes.php',
// ];

// \Bitrix\Main\Loader::registerAutoLoadClasses(
//     'site.api',
//     $classes,
// );
