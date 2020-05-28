<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Flarum\Foundation\Application;
use Flarum\Install\Step;
use Flarum\Settings\DatabaseSettingsRepository;
use Illuminate\Database\ConnectionInterface;

class WriteSettings implements Step
{
    /**
     * @var ConnectionInterface
     */
    private $database;

    /**
     * @var array
     */
    private $custom;

    public function __construct(ConnectionInterface $database, array $custom)
    {
        $this->database = $database;
        $this->custom = $custom;
    }

    public function getMessage()
    {
        return 'Writing default settings';
    }

    public function run()
    {
        $repo = new DatabaseSettingsRepository($this->database);

        $repo->set('version', Application::VERSION);

        foreach ($this->getSettings() as $key => $value) {
            $repo->set($key, $value);
        }
    }

    private function getSettings()
    {
        return $this->custom + $this->getDefaults();
    }

    private function getDefaults()
    {
        return [
            'allow_post_editing' => 'reply',
            'allow_renaming' => '10',
            'allow_sign_up' => '1',
            'custom_less' => '',
            'default_locale' => 'en',
            'default_route' => '/all',
            'enable_password_auth' => '1',
            'extensions_enabled' => '[]',
            'forum_title' => 'A new Flarum forum',
            'forum_description' => '',
            'mail_driver' => 'mail',
            'mail_from' => 'noreply@localhost',
            'theme_colored_header' => '0',
            'theme_dark_mode' => '0',
            'theme_primary_color' => '#4D698E',
            'theme_secondary_color' => '#4D698E',
            'welcome_message' => 'This is beta software and you should not use it in production.',
            'welcome_title' => 'Welcome to Flarum',
        ];
    }
}
