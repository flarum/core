<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Forum\Content;
use Flarum\Forum\Controller;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;

return function (RouteCollection $map, RouteHandlerFactory $route) {
    $map->get(
        '/all',
        'index',
        $route->toForum(Content\Index::class)
    );

    $map->get(
        '/d/{id:\d+(?:-[^/]*)?}[/{near:[^/]*}]',
        'discussion',
        $route->toForum(Content\Discussion::class)
    );

    $map->get(
        '/u/{username}[/{filter:[^/]*}]',
        'user',
        $route->toForum(Content\User::class)
    );

    $map->get(
        '/settings',
        'settings',
        $route->toForum(Content\AssertRegistered::class)
    );

    $map->get(
        '/notifications',
        'notifications',
        $route->toForum(Content\AssertRegistered::class)
    );

    $map->get(
        '/logout',
        'logout',
        $route->toController(Controller\LogOutController::class)
    );

    $map->post(
        '/login',
        'login',
        $route->toController(Controller\LogInController::class)
    );

    $map->get(
        '/auth/{provider}',
        'sso',
        $route->toController(Controller\SsoController::class)
    );

    $map->post(
        '/auth/{provider}',
        'sso',
        $route->toController(Controller\SsoController::class)
    );

    $map->post(
        '/register',
        'register',
        $route->toController(Controller\RegisterController::class)
    );

    $map->get(
        '/confirm/{token}',
        'confirmEmail',
        $route->toController(Controller\ConfirmEmailController::class)
    );

    $map->get(
        '/reset/{token}',
        'resetPassword',
        $route->toController(Controller\ResetPasswordController::class)
    );

    $map->post(
        '/reset',
        'savePassword',
        $route->toController(Controller\SavePasswordController::class)
    );
};
