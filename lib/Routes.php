<?php

namespace Site\Api;

use Bitrix\Main\Routing\RoutingConfigurator;
use Site\Api\Controllers\AboutController;
use Site\Api\Controllers\AdController;
use Site\Api\Controllers\AdvController;
use Site\Api\Controllers\AuthenticationController;
use Site\Api\Controllers\BrandController;
use Site\Api\Controllers\CityController;
use Site\Api\Controllers\CsrfTokenController;
use Site\Api\Controllers\FAQController;
use Site\Api\Controllers\ReviewController;
use Site\Api\Controllers\TariffController;
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
            $routes->post('forgot/confirm-code', [AuthenticationController::class, 'sendForgotConfirmCode']);
            $routes->post('confirm-forgot-code', [AuthenticationController::class, 'confirmForgot']);
            $routes->post('change-password', [AuthenticationController::class, 'changePassword']);

            // user
            $routes->post('register', [UserController::class, 'create']);
            $routes->get('users/{id}', [UserController::class, 'getOne']);
            $routes->delete('users', [UserController::class, 'delete']);
            $routes->post('users/{id}', [UserController::class, 'edit']);
            $routes->get('user', [UserController::class, 'getCurrentUser']);
            $routes->delete('user/photo', [UserController::class, 'deletePhoto']);
            $routes->get('user/ads/waiting', [AdController::class, 'getWaiting']);
            $routes->get('user/ads/drafts', [AdController::class, 'getDrafts']);
            $routes->get('user/ads/active', [AdController::class, 'getActive']);
            $routes->get('user/ads/archieve', [AdController::class, 'getArchieve']);
            $routes->post('user/ads/{id}/publish', [AdController::class, 'publish']);
            $routes->post('user/ads/{id}/archieve', [AdController::class, 'archieve']);
            $routes->post('user/ads/{id}/promote', [AdController::class, 'promote']);
            $routes->post('user/ads/{id}/pay', [AdController::class, 'pay']);
            $routes->post('user/confirm-code', [AuthenticationController::class, 'sendForgotConfirmCode']);
            $routes->post('user/confirm-email', [UserController::class, 'confirmEmail']);
            $routes->post('user/confirm-phone', [UserController::class, 'confirmPhone']);
            $routes->get('favorites', [AdController::class, 'favorites']);
            $routes->post('favorites/{id}', [AdController::class, 'toggleFavorites']);
            $routes->get('viewed', [AdController::class, 'getViewed']);
            $routes->post('viewed/{id}', [AdController::class, 'addViewed']);
            $routes->get('user/unread', [UserController::class, 'unread']);

            // отзывы
            $routes->get('users/{id}/reviews', [ReviewController::class, 'getList']);
            $routes->get('user/reviews', [ReviewController::class, 'getUserReviews']);
            $routes->post('user/reviews', [ReviewController::class, 'create']);
            $routes->get('review-ratings', [ReviewController::class, 'getReviewRatings']);
            $routes->delete('reviews/{id}', [ReviewController::class, 'delete']);
            $routes->post('reviews/{id}/complain', [ReviewController::class, 'complain']);

            // бренды
            $routes->get('brands', [BrandController::class, 'getList']);

            // объявления
            $routes->get('ads', [AdController::class, 'getList']);
            $routes->post('ads', [AdController::class, 'create']);
            $routes->post('ads/draft', [AdController::class, 'createDraft']);
            $routes->get('ads-create-values', [AdController::class, 'getCreateValues']);
            $routes->get('filter', [AdController::class, 'getFilter']);
            $routes->get('ads/removing-reasons', [AdController::class, 'getReasons']);
            $routes->get('ads/{id}', [AdController::class, 'getOne']);
            $routes->post('ads/{id}', [AdController::class, 'edit']);
            $routes->delete('ads/{id}', [AdController::class, 'delete']);
            $routes->post('search-requests', [AdController::class, 'setSearchRequests']);
            $routes->get('search-requests', [AdController::class, 'getSearchRequests']);
            $routes->post('ads/{id}/call', [AdController::class, 'addCall']);

            // рекламы

            $routes->get('advs', [AdvController::class, 'getList']);

            // faq

            $routes->get('faq', [FAQController::class, 'getList']);

            // about

            $routes->get('about', [AboutController::class, 'getOne']);

            // cities

            $routes->get('cities', [CityController::class, 'getList']);

            //tariffs

            $routes->get('tariffs', [TariffController::class, 'getList']);
        });
    }
}
