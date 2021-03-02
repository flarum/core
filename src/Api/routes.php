<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Controller;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;

return function (RouteCollection $map, RouteHandlerFactory $route) {
    // Get forum information
    $map->get(
        '/',
        'forum.show',
        $route->toController(Controller\ShowForumController::class)
    );

    // Retrieve authentication token
    $map->post(
        '/token',
        'token',
        $route->toController(Controller\CreateTokenController::class)
    );

    $map->get(
        '/tokens',
        'tokens.index',
        $route->toController(Controller\ListTokensController::class)
    );

    $map->delete(
        '/tokens/{id}',
        'tokens.delete',
        $route->toController(Controller\DeleteTokenController::class)
    );

    // Send forgot password email
    $map->post(
        '/forgot',
        'forgot',
        $route->toController(Controller\ForgotPasswordController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    // List users
    $map->get(
        '/users',
        'users.index',
        $route->toController(Controller\ListUsersController::class)
    );

    // Register a user
    $map->post(
        '/users',
        'users.create',
        $route->toController(Controller\CreateUserController::class)
    );

    // Get a single user
    $map->get(
        '/users/{id}',
        'users.show',
        $route->toController(Controller\ShowUserController::class)
    );

    // Edit a user
    $map->patch(
        '/users/{id}',
        'users.update',
        $route->toController(Controller\UpdateUserController::class)
    );

    // Delete a user
    $map->delete(
        '/users/{id}',
        'users.delete',
        $route->toController(Controller\DeleteUserController::class)
    );

    // Upload avatar
    $map->post(
        '/users/{id}/avatar',
        'users.avatar.upload',
        $route->toController(Controller\UploadAvatarController::class)
    );

    // Remove avatar
    $map->delete(
        '/users/{id}/avatar',
        'users.avatar.delete',
        $route->toController(Controller\DeleteAvatarController::class)
    );

    // send confirmation email
    $map->post(
        '/users/{id}/send-confirmation',
        'users.confirmation.send',
        $route->toController(Controller\SendConfirmationEmailController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    // List notifications for the current user
    $map->get(
        '/notifications',
        'notifications.index',
        $route->toController(Controller\ListNotificationsController::class)
    );

    // Mark all notifications as read
    $map->post(
        '/notifications/read',
        'notifications.readAll',
        $route->toController(Controller\ReadAllNotificationsController::class)
    );

    // Mark a single notification as read
    $map->patch(
        '/notifications/{id}',
        'notifications.update',
        $route->toController(Controller\UpdateNotificationController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | Discussions
    |--------------------------------------------------------------------------
    */

    // List discussions
    $map->get(
        '/discussions',
        'discussions.index',
        $route->toController(Controller\ListDiscussionsController::class)
    );

    // Create a discussion
    $map->post(
        '/discussions',
        'discussions.create',
        $route->toController(Controller\CreateDiscussionController::class)
    );

    // Show a single discussion
    $map->get(
        '/discussions/{id}',
        'discussions.show',
        $route->toController(Controller\ShowDiscussionController::class)
    );

    // Edit a discussion
    $map->patch(
        '/discussions/{id}',
        'discussions.update',
        $route->toController(Controller\UpdateDiscussionController::class)
    );

    // Delete a discussion
    $map->delete(
        '/discussions/{id}',
        'discussions.delete',
        $route->toController(Controller\DeleteDiscussionController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | Posts
    |--------------------------------------------------------------------------
    */

    // List posts, usually for a discussion
    $map->get(
        '/posts',
        'posts.index',
        $route->toController(Controller\ListPostsController::class)
    );

    // Create a post
    $map->post(
        '/posts',
        'posts.create',
        $route->toController(Controller\CreatePostController::class)
    );

    // Show a single or multiple posts by ID
    $map->get(
        '/posts/{id}',
        'posts.show',
        $route->toController(Controller\ShowPostController::class)
    );

    // Edit a post
    $map->patch(
        '/posts/{id}',
        'posts.update',
        $route->toController(Controller\UpdatePostController::class)
    );

    // Delete a post
    $map->delete(
        '/posts/{id}',
        'posts.delete',
        $route->toController(Controller\DeletePostController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    */

    // List groups
    $map->get(
        '/groups',
        'groups.index',
        $route->toController(Controller\ListGroupsController::class)
    );

    // Create a group
    $map->post(
        '/groups',
        'groups.create',
        $route->toController(Controller\CreateGroupController::class)
    );

    // Edit a group
    $map->patch(
        '/groups/{id}',
        'groups.update',
        $route->toController(Controller\UpdateGroupController::class)
    );

    // Delete a group
    $map->delete(
        '/groups/{id}',
        'groups.delete',
        $route->toController(Controller\DeleteGroupController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | Administration
    |--------------------------------------------------------------------------
    */

    // Toggle an extension
    $map->patch(
        '/extensions/{name}',
        'extensions.update',
        $route->toController(Controller\UpdateExtensionController::class)
    );

    // Uninstall an extension
    $map->delete(
        '/extensions/{name}',
        'extensions.delete',
        $route->toController(Controller\UninstallExtensionController::class)
    );

    // Update settings
    $map->post(
        '/settings',
        'settings',
        $route->toController(Controller\SetSettingsController::class)
    );

    // Update a permission
    $map->post(
        '/permission',
        'permission',
        $route->toController(Controller\SetPermissionController::class)
    );

    // Upload a logo
    $map->post(
        '/logo',
        'logo',
        $route->toController(Controller\UploadLogoController::class)
    );

    // Remove the logo
    $map->delete(
        '/logo',
        'logo.delete',
        $route->toController(Controller\DeleteLogoController::class)
    );

    // Upload a favicon
    $map->post(
        '/favicon',
        'favicon',
        $route->toController(Controller\UploadFaviconController::class)
    );

    // Remove the favicon
    $map->delete(
        '/favicon',
        'favicon.delete',
        $route->toController(Controller\DeleteFaviconController::class)
    );

    // Clear the cache
    $map->delete(
        '/cache',
        'cache.clear',
        $route->toController(Controller\ClearCacheController::class)
    );

    // List available mail drivers, available fields and validation status
    $map->get(
        '/mail/settings',
        'mailSettings.index',
        $route->toController(Controller\ShowMailSettingsController::class)
    );

    // Send test mail post
    $map->post(
        '/mail/test',
        'mailTest',
        $route->toController(Controller\SendTestMailController::class)
    );
};
