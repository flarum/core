<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Formatter\Formatter;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ErrorHandling\FrontendFormatter;
use Flarum\Foundation\ErrorHandling\Middleware\ExecuteErrorToFrontend;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Foundation\ErrorHandling\ViewFormatter;
use Flarum\Foundation\ErrorHandling\WhoopsFormatter;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Frontend\AddLocaleAssets;
use Flarum\Frontend\AddTranslations;
use Flarum\Frontend\Assets;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Stratigility\MiddlewarePipe;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ForumServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url) {
            return $url->addCollection('forum', $this->container->make('flarum.forum.routes'));
        });

        $this->container->singleton('flarum.forum.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->container->afterResolving('flarum.forum.routes', function (RouteCollection $routes) {
            $this->setDefaultRoute($routes);
        });

        $this->container->singleton('flarum.forum.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'flarum.forum.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\CollectGarbage::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\SetLocale::class,
                'flarum.forum.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\ShareErrorsFromSession::class,
                HttpMiddleware\FlarumPromotionHeader::class,
            ];
        });

        $this->container->bind('flarum.forum.error_handler', function () {
            return new HttpMiddleware\HandleErrors(
                $this->container->make(Registry::class),
                $this->container['flarum.config']->inDebugMode() ? $this->container->make(WhoopsFormatter::class) : $this->container->make(ViewFormatter::class),
                $this->container->make(FrontendFormatter::class),
                $this->container->tagged(Reporter::class)
            );
        });

        $this->container->bind('flarum.forum.route_resolver', function () {
            return new HttpMiddleware\ResolveRoute($this->container->make('flarum.forum.routes'));
        });

        $this->container->singleton('flarum.forum.handler', function () {
            $pipe = new MiddlewarePipe;

            foreach ($this->container->make('flarum.forum.middleware') as $middleware) {
                $pipe->pipe($this->container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->bind('flarum.assets.forum', function () {
            /** @var Assets $assets */
            $assets = $this->container->make('flarum.assets.factory')('forum');

            $assets->js(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../js/dist/forum.js');
                $sources->addString(function () {
                    return $this->container->make(Formatter::class)->getJs();
                });
            });

            $assets->css(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../less/forum.less');
                $sources->addString(function () {
                    return $this->container->make(SettingsRepositoryInterface::class)->get('custom_less', '');
                });
            });

            $this->container->make(AddTranslations::class)->forFrontend('forum')->to($assets);
            $this->container->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->container->bind('flarum.frontend.forum', function () {
            return $this->container->make('flarum.frontend.factory')('forum');
        });

        $this->container->when(FrontendFormatter::class)
        ->needs(MiddlewarePipeInterface::class)
        ->give(function () {
            $middleware = $this->container->make('flarum.forum.middleware');

            $pipe = new MiddlewarePipe;

            foreach ($middleware as $middlewareClass) {
                if (! in_array($middlewareClass, [
                    'flarum.forum.error_handler',
                    HttpMiddleware\InjectActorReference::class,
                    HttpMiddleware\RememberFromCookie::class,
                    HttpMiddleware\AuthenticateWithSession::class,
                    'flarum.forum.route_resolver',
                ])) {
                    $pipe->pipe($this->container->make($middlewareClass));
                }
            }

            $pipe->pipe(new ExecuteErrorToFrontend('forum', $this->container->make(RouteHandlerFactory::class)));

            return $pipe;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.forum');

        $this->container->make('view')->share([
            'translator' => $this->container->make(TranslatorInterface::class),
            'settings' => $this->container->make(SettingsRepositoryInterface::class)
        ]);

        $events = $this->container->make('events');

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () {
                $recompile = new RecompileFrontendAssets(
                    $this->container->make('flarum.assets.forum'),
                    $this->container->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) {
                $recompile = new RecompileFrontendAssets(
                    $this->container->make('flarum.assets.forum'),
                    $this->container->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);

                $validator = new ValidateCustomLess(
                    $this->container->make('flarum.assets.forum'),
                    $this->container->make('flarum.locales'),
                    $this->container
                );
                $validator->whenSettingsSaved($event);
            }
        );

        $events->listen(
            Saving::class,
            function (Saving $event) {
                $validator = new ValidateCustomLess(
                    $this->container->make('flarum.assets.forum'),
                    $this->container->make('flarum.locales'),
                    $this->container
                );
                $validator->whenSettingsSaving($event);
            }
        );
    }

    /**
     * Populate the forum client routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }

    /**
     * Determine the default route.
     *
     * @param RouteCollection $routes
     */
    protected function setDefaultRoute(RouteCollection $routes)
    {
        $factory = $this->container->make(RouteHandlerFactory::class);
        $defaultRoute = $this->container->make('flarum.settings')->get('default_route');

        if (isset($routes->getRouteData()[0]['GET'][$defaultRoute]['handler'])) {
            $toDefaultController = $routes->getRouteData()[0]['GET'][$defaultRoute]['handler'];
        } else {
            $toDefaultController = $factory->toForum(Content\Index::class);
        }

        $routes->get(
            '/',
            'default',
            $toDefaultController
        );
    }
}
