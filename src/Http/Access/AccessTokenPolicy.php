<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Access;

use Flarum\Http\AccessToken;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class AccessTokenPolicy extends AbstractPolicy
{
    public function delete(User $actor, AccessToken $token)
    {
        return $token->user_id === $actor->id;
    }
}
