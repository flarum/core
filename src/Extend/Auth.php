<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Auth implements ExtenderInterface
{
    protected $ssoDrivers = [];

    /**
     * @param string $provider URL-friendly, lowercase identifier (ex. 'github', 'saml', 'google', 'facebook', 'wechat')
     * @param $driver Class attribute of driver that implements Flarum\Forum\Auth\SsoDriverInterface
     */
    public function ssoDriver($provider, $driver)
    {
        $this->ssoDrivers[$provider] = $driver;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.auth.supported_drivers', function ($existingMiddleware) {
            return array_merge($existingMiddleware, $this->ssoDrivers);
        });
    }
}
