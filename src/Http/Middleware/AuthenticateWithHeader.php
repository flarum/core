<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Api\ApiKey;
use Flarum\Http\AccessToken;
use Flarum\User\User;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class AuthenticateWithHeader implements Middleware
{
    const TOKEN_PREFIX = 'Token ';

    public function process(Request $request, Handler $handler): Response
    {
        $headerLine = $request->getHeaderLine('authorization');

        $parts = explode(';', $headerLine);

        if (isset($parts[0]) && Str::startsWith($parts[0], self::TOKEN_PREFIX)) {
            $id = substr($parts[0], strlen(self::TOKEN_PREFIX));

            if ($key = ApiKey::where('key', $id)->first()) {
                $key->touch();

                $userId = $parts[1] ?? '';
                $actor = $key->user ?? $this->getUser($userId);

                $request = $request->withAttribute('apiKey', $key);
                $request = $request->withAttribute('bypassThrottling', true);
            } elseif ($token = AccessToken::find($id)) {
                $token->updateLastActivity($request);

                $actor = $token->user;
            }

            if (isset($actor)) {
                $request = $request->withAttribute('actor', $actor);
                $request = $request->withAttribute('bypassCsrfToken', true);
                $request = $request->withoutAttribute('session');
            }
        }

        return $handler->handle($request);
    }

    private function getUser($string)
    {
        $parts = explode('=', trim($string));

        if (isset($parts[0]) && $parts[0] === 'userId') {
            return User::find($parts[1]);
        }
    }
}
