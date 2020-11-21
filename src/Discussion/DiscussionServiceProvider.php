<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Discussion\Access\ScopeDiscussionVisibility;
use Flarum\Discussion\Event\Renamed;
use Flarum\Foundation\AbstractServiceProvider;

class DiscussionServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $events = $this->app->make('events');

        $events->subscribe(DiscussionMetadataUpdater::class);
        $events->subscribe(DiscussionPolicy::class);

        $events->listen(
            Renamed::class,
            DiscussionRenamedLogger::class
        );

        Discussion::registerVisibilityScoper(Discussion::class, new ScopeDiscussionVisibility(), 'view');
    }
}
