<?php

namespace Site\Api;

use Bitrix\Main\Routing\RoutingConfigurator;
use Site\Api\Controllers\AdController;
use Site\Api\Controllers\AdvController;
use Site\Api\Controllers\AuthenticationController;
use Site\Api\Controllers\BrandController;
use Site\Api\Controllers\CsrfTokenController;
use Site\Api\Controllers\UserController;

/**
 * Routes
 *
 */
class Routes
{
    //size
    public static function getRoutes(RoutingConfigurator $routes): void
    {
        $routes->prefix('api')->group(function (RoutingConfigurator $routes) {
            // CSRF
            $routes->get('csrf', [CsrfTokenController::class, 'getCsrf']);

            // auth
            $routes->post('login', [AuthenticationController::class, 'login']);
            $routes->post('logout', [AuthenticationController::class, 'logout']);
            $routes->post('confirm-register', [AuthenticationController::class, 'confirmRegistration']);
            $routes->post('confirm-code', [AuthenticationController::class, 'sendConfirmCode']);
            $routes->post('forgot', [AuthenticationController::class, 'forgot']);
            $routes->post('confirm-forgot-code', [AuthenticationController::class, 'confirmForgot']);
            $routes->post('change-password', [AuthenticationController::class, 'changePassword']);

            // user
            $routes->post('register', [UserController::class, 'create']);
            $routes->get('users/{id}', [UserController::class, 'getOne']);

            // бренды
            $routes->get('brands', [BrandController::class, 'getList']);

            // объявления
            $routes->get('ads', [AdController::class, 'getList']);
            $routes->post('ads', [AdController::class, 'create']);
            $routes->get('ads-create-values', [AdController::class, 'getCreateValues']);
            $routes->get('filter', [AdController::class, 'getFilter']);

            // рекламы

            $routes->get('advs', [AdvController::class, 'getList']);
            //$routes->post('registration', [AuthenticationController::class, 'registrationAction']);
            //$routes->get('logout', [AuthenticationController::class, 'logoutAction']);
            //$routes->post('confirm.registration', [AuthenticationController::class, 'confirmRegistrationAction']);
            //$routes->post('forget.password', [AuthenticationController::class, 'forgetPasswordAction']);
            //$routes->get('get.sessid', [CsrfTokenController::class, 'getCsrfAction']);
        });
    }
}
