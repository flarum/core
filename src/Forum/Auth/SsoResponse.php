<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Auth;

class SsoResponse
{
    protected $provider;

    protected $identifier;

    /**
     * @var array
     */
    protected $provided = [];

    /**
     * @var array
     */
    protected $suggested = [];

    /**
     * @var mixed
     */
    protected $payload = [];

    /**
     * @var string
     */
    public function __construct(string $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return $this
     */
    public function withIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return array
     */
    public function getProvided(): array
    {
        return $this->provided;
    }

    /**
     * @return array
     */
    public function getSuggested(): array
    {
        return $this->suggested;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function provide(string $key, $value): self
    {
        $this->provided[$key] = $value;

        return $this;
    }

    /**
     * @param string $email
     * @return $this
     *
     * @deprecated in favor of provideEmail(), as email trustiness is now determined in admin settings.
     */
    public function provideTrustedEmail(string $email): self
    {
        return $this->provide('email', $email);
    }

    /**
     * @param string $email
     * @return $this
     */
    public function provideEmail(string $email): self
    {
        return $this->provide('email', $email);
    }

    /**
     * @param string $url
     * @return $this
     */
    public function provideAvatar(string $url): self
    {
        return $this->provide('avatar_url', $url);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function suggest(string $key, $value): self
    {
        $this->suggested[$key] = $value;

        return $this;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function suggestUsername(string $username): self
    {
        $username = preg_replace('/[^a-z0-9-_]/i', '', $username);

        return $this->suggest('username', $username);
    }

    /**
     * @param string $email
     * @return $this
     */
    public function suggestEmail(string $email): self
    {
        return $this->suggest('email', $email);
    }

    /**
     * @param mixed $payload
     * @return $this
     */
    public function setPayload($payload): self
    {
        $this->payload = $payload;

        return $this;
    }
}
