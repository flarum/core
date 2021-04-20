<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\NotificationSerializer;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ErrorHandling\JsonApiFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Laminas\Stratigility\MiddlewarePipe;

class ApiServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url) {
            return $url->addCollection('api', $this->container->make('flarum.api.routes'), 'api');
        });

        $this->container->singleton('flarum.api.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->container->singleton('flarum.api.throttlers', function () {
            return [
                'bypassThrottlingAttribute' => function ($request) {
                    if ($request->getAttribute('bypassThrottling')) {
                        return false;
                    }
                }
            ];
        });

        $this->container->bind(Middleware\ThrottleApi::class, function ($container) {
            return new Middleware\ThrottleApi($container->make('flarum.api.throttlers'));
        });

        $this->container->singleton('flarum.api.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'flarum.api.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\SetLocale::class,
                'flarum.api.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\ThrottleApi::class
            ];
        });

        $this->container->bind('flarum.api.error_handler', function () {
            $jsonFormatter = new JsonApiFormatter($this->container['flarum.config']->inDebugMode());

            return new HttpMiddleware\HandleErrors(
                $this->container->make(Registry::class),
                $jsonFormatter,
                $jsonFormatter, // This won't be used.
                $this->container->tagged(Reporter::class)
            );
        });

        $this->container->bind('flarum.api.route_resolver', function () {
            return new HttpMiddleware\ResolveRoute($this->container->make('flarum.api.routes'));
        });

        $this->container->singleton('flarum.api.handler', function () {
            $pipe = new MiddlewarePipe;

            foreach ($this->container->make('flarum.api.middleware') as $middleware) {
                $pipe->pipe($this->container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->singleton('flarum.api.notification_serializers', function () {
            return [
                'discussionRenamed' => BasicDiscussionSerializer::class
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->setNotificationSerializers();

        AbstractSerializeController::setContainer($this->container);

        AbstractSerializer::setContainer($this->container);
    }

    /**
     * Register notification serializers.
     */
    protected function setNotificationSerializers()
    {
        $serializers = $this->container->make('flarum.api.notification_serializers');

        foreach ($serializers as $type => $serializer) {
            NotificationSerializer::setSubjectSerializer($type, $serializer);
        }
    }

    /**
     * Populate the API routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }
}
