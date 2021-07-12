<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use FastRoute\DataGenerator;
use FastRoute\RouteParser;
use Illuminate\Support\Arr;

/**
 * @internal
 */
class RouteCollection
{
    /**
     * @var array
     */
    protected $reverse = [];

    /**
     * @var DataGenerator
     */
    protected $dataGenerator;

    /**
     * @var RouteParser
     */
    protected $routeParser;

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var array
     */
    protected $pendingRoutes = [];

    public function __construct()
    {
        $this->dataGenerator = new DataGenerator\GroupCountBased;
        $this->routeParser = new RouteParser\Std;
    }

    public function get($path, $name, $handler)
    {
        return $this->addRoute('GET', $path, $name, $handler);
    }

    public function post($path, $name, $handler)
    {
        return $this->addRoute('POST', $path, $name, $handler);
    }

    public function put($path, $name, $handler)
    {
        return $this->addRoute('PUT', $path, $name, $handler);
    }

    public function patch($path, $name, $handler)
    {
        return $this->addRoute('PATCH', $path, $name, $handler);
    }

    public function delete($path, $name, $handler)
    {
        return $this->addRoute('DELETE', $path, $name, $handler);
    }

    public function addRoute($method, $path, $name, $handler)
    {
        if (isset($this->routes[$name])) {
            throw new \RuntimeException("Route $name already exists");
        }

        $this->routes[$name] = $this->pendingRoutes[$name] = compact('method', 'path', 'handler');

        return $this;
    }

    public function removeRoute(string $name): self
    {
        unset($this->routes[$name], $this->pendingRoutes[$name]);

        return $this;
    }

    protected function applyRoutes(): void
    {
        foreach ($this->pendingRoutes as $name => $route) {
            $routeDatas = $this->routeParser->parse($route['path']);

            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRoute($route['method'], $routeData, ['name' => $name, 'handler' => $route['handler']]);
            }

            $this->reverse[$name] = $routeDatas;
        }

        $this->pendingRoutes = [];
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getRouteData()
    {
        if (! empty($this->pendingRoutes)) {
            $this->applyRoutes();
        }

        return $this->dataGenerator->getData();
    }

    protected function fixPathPart(&$part, $key, array $parameters)
    {
        // This assumes that $parameters always has the required param
        // the route needs. This isn't always the case.
        if (is_array($part) && array_key_exists($part[0], $parameters)) {
            $part = $parameters[$part[0]];
        } elseif (is_array($part) && ! array_key_exists($part[0], $parameters)) {
            throw new \InvalidArgumentException("Route is missing argument for part $part[0].");
        }
    }

    public function getPath($name, array $parameters = [])
    {
        if (! empty($this->pendingRoutes)) {
            $this->applyRoutes();
        }

        if (isset($this->reverse[$name])) {
            $maxMatches = 0;
            $matchingParts = $this->reverse[$name][0];

            // For a given route name, we want to choose the option that best matches the given parameters.
            // Each routing option is an array of parts. Each part is either a constant string
            // (which we don't care about here), or an array where the first element is the parameter name
            // and the second element is a regex into which the parameter value is inserted, if the parameter matches.
            foreach ($this->reverse[$name] as $parts) {
                foreach ($parts as $i => $part) {
                    if (is_array($part) && Arr::exists($parameters, $part[0]) && $i > $maxMatches) {
                        $maxMatches = $i;
                        $matchingParts = $parts;
                    }
                }
            }

            array_walk($matchingParts, [$this, 'fixPathPart'], $parameters);

            return '/'.ltrim(implode('', $matchingParts), '/');
        }

        throw new \RuntimeException("Route $name not found");
    }
}
