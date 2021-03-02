<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Discussion\Access\DiscussionPolicy;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ContainerUtil;
use Flarum\Group\Access\GroupPolicy;
use Flarum\Group\Group;
use Flarum\Http\Access\AccessTokenPolicy;
use Flarum\Http\AccessToken;
use Flarum\Post\Access\PostPolicy;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\ScopeUserVisibility;
use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\DisplayName\UsernameDriver;
use Flarum\User\Event\EmailChangeRequested;
use Flarum\User\Event\Registered;
use Flarum\User\Event\Saving;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Arr;
use League\Flysystem\FilesystemInterface;

class UserServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerAvatarsFilesystem();
        $this->registerDisplayNameDrivers();
        $this->registerPasswordCheckers();

        $this->app->singleton('flarum.user.group_processors', function () {
            return [];
        });

        $this->app->singleton('flarum.policies', function () {
            return [
                Access\AbstractPolicy::GLOBAL => [],
                AccessToken::class => [AccessTokenPolicy::class],
                Discussion::class => [DiscussionPolicy::class],
                Group::class => [GroupPolicy::class],
                Post::class => [PostPolicy::class],
                User::class => [Access\UserPolicy::class],
            ];
        });
    }

    protected function registerDisplayNameDrivers()
    {
        $this->app->singleton('flarum.user.display_name.supported_drivers', function () {
            return [
                'username' => UsernameDriver::class,
            ];
        });

        $this->app->singleton('flarum.user.display_name.driver', function () {
            $drivers = $this->app->make('flarum.user.display_name.supported_drivers');
            $settings = $this->app->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('display_name_driver', '');

            $driverClass = Arr::get($drivers, $driverName);

            return $driverClass
                ? $this->app->make($driverClass)
                : $this->app->make(UsernameDriver::class);
        });

        $this->app->alias('flarum.user.display_name.driver', DriverInterface::class);
    }

    protected function registerAvatarsFilesystem()
    {
        $avatarsFilesystem = function (Container $app) {
            return $app->make(Factory::class)->disk('flarum-avatars')->getDriver();
        };

        $this->app->when(AvatarUploader::class)
            ->needs(FilesystemInterface::class)
            ->give($avatarsFilesystem);
    }

    protected function registerPasswordCheckers()
    {
        $this->app->singleton('flarum.user.password_checkers', function () {
            return [
                'standard' => function (User $user, $password) {
                    if ($this->app->make('hash')->check($password, $user->password)) {
                        return true;
                    }
                }
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        foreach ($this->app->make('flarum.user.group_processors') as $callback) {
            User::addGroupProcessor(ContainerUtil::wrapCallback($callback, $this->app));
        }

        User::setPasswordCheckers($this->app->make('flarum.user.password_checkers'));
        User::setHasher($this->app->make('hash'));
        User::setGate($this->app->makeWith(Access\Gate::class, ['policyClasses' => $this->app->make('flarum.policies')]));
        User::setDisplayNameDriver($this->app->make('flarum.user.display_name.driver'));

        $events = $this->app->make('events');

        $events->listen(Saving::class, SelfDemotionGuard::class);
        $events->listen(Registered::class, AccountActivationMailer::class);
        $events->listen(EmailChangeRequested::class, EmailConfirmationMailer::class);

        $events->subscribe(UserMetadataUpdater::class);

        User::registerPreference('discloseOnline', 'boolval', true);
        User::registerPreference('indexProfile', 'boolval', true);
        User::registerPreference('locale');

        User::registerVisibilityScoper(new ScopeUserVisibility(), 'view');
    }
}
