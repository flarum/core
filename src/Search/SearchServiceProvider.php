<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Discussion\Search\Gambit\AuthorGambit;
use Flarum\Discussion\Search\Gambit\CreatedGambit;
use Flarum\Discussion\Search\Gambit\FulltextGambit as DiscussionFulltextGambit;
use Flarum\Discussion\Search\Gambit\HiddenGambit;
use Flarum\Discussion\Search\Gambit\UnreadGambit;
use Flarum\Discussion\Search\Gambit\UpdatedGambit;
use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Event\ConfigureUserGambits;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\User\Search\Gambit\EmailGambit;
use Flarum\User\Search\Gambit\FulltextGambit as UserFulltextGambit;
use Flarum\User\Search\Gambit\GroupGambit;
use Flarum\User\Search\UserSearcher;
use Illuminate\Contracts\Container\Container;

class SearchServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerDiscussionGambits();

        $this->registerUserGambits();
    }

    public function registerUserGambits()
    {
        $this->app->when(UserSearcher::class)
            ->needs(GambitManager::class)
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);

                $gambits->setFulltextGambit(UserFulltextGambit::class);
                $gambits->add(EmailGambit::class);
                $gambits->add(GroupGambit::class);

                $app->make('events')->dispatch(
                    new ConfigureUserGambits($gambits)
                );

                return $gambits;
            });
    }

    public function registerDiscussionGambits()
    {
        $this->app->when(DiscussionSearcher::class)
            ->needs(GambitManager::class)
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);

                $gambits->setFulltextGambit(DiscussionFulltextGambit::class);
                $gambits->add(AuthorGambit::class);
                $gambits->add(CreatedGambit::class);
                $gambits->add(HiddenGambit::class);
                $gambits->add(UnreadGambit::class);
                $gambits->add(UpdatedGambit::class);

                $app->make('events')->dispatch(
                    new ConfigureDiscussionGambits($gambits)
                );

                return $gambits;
            });
    }
}
