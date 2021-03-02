<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Illuminate\Contracts\Session\Session;

class SessionAuthenticator
{
    /**
     * @param Session $session
     * @param AccessToken|int $token Token or user ID
     */
    public function logIn(Session $session, $token)
    {
        // Backwards compatibility with $userId as parameter
        if (! ($token instanceof AccessToken)) {
            $token = AccessToken::generate($token);
        }

        $session->regenerate(true);
        $session->put('access_token', $token->token);
    }

    /**
     * @param Session $session
     */
    public function logOut(Session $session)
    {
        $token = AccessToken::find($session->get('access_token'));

        if ($token) {
            $token->delete();
        }

        $session->invalidate();
        $session->regenerateToken();
    }
}
