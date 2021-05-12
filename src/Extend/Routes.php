<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class Routes implements ExtenderInterface
{
    private $appName;

    private $routes = [];
    private $removedRoutes = [];

    /**
     * @param string $appName: Name of the app (api, forum, admin).
     */
    public function __construct($appName)
    {
        $this->appName = $appName;
    }

    /**
     * Add a GET route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|string $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should implement \Psr\Http\Server\RequestHandlerInterface,
     * or extend one of the Flarum Api controllers:
     * - \Flarum\Api\Controller\AbstractSerializeController
     * - \Flarum\Api\Controller\AbstractShowController
     * - \Flarum\Api\Controller\AbstractCreateController
     * - \Flarum\Api\Controller\AbstractListController
     * - \Flarum\Api\Controller\AbstractDeleteController
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Flarum Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function get($path, $name, $handler)
    {
        return $this->route('GET', $path, $name, $handler);
    }

    /**
     * Add a POST route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|string $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should implement \Psr\Http\Server\RequestHandlerInterface,
     * or extend one of the Flarum Api controllers:
     * - \Flarum\Api\Controller\AbstractSerializeController
     * - \Flarum\Api\Controller\AbstractShowController
     * - \Flarum\Api\Controller\AbstractCreateController
     * - \Flarum\Api\Controller\AbstractListController
     * - \Flarum\Api\Controller\AbstractDeleteController
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Flarum Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function post($path, $name, $handler)
    {
        return $this->route('POST', $path, $name, $handler);
    }

    /**
     * Add a PUT route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|string $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should implement \Psr\Http\Server\RequestHandlerInterface,
     * or extend one of the Flarum Api controllers:
     * - \Flarum\Api\Controller\AbstractSerializeController
     * - \Flarum\Api\Controller\AbstractShowController
     * - \Flarum\Api\Controller\AbstractCreateController
     * - \Flarum\Api\Controller\AbstractListController
     * - \Flarum\Api\Controller\AbstractDeleteController
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Flarum Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function put($path, $name, $handler)
    {
        return $this->route('PUT', $path, $name, $handler);
    }

    /**
     * Add a PATCH route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|string $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should implement \Psr\Http\Server\RequestHandlerInterface,
     * or extend one of the Flarum Api controllers:
     * - \Flarum\Api\Controller\AbstractSerializeController
     * - \Flarum\Api\Controller\AbstractShowController
     * - \Flarum\Api\Controller\AbstractCreateController
     * - \Flarum\Api\Controller\AbstractListController
     * - \Flarum\Api\Controller\AbstractDeleteController
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Flarum Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function patch($path, $name, $handler)
    {
        return $this->route('PATCH', $path, $name, $handler);
    }

    /**
     * Add a DELETE route.
     *
     * @param string $path: The path of the route
     * @param string $name: The name of the route, must be unique.
     * @param callable|string $handler: ::class attribute of the controller class, or a closure.
     *
     * If the handler is a controller class, it should implement \Psr\Http\Server\RequestHandlerInterface,
     * or extend one of the Flarum Api controllers:
     * - \Flarum\Api\Controller\AbstractSerializeController
     * - \Flarum\Api\Controller\AbstractShowController
     * - \Flarum\Api\Controller\AbstractCreateController
     * - \Flarum\Api\Controller\AbstractListController
     * - \Flarum\Api\Controller\AbstractDeleteController
     *
     * The handler should accept:
     * - \Psr\Http\Message\ServerRequestInterface $request
     * - \Tobscure\JsonApi\Document $document: If it extends one of the Flarum Api controllers.
     *
     * The handler should return:
     * - \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public function delete($path, $name, $handler)
    {
        return $this->route('DELETE', $path, $name, $handler);
    }

    private function route($httpMethod, $path, $name, $handler)
    {
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $path,
            'name' => $name,
            'handler' => $handler
        ];

        return $this;
    }

    /**
     * Remove an existing route.
     * Necessary before overriding a route.
     *
     * @param string $name: The name of the route.
     * @return self
     */
    public function remove(string $name)
    {
        $this->removedRoutes[] = $name;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (empty($this->routes) && empty($this->removedRoutes)) {
            return;
        }

        $container->resolving(
            "flarum.{$this->appName}.routes",
            function (RouteCollection $collection, Container $container) {
                /** @var RouteHandlerFactory $factory */
                $factory = $container->make(RouteHandlerFactory::class);

                foreach ($this->removedRoutes as $routeName) {
                    $collection->removeRoute($routeName);
                }

                foreach ($this->routes as $route) {
                    $collection->addRoute(
                        $route['method'],
                        $route['path'],
                        $route['name'],
                        $factory->toController($route['handler'])
                    );
                }
            }
        );
    }
}
