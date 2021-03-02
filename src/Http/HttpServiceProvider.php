<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\IdWithTransliteratedSlugDriver;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\Access\AccessTokenVisibility;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Flarum\User\UsernameSlugDriver;
use Illuminate\Support\Arr;

class HttpServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.http.csrfExemptPaths', function () {
            return ['token'];
        });

        $this->app->bind(Middleware\CheckCsrfToken::class, function ($app) {
            return new Middleware\CheckCsrfToken($app->make('flarum.http.csrfExemptPaths'));
        });

        $this->app->singleton('flarum.http.slugDrivers', function () {
            return [
                Discussion::class => [
                    'default' => IdWithTransliteratedSlugDriver::class
                ],
                User::class => [
                    'default' => UsernameSlugDriver::class
                ],
            ];
        });

        $this->app->singleton('flarum.http.selectedSlugDrivers', function () {
            $settings = $this->app->make(SettingsRepositoryInterface::class);

            $compiledDrivers = [];

            foreach ($this->app->make('flarum.http.slugDrivers') as $resourceClass => $resourceDrivers) {
                $driverKey = $settings->get("slug_driver_$resourceClass", 'default');

                $driverClass = Arr::get($resourceDrivers, $driverKey, $resourceDrivers['default']);

                $compiledDrivers[$resourceClass] = $this->app->make($driverClass);
            }

            return $compiledDrivers;
        });
        $this->app->bind(SlugManager::class, function () {
            return new SlugManager($this->app->make('flarum.http.selectedSlugDrivers'));
        });

        AccessToken::registerVisibilityScoper(new AccessTokenVisibility(), 'view');
    }
}
