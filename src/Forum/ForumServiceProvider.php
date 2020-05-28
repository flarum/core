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
use Symfony\Component\Translation\TranslatorInterface;

class ForumServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->extend(UrlGenerator::class, function (UrlGenerator $url) {
            return $url->addCollection('forum', $this->app->make('flarum.forum.routes'));
        });

        $this->app->singleton('flarum.forum.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->app->afterResolving('flarum.forum.routes', function (RouteCollection $routes) {
            $this->setDefaultRoute($routes);
        });

        $this->app->singleton('flarum.forum.middleware', function () {
            return [
                'flarum.forum.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\CollectGarbage::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\SetLocale::class,
                HttpMiddleware\ShareErrorsFromSession::class
            ];
        });

        $this->app->bind('flarum.forum.error_handler', function () {
            return new HttpMiddleware\HandleErrors(
                $this->app->make(Registry::class),
                $this->app['flarum']->inDebugMode() ? $this->app->make(WhoopsFormatter::class) : $this->app->make(ViewFormatter::class),
                $this->app->tagged(Reporter::class)
            );
        });

        $this->app->singleton('flarum.forum.handler', function () {
            $pipe = new MiddlewarePipe;

            foreach ($this->app->make('flarum.forum.middleware') as $middleware) {
                $pipe->pipe($this->app->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\DispatchRoute($this->app->make('flarum.forum.routes')));

            return $pipe;
        });

        $this->app->bind('flarum.assets.forum', function () {
            /** @var Assets $assets */
            $assets = $this->app->make('flarum.assets.factory')('forum');

            $assets->js(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../js/dist/forum.js');
                $sources->addString(function () {
                    return $this->app->make(Formatter::class)->getJs();
                });
            });

            $assets->css(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../less/forum.less');
                $sources->addString(function () {
                    return $this->app->make(SettingsRepositoryInterface::class)->get('custom_less', '');
                });
            });

            $this->app->make(AddTranslations::class)->forFrontend('forum')->to($assets);
            $this->app->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->app->bind('flarum.frontend.forum', function () {
            return $this->app->make('flarum.frontend.factory')('forum');
        });

        $this->app->singleton('flarum.auth.supported_drivers', function () {
            return [];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum.forum');

        $this->app->make('view')->share([
            'translator' => $this->app->make(TranslatorInterface::class),
            'settings' => $this->app->make(SettingsRepositoryInterface::class)
        ]);

        $events = $this->app->make('events');

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () {
                $recompile = new RecompileFrontendAssets(
                    $this->app->make('flarum.assets.forum'),
                    $this->app->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) {
                $recompile = new RecompileFrontendAssets(
                    $this->app->make('flarum.assets.forum'),
                    $this->app->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);

                $validator = new ValidateCustomLess(
                    $this->app->make('flarum.assets.forum'),
                    $this->app->make('flarum.locales'),
                    $this->app
                );
                $validator->whenSettingsSaved($event);
            }
        );

        $events->listen(
            Saving::class,
            function (Saving $event) {
                $validator = new ValidateCustomLess(
                    $this->app->make('flarum.assets.forum'),
                    $this->app->make('flarum.locales'),
                    $this->app
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
        $factory = $this->app->make(RouteHandlerFactory::class);

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
        $factory = $this->app->make(RouteHandlerFactory::class);
        $defaultRoute = $this->app->make('flarum.settings')->get('default_route');

        if (isset($routes->getRouteData()[0]['GET'][$defaultRoute])) {
            $toDefaultController = $routes->getRouteData()[0]['GET'][$defaultRoute];
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
