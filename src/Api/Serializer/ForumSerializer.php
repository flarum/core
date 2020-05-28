<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Foundation\Application;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;

class ForumSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'forums';

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param Application $app
     * @param SettingsRepositoryInterface $settings
     * @param UrlGenerator $url
     */
    public function __construct(Application $app, SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        $this->app = $app;
        $this->settings = $settings;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getId($model)
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($model)
    {
        $ssoProviders = array_filter($this->app->make('flarum.auth.supported_drivers'), function ($provider) {
            return $this->settings->get('auth_driver_enabled_'.$provider);
        }, ARRAY_FILTER_USE_KEY);

        $ssoProviders = array_map(function ($provider) {
            return $this->app->make($provider)->meta();
        }, $ssoProviders);

        $attributes = [
            'title' => $this->settings->get('forum_title'),
            'description' => $this->settings->get('forum_description'),
            'showLanguageSelector' => (bool) $this->settings->get('show_language_selector', true),
            'baseUrl' => $url = $this->url->to('forum')->base(),
            'basePath' => parse_url($url, PHP_URL_PATH) ?: '',
            'debug' => $this->app->inDebugMode(),
            'apiUrl' => $this->url->to('api')->base(),
            'welcomeTitle' => $this->settings->get('welcome_title'),
            'welcomeMessage' => $this->settings->get('welcome_message'),
            'themePrimaryColor' => $this->settings->get('theme_primary_color'),
            'themeSecondaryColor' => $this->settings->get('theme_secondary_color'),
            'logoUrl' => $this->getLogoUrl(),
            'faviconUrl' => $this->getFaviconUrl(),
            'headerHtml' => $this->settings->get('custom_header'),
            'footerHtml' => $this->settings->get('custom_footer'),
            'allowSignUp' => (bool) $this->settings->get('allow_sign_up'),
            'enablePasswordAuth' => (bool) $this->settings->get('enable_password_auth', true),
            'defaultRoute'  => $this->settings->get('default_route'),
            'canViewDiscussions' => $this->actor->can('viewDiscussions'),
            'canStartDiscussion' => $this->actor->can('startDiscussion'),
            'canViewUserList' => $this->actor->can('viewUserList'),
            'ssoProviders' => $ssoProviders,
        ];

        if ($this->actor->can('administrate')) {
            $attributes['adminUrl'] = $this->url->to('admin')->base();
            $attributes['version'] = Application::VERSION;
        }

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function groups($model)
    {
        return $this->hasMany($model, GroupSerializer::class);
    }

    /**
     * @return null|string
     */
    protected function getLogoUrl()
    {
        $logoPath = $this->settings->get('logo_path');

        return $logoPath ? $this->url->to('forum')->path('assets/'.$logoPath) : null;
    }

    /**
     * @return null|string
     */
    protected function getFaviconUrl()
    {
        $faviconPath = $this->settings->get('favicon_path');

        return $faviconPath ? $this->url->to('forum')->path('assets/'.$faviconPath) : null;
    }
}
