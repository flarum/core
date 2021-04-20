<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Extension\Exception\DependentExtensionsException;
use Flarum\Extension\Exception\DependentExtensionsExceptionHandler;
use Flarum\Extension\Exception\MissingDependenciesException;
use Flarum\Extension\Exception\MissingDependenciesExceptionHandler;
use Flarum\Foundation\ErrorHandling\ExceptionHandler;
use Flarum\Foundation\ErrorHandling\LogReporter;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Http\Content\NotAuthenticated;
use Flarum\Http\Content\NotFound;
use Flarum\Http\Content\PermissionDenied;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException as IlluminateValidationException;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class ErrorServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton('flarum.error.statuses', function () {
            return [
                // 400 Bad Request
                'csrf_token_mismatch' => 400,
                'invalid_parameter' => 400,

                // 401 Unauthorized
                'invalid_access_token' => 401,
                'not_authenticated' => 401,

                // 403 Forbidden
                'invalid_confirmation_token' => 403,
                'permission_denied' => 403,

                // 404 Not Found
                'not_found' => 404,

                // 405 Method Not Allowed
                'method_not_allowed' => 405,

                // 429 Too Many Requests
                'too_many_requests' => 429,
            ];
        });

        $this->container->singleton('flarum.error.classes', function () {
            return [
                InvalidParameterException::class => 'invalid_parameter',
                ModelNotFoundException::class => 'not_found',
            ];
        });

        $this->container->singleton('flarum.error.contents', function () {
            return [
                NotAuthenticatedException::class => NotAuthenticated::class,
                PermissionDeniedException::class => PermissionDenied::class,
                ModelNotFoundException::class => NotFound::class,
                RouteNotFoundException::class => NotFound::class,
            ];
        });

        $this->container->singleton('flarum.error.handlers', function () {
            return [
                IlluminateValidationException::class => ExceptionHandler\IlluminateValidationExceptionHandler::class,
                ValidationException::class => ExceptionHandler\ValidationExceptionHandler::class,
                DependentExtensionsException::class => DependentExtensionsExceptionHandler::class,
                MissingDependenciesException::class => MissingDependenciesExceptionHandler::class,
            ];
        });

        $this->container->singleton(Registry::class, function () {
            return new Registry(
                $this->container->make('flarum.error.statuses'),
                $this->container->make('flarum.error.classes'),
                $this->container->make('flarum.error.handlers'),
                $this->container->make('flarum.error.contents'),
            );
        });

        $this->container->tag(LogReporter::class, Reporter::class);
    }
}
