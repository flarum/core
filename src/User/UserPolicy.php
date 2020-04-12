<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Illuminate\Database\Eloquent\Builder;

class UserPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = User::class;

    /**
     * @param User $actor
     * @param string $ability
     * @param User $model
     * @return bool|null
     */
    public function can(User $actor, $ability, User $model)
    {
        if (strpos($ability, 'user.edit') === 0) {
            if ($model->isAdmin() && ! $actor->isAdmin()) {
                return false;
            }

            return $actor->hasPermission($ability);
        }
        if ($actor->hasPermission('user.'.$ability)) {
            return true;
        }
    }

    /**
     * @param User $actor
     * @param Builder $query
     */
    public function find(User $actor, Builder $query)
    {
        if ($actor->cannot('viewUserList')) {
            if ($actor->isGuest()) {
                $query->whereRaw('FALSE');
            } else {
                $query->where('id', $actor->id);
            }
        }
    }
}
