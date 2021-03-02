<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\AccessToken;
use Flarum\User\Guest;
use Illuminate\Contracts\Session\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class AuthenticateWithSession implements Middleware
{
    public function process(Request $request, Handler $handler): Response
    {
        $session = $request->getAttribute('session');

        $actor = $this->getActor($session, $request);

        $actor->setSession($session);

        $request = $request->withAttribute('actor', $actor);

        return $handler->handle($request);
    }

    private function getActor(Session $session, Request $request)
    {
        $token = AccessToken::find($session->get('access_token'));

        $actor = $token ? $token->user : new Guest;

        if ($token) {
            $token->updateLastActivity($request);
        }

        if ($actor->exists) {
            $actor->updateLastSeen()->save();
        }

        return $actor;
    }
}
