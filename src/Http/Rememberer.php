<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Psr\Http\Message\ResponseInterface;

class Rememberer
{
    const COOKIE_NAME = 'remember';

    /**
     * @var CookieFactory
     */
    protected $cookie;

    /**
     * @param CookieFactory $cookie
     */
    public function __construct(CookieFactory $cookie)
    {
        $this->cookie = $cookie;
    }

    public function remember(ResponseInterface $response, AccessToken $token)
    {
        $token->lifetime_seconds = 5 * 365 * 24 * 60 * 60; // 5 years
        $token->save();

        return $this->cookie
            ->make(self::COOKIE_NAME, $token->token, $token->lifetime_seconds)
            ->addToResponse($response);
    }

    public function rememberUser(ResponseInterface $response, $userId)
    {
        $token = AccessToken::generate($userId);

        return $this->remember($response, $token);
    }

    public function forget(ResponseInterface $response)
    {
        return $this->cookie->expire(self::COOKIE_NAME)
            ->addToResponse($response);
    }
}
