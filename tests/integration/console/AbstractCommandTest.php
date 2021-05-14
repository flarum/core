<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\console;

use Flarum\Console\AbstractCommand;
use Flarum\Extend;
use Flarum\Locale\Translator;
use Flarum\Testing\integration\ConsoleTestCase;

class AbstractCommandTest extends ConsoleTestCase
{
    /**
     * @test
     */
    public function scheduled_command_exists_when_added()
    {
        $this->extend(
            (new Extend\Console())
                ->command(CustomEchoTranslationsCommand::class)
        );

        $input = [
            'command' => 'customEchoTranslationsCommand'
        ];

        // Arbitrary translation
        $this->assertEquals('Flarum Email Test', $this->runCommand($input));
    }
}

class CustomEchoTranslationsCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('customEchoTranslationsCommand');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $translator = resolve(Translator::class);

        $this->info($translator->trans('core.emails.send_test.subject'));
    }
}
