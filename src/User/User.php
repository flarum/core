<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Carbon\Carbon;
use DomainException;
use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Discussion\Discussion;
use Flarum\Event\ConfigureUserPreferences;
use Flarum\Event\PrepareUserGroups;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\Group\Group;
use Flarum\Group\Permission;
use Flarum\Http\AccessToken;
use Flarum\Http\UrlGenerator;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\Event\Activated;
use Flarum\User\Event\AvatarChanged;
use Flarum\User\Event\CheckingPassword;
use Flarum\User\Event\Deleted;
use Flarum\User\Event\EmailChanged;
use Flarum\User\Event\EmailChangeRequested;
use Flarum\User\Event\GetDisplayName;
use Flarum\User\Event\PasswordChanged;
use Flarum\User\Event\Registered;
use Flarum\User\Event\Renamed;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Arr;

/**
 * @property int $id
 * @property string $username
 * @property string $display_name
 * @property string $email
 * @property bool $is_email_confirmed
 * @property string $password
 * @property string|null $avatar_url
 * @property array $preferences
 * @property \Carbon\Carbon|null $joined_at
 * @property \Carbon\Carbon|null $last_seen_at
 * @property \Carbon\Carbon|null $marked_all_as_read_at
 * @property \Carbon\Carbon|null $read_notifications_at
 * @property int $discussion_count
 * @property int $comment_count
 */
class User extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'joined_at',
        'last_seen_at',
        'marked_all_as_read_at',
        'read_notifications_at'
    ];

    /**
     * An array of permissions that this user has.
     *
     * @var string[]|null
     */
    protected $permissions = null;

    /**
     * @var Session
     */
    protected $session;

    /**
     * An array of registered user preferences. Each preference is defined with
     * a key, and its value is an array containing the following keys:.
     *
     * - transformer: a callback that confines the value of the preference
     * - default: a default value if the preference isn't set
     *
     * @var array
     */
    protected static $preferences = [];

    /**
     * A driver for getting display names.
     *
     * @var DriverInterface
     */
    protected static $displayNameDriver;

    /**
     * The hasher with which to hash passwords.
     *
     * @var Hasher
     */
    protected static $hasher;

    /**
     * The access gate.
     *
     * @var Gate
     */
    protected static $gate;

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        // Don't allow the root admin to be deleted.
        static::deleting(function (self $user) {
            if ($user->id == 1) {
                throw new DomainException('Cannot delete the root admin');
            }
        });

        static::deleted(function (self $user) {
            $user->raise(new Deleted($user));

            Notification::whereSubject($user)->delete();
        });

        static::$dispatcher->dispatch(
            new ConfigureUserPreferences
        );
    }

    /**
     * Register a new user.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return static
     */
    public static function register($username, $email, $password)
    {
        $user = new static;

        $user->username = $username;
        $user->email = $email;
        $user->password = $password;
        $user->joined_at = Carbon::now();

        $user->raise(new Registered($user));

        return $user;
    }

    /**
     * @return Gate
     */
    public static function getGate()
    {
        return static::$gate;
    }

    /**
     * @param Gate $gate
     */
    public static function setGate($gate)
    {
        static::$gate = $gate;
    }

    /**
     * Set the display name driver.
     *
     * @param DriverInterface $driver
     */
    public static function setDisplayNameDriver(DriverInterface $driver)
    {
        static::$displayNameDriver = $driver;
    }

    /**
     * Rename the user.
     *
     * @param string $username
     * @return $this
     */
    public function rename($username)
    {
        if ($username !== $this->username) {
            $oldUsername = $this->username;
            $this->username = $username;

            $this->raise(new Renamed($this, $oldUsername));
        }

        return $this;
    }

    /**
     * Change the user's email.
     *
     * @param string $email
     * @return $this
     */
    public function changeEmail($email)
    {
        if ($email !== $this->email) {
            $this->email = $email;

            $this->raise(new EmailChanged($this));
        }

        return $this;
    }

    /**
     * Request that the user's email be changed.
     *
     * @param string $email
     * @return $this
     */
    public function requestEmailChange($email)
    {
        if ($email !== $this->email) {
            $this->raise(new EmailChangeRequested($this, $email));
        }

        return $this;
    }

    /**
     * Change the user's password.
     *
     * @param string $password
     * @return $this
     */
    public function changePassword($password)
    {
        $this->password = $password;

        $this->raise(new PasswordChanged($this));

        return $this;
    }

    /**
     * Set the password attribute, storing it as a hash.
     *
     * @param string $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? static::$hasher->make($value) : '';
    }

    /**
     * Mark all discussions as read.
     *
     * @return $this
     */
    public function markAllAsRead()
    {
        $this->marked_all_as_read_at = Carbon::now();

        return $this;
    }

    /**
     * Mark all notifications as read.
     *
     * @return $this
     */
    public function markNotificationsAsRead()
    {
        $this->read_notifications_at = Carbon::now();

        return $this;
    }

    /**
     * Change the path of the user avatar.
     *
     * @param string $path
     * @return $this
     */
    public function changeAvatarPath($path)
    {
        $this->avatar_url = $path;

        $this->raise(new AvatarChanged($this));

        return $this;
    }

    /**
     * Get the URL of the user's avatar.
     *
     * @todo Allow different storage locations to be used
     * @param string|null $value
     * @return string
     */
    public function getAvatarUrlAttribute(string $value = null)
    {
        if ($value && strpos($value, '://') === false) {
            return app(UrlGenerator::class)->to('forum')->path('assets/avatars/'.$value);
        }

        return $value;
    }

    /**
     * Get the user's display name.
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        // Event is deprecated in beta 14, remove in beta 15.
        return static::$dispatcher->until(new GetDisplayName($this)) ?: static::$displayNameDriver->displayName($this);
    }

    /**
     * Check if a given password matches the user's password.
     *
     * @param string $password
     * @return bool
     */
    public function checkPassword($password)
    {
        $valid = static::$dispatcher->until(new CheckingPassword($this, $password));

        if ($valid !== null) {
            return $valid;
        }

        return static::$hasher->check($password, $this->password);
    }

    /**
     * Activate the user's account.
     *
     * @return $this
     */
    public function activate()
    {
        if ($this->is_email_confirmed !== true) {
            $this->is_email_confirmed = true;

            $this->raise(new Activated($this));
        }

        return $this;
    }

    /**
     * Check whether the user has a certain permission based on their groups.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (is_null($this->permissions)) {
            $this->permissions = $this->getPermissions();
        }

        return in_array($permission, $this->permissions);
    }

    /**
     * Check whether the user has a permission that is like the given string,
     * based on their groups.
     *
     * @param string $match
     * @return bool
     */
    public function hasPermissionLike($match)
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (is_null($this->permissions)) {
            $this->permissions = $this->getPermissions();
        }

        foreach ($this->permissions as $permission) {
            if (substr($permission, -strlen($match)) === $match) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the notification types that should be alerted to this user, according
     * to their preferences.
     *
     * @return array
     */
    public function getAlertableNotificationTypes()
    {
        $types = array_keys(Notification::getSubjectModels());

        return array_filter($types, [$this, 'shouldAlert']);
    }

    /**
     * Get the number of unread notifications for the user.
     *
     * @return int
     */
    public function getUnreadNotificationCount()
    {
        return $this->getUnreadNotifications()->count();
    }

    /**
     * Get all notifications that have not been read yet.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getUnreadNotifications()
    {
        static $cached = null;

        if (is_null($cached)) {
            $cached = $this->notifications()
                ->whereIn('type', $this->getAlertableNotificationTypes())
                ->whereNull('read_at')
                ->where('is_deleted', false)
                ->whereSubjectVisibleTo($this)
                ->get();
        }

        return $cached;
    }

    /**
     * Get the number of new, unseen notifications for the user.
     *
     * @return int
     */
    public function getNewNotificationCount()
    {
        return $this->getUnreadNotifications()->filter(function ($notification) {
            return $notification->created_at > $this->read_notifications_at ?: 0;
        })->count();
    }

    /**
     * Get the values of all registered preferences for this user, by
     * transforming their stored preferences and merging them with the defaults.
     *
     * @param string $value
     * @return array
     */
    public function getPreferencesAttribute($value)
    {
        $defaults = array_map(function ($value) {
            return $value['default'];
        }, static::$preferences);

        $user = Arr::only((array) json_decode($value, true), array_keys(static::$preferences));

        return array_merge($defaults, $user);
    }

    /**
     * Encode an array of preferences for storage in the database.
     *
     * @param mixed $value
     */
    public function setPreferencesAttribute($value)
    {
        $this->attributes['preferences'] = json_encode($value);
    }

    /**
     * Check whether or not the user should receive an alert for a notification
     * type.
     *
     * @param string $type
     * @return bool
     */
    public function shouldAlert($type)
    {
        return (bool) $this->getPreference(static::getNotificationPreferenceKey($type, 'alert'));
    }

    /**
     * Check whether or not the user should receive an email for a notification
     * type.
     *
     * @param string $type
     * @return bool
     */
    public function shouldEmail($type)
    {
        return (bool) $this->getPreference(static::getNotificationPreferenceKey($type, 'email'));
    }

    /**
     * Get the value of a preference for this user.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreference($key, $default = null)
    {
        return Arr::get($this->preferences, $key, $default);
    }

    /**
     * Set the value of a preference for this user.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setPreference($key, $value)
    {
        if (isset(static::$preferences[$key])) {
            $preferences = $this->preferences;

            if (! is_null($transformer = static::$preferences[$key]['transformer'])) {
                $preferences[$key] = call_user_func($transformer, $value);
            } else {
                $preferences[$key] = $value;
            }

            $this->preferences = $preferences;
        }

        return $this;
    }

    /**
     * Set the user as being last seen just now.
     *
     * @return $this
     */
    public function updateLastSeen()
    {
        $this->last_seen_at = Carbon::now();

        return $this;
    }

    /**
     * Check whether or not the user is an administrator.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->groups->contains(Group::ADMINISTRATOR_ID);
    }

    /**
     * Check whether or not the user is a guest.
     *
     * @return bool
     */
    public function isGuest()
    {
        return false;
    }

    /**
     * Define the relationship with the user's posts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Define the relationship with the user's discussions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }

    /**
     * Define the relationship with the user's read discussions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function read()
    {
        return $this->belongsToMany(Discussion::class);
    }

    /**
     * Define the relationship with the user's groups.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function visibleGroups()
    {
        return $this->belongsToMany(Group::class)->where('is_hidden', false);
    }

    /**
     * Define the relationship with the user's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Define the relationship with the user's email tokens.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emailTokens()
    {
        return $this->hasMany(EmailToken::class);
    }

    /**
     * Define the relationship with the permissions of all of the groups that
     * the user is in.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function permissions()
    {
        $groupIds = [Group::GUEST_ID];

        // If a user's account hasn't been activated, they are essentially no
        // more than a guest. If they are activated, we can give them the
        // standard 'member' group, as well as any other groups they've been
        // assigned to.
        if ($this->is_email_confirmed) {
            $groupIds = array_merge($groupIds, [Group::MEMBER_ID], $this->groups->pluck('id')->all());
        }

        event(new PrepareUserGroups($this, $groupIds));

        return Permission::whereIn('group_id', $groupIds);
    }

    /**
     * Get a list of permissions that the user has.
     *
     * @return string[]
     */
    public function getPermissions()
    {
        return $this->permissions()->pluck('permission')->all();
    }

    /**
     * Define the relationship with the user's access tokens.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class);
    }

    /**
     * @deprecated in favor of $this->ssoProviders().
     */
    public function loginProviders()
    {
        return $this->ssoProviders();
    }

    /**
     * Get the user's sso providers.
     */
    public function ssoProviders()
    {
        return $this->hasMany(SsoProvider::class);
    }

    /**
     * Get an array of login provider identifiers linked to the user.
     */
    public function ssoProviderNames(): array
    {
        $providers = array_map(function ($provider) {
            return $provider->provider;
        }, $this->ssoProviders()->getModels());

        return array_values(array_filter($providers, function ($provider) {
            return $provider != '';
        }));
    }

    /**
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        return static::$gate->forUser($this)->allows($ability, $arguments);
    }

    /**
     * @param string $ability
     * @param array|mixed $arguments
     * @return bool
     */
    public function cannot($ability, $arguments = [])
    {
        return ! $this->can($ability, $arguments);
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Set the hasher with which to hash passwords.
     *
     * @param Hasher $hasher
     */
    public static function setHasher(Hasher $hasher)
    {
        static::$hasher = $hasher;
    }

    /**
     * Register a preference with a transformer and a default value.
     *
     * @param string $key
     * @param callable $transformer
     * @param mixed $default
     */
    public static function addPreference($key, callable $transformer = null, $default = null)
    {
        static::$preferences[$key] = compact('transformer', 'default');
    }

    /**
     * Get the key for a preference which flags whether or not the user will
     * receive a notification for $type via $method.
     *
     * @param string $type
     * @param string $method
     * @return string
     */
    public static function getNotificationPreferenceKey($type, $method)
    {
        return 'notify_'.$type.'_'.$method;
    }

    /**
     * Refresh the user's comments count.
     *
     * @return $this
     */
    public function refreshCommentCount()
    {
        $this->comment_count = $this->posts()
            ->where('type', 'comment')
            ->where('is_private', false)
            ->count();

        return $this;
    }

    /**
     * Refresh the user's comments count.
     *
     * @return $this
     */
    public function refreshDiscussionCount()
    {
        $this->discussion_count = $this->discussions()
            ->where('is_private', false)
            ->count();

        return $this;
    }
}
