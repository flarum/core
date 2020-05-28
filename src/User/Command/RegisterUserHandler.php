<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AssertPermissionTrait;
use Flarum\User\AvatarUploader;
use Flarum\User\Event\RegisteringFromProvider;
use Flarum\User\Event\Saving;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\RegistrationToken;
use Flarum\User\User;
use Flarum\User\UserValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

class RegisterUserHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var UserValidator
     */
    protected $validator;

    /**
     * @var AvatarUploader
     */
    protected $avatarUploader;

    /**
     * @param Dispatcher $events
     * @param SettingsRepositoryInterface $settings
     * @param UserValidator $validator
     * @param AvatarUploader $avatarUploader
     */
    public function __construct(Dispatcher $events, SettingsRepositoryInterface $settings, UserValidator $validator, AvatarUploader $avatarUploader)
    {
        $this->events = $events;
        $this->settings = $settings;
        $this->validator = $validator;
        $this->avatarUploader = $avatarUploader;
    }

    /**
     * @param RegisterUser $command
     * @return User
     * @throws PermissionDeniedException if signup is closed and the actor is
     *     not an administrator.
     * @throws ValidationException
     */
    public function handle(RegisterUser $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        if (! $this->settings->get('allow_sign_up')) {
            $this->assertAdmin($actor);
        }

        $password = Arr::get($data, 'attributes.password');

        // If a valid authentication token was provided as an attribute,
        // then we won't require the user to choose a password.
        if (isset($data['attributes']['token'])) {
            $token = RegistrationToken::validOrFail($data['attributes']['token']);

            $password = $password ?: Str::random(20);
        }

        $user = User::register(
            Arr::get($data, 'attributes.username'),
            Arr::get($data, 'attributes.email'),
            $password
        );

        if (isset($token)) {
            $this->applyToken($user, $token);
        }

        if ($actor->isAdmin() && Arr::get($data, 'attributes.isEmailConfirmed')) {
            $user->activate();
        }

        $this->events->dispatch(
            new Saving($user, $actor, $data)
        );

        $this->validator->assertValid(array_merge($user->getAttributes(), compact('password')));

        $user->save();

        if (isset($token)) {
            $this->fulfillToken($user, $token);
        }

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }

    private function applyToken(User $user, RegistrationToken $token)
    {
        foreach ($token->user_attributes as $k => $v) {
            if ($k === 'avatar_url') {
                $this->uploadAvatarFromUrl($user, $v);
                continue;
            }

            $user->$k = $v;

            if ($k === 'email') {
                $user->activate();
            }
        }

        $this->events->dispatch(
            new RegisteringFromProvider($user, $token->provider, $token->payload)
        );
    }

    private function uploadAvatarFromUrl(User $user, string $url)
    {
        $image = (new ImageManager)->make($url);

        $this->avatarUploader->upload($user, $image);
    }

    private function fulfillToken(User $user, RegistrationToken $token)
    {
        $token->delete();

        if ($token->provider && $token->identifier) {
            $user->ssoProviders()->create([
                'provider' => $token->provider,
                'identifier' => $token->identifier
            ]);
        }
    }
}
