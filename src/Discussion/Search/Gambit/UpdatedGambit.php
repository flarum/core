<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search\Gambit;

use Flarum\Discussion\Search\DiscussionSearch;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use LogicException;

class UpdatedGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'updated:(\d{4}\-\d\d\-\d\d)(\.\.(\d{4}\-\d\d\-\d\d))?';

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        // If we've just been provided with a single YYYY-MM-DD date, then find
        // discussions that were started on that exact date. But if we've been
        // provided with a YYYY-MM-DD..YYYY-MM-DD range, then find discussions
        // that were last updated during that period.
        if (empty($matches[3])) {
            $search->getQuery()->whereDate('last_posted_at', $negate ? '!=' : '=', $matches[1]);
        } else {
            $search->getQuery()->whereBetween('last_posted_at', [$matches[1], $matches[3]], 'and', $negate);
        }
    }
}
