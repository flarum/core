<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Frontend\Assets;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\Frontend as ActualFrontend;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Illuminate\Contracts\Container\Container;

class Frontend implements ExtenderInterface
{
    private $frontend;

    private $css = [];
    private $js;
    private $routes = [];
    private $removedRoutes = [];
    private $content = [];

    public function __construct(string $frontend)
    {
        $this->frontend = $frontend;
    }

    public function css(string $path)
    {
        $this->css[] = $path;

        return $this;
    }

    public function js(string $path)
    {
        $this->js = $path;

        return $this;
    }

    public function route(string $path, string $name, $content = null)
    {
        $this->routes[] = compact('path', 'name', 'content');

        return $this;
    }

    public function removeRoute(string $name)
    {
        $this->removedRoutes[] = $name;

        return $this;
    }

    /**
     * @param callable|string $callback
     * @return $this
     */
    public function content($callback)
    {
        $this->content[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $this->registerAssets($container, $this->getModuleName($extension));
        $this->registerRoutes($container);
        $this->registerContent($container);
    }

    private function registerAssets(Container $container, string $moduleName)
    {
        if (empty($this->css) && empty($this->js)) {
            return;
        }

        $abstract = 'flarum.assets.'.$this->frontend;

        $container->resolving($abstract, function (Assets $assets) use ($moduleName) {
            if ($this->js) {
                $assets->js(function (SourceCollector $sources) use ($moduleName) {
                    $sources->addString(function () {
                        return 'var module={};';
                    });
                    $sources->addFile($this->js);
                    $sources->addString(function () use ($moduleName) {
                        return "flarum.extensions['$moduleName']=module.exports;";
                    });
                });
            }

            if ($this->css) {
                $assets->css(function (SourceCollector $sources) {
                    foreach ($this->css as $path) {
                        $sources->addFile($path);
                    }
                });
            }
        });

        if (! $container->bound($abstract)) {
            $container->bind($abstract, function (Container $container) {
                return $container->make('flarum.assets.factory')($this->frontend);
            });

            /** @var \Illuminate\Contracts\Events\Dispatcher $events */
            $events = $container->make('events');

            $events->listen(
                [Enabled::class, Disabled::class, ClearingCache::class],
                function () use ($container, $abstract) {
                    $recompile = new RecompileFrontendAssets(
                        $container->make($abstract),
                        $container->make(LocaleManager::class)
                    );
                    $recompile->flush();
                }
            );

            $events->listen(
                Saved::class,
                function (Saved $event) use ($container, $abstract) {
                    $recompile = new RecompileFrontendAssets(
                        $container->make($abstract),
                        $container->make(LocaleManager::class)
                    );
                    $recompile->whenSettingsSaved($event);
                }
            );
        }
    }

    private function registerRoutes(Container $container)
    {
        if (empty($this->routes) && empty($this->removedRoutes)) {
            return;
        }

        $container->resolving(
            "flarum.{$this->frontend}.routes",
            function (RouteCollection $collection, Container $container) {
                /** @var RouteHandlerFactory $factory */
                $factory = $container->make(RouteHandlerFactory::class);

                foreach ($this->removedRoutes as $routeName) {
                    $collection->removeRoute($routeName);
                }

                foreach ($this->routes as $route) {
                    $collection->get(
                        $route['path'],
                        $route['name'],
                        $factory->toFrontend($this->frontend, $route['content'])
                    );
                }
            }
        );
    }

    private function registerContent(Container $container)
    {
        if (empty($this->content)) {
            return;
        }

        $container->resolving(
            "flarum.frontend.$this->frontend",
            function (ActualFrontend $frontend, Container $container) {
                foreach ($this->content as $content) {
                    $frontend->content(ContainerUtil::wrapCallback($content, $container));
                }
            }
        );
    }

    private function getModuleName(?Extension $extension): string
    {
        return $extension ? $extension->getId() : 'site-custom';
    }
}
