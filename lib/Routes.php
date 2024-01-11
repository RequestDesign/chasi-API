<?php

namespace Site\Api;

use Bitrix\Main\Routing\RoutingConfigurator;
use Site\Api\Http\Controllers\AuthenticationController;
use Site\Api\Http\Controllers\CsrfTokenController;
use Site\Api\Http\Controllers\UserController;

/**
 * Routes
 *
 */
class Routes
{
    public static function getRoutes(RoutingConfigurator $routes): void
    {
        $routes->prefix('api')->group(function (RoutingConfigurator $routes) {
            $routes->post('registration', [AuthenticationController::class, 'registrationAction']);
            $routes->post('login', [AuthenticationController::class, 'loginAction']);
            $routes->get('logout', [AuthenticationController::class, 'logoutAction']);
            $routes->post('confirm.registration', [AuthenticationController::class, 'confirmRegistrationAction']);
            $routes->post('forget.password', [AuthenticationController::class, 'forgetPasswordAction']);
            $routes->get('get.sessid', [CsrfTokenController::class, 'getCsrfAction']);

            // $routes->put('user', [UserController::class, 'getAction']);
        });
    }
}
