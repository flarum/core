<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\SsoProvider;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class DeleteSsoProviderController extends AbstractDeleteController
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(Container $container, SettingsRepositoryInterface $settings)
    {
        $this->container = $container;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    protected function delete(ServerRequestInterface $request)
    {
        $provider = Arr::get($request->getQueryParams(), 'provider');

        $drivers = $this->container->make('flarum.auth.supported_drivers');

        if (! array_key_exists($provider, $drivers)) {
            throw new RouteNotFoundException;
        }

        $actor = $request->getAttribute('actor');

        if ($actor->isGuest()) {
            throw new NotAuthenticatedException;
        }

        if (! in_array($provider, $actor->ssoProviderNames())) {
            throw new RouteNotFoundException;
        }

        if (! $this->settings->get('enable_password_auth', true) && count($actor->ssoProviderNames()) === 1) {
            throw new PermissionDeniedException;
        }

        SsoProvider::where('sso_providers.user_id', $actor->id)->where('provider', $provider)->delete();
    }
}
