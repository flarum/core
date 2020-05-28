<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Database\AbstractModel;

/**
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $identifier
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $last_login_at
 * @property-read User $user
 */
class SsoProvider extends AbstractModel
{
    protected $dates = ['created_at', 'last_login_at'];

    public $timestamps = true;

    const UPDATED_AT = 'last_login_at';

    protected $fillable = ['provider', 'identifier'];

    /**
     * Get the user that the sso provider belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user associated with the provider so that they can be logged in.
     *
     * @param string $provider
     * @param string $identifier
     * @return User|null
     */
    public static function logIn(string $provider, string $identifier): ?User
    {
        if ($provider = static::where(compact('provider', 'identifier'))->first()) {
            $provider->touch();

            return $provider->user;
        }

        return null;
    }
}
