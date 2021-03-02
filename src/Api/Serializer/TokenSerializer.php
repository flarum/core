<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

class TokenSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'access-tokens';

    public function getId($token)
    {
        return $token->token;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($token)
    {
        $session = $this->request->getAttribute('session');

        return [
            'token' => $token->token,
            'userId' => $token->user_id,
            'createdAt' => $this->formatDate($token->created_at),
            'lastActivityAt' => $this->formatDate($token->last_activity_at),
            'lifetimeSeconds' => $token->lifetime_seconds,
            'current' => $session && $session->get('access_token') === $token->token,
            'title' => $token->title,
            'lastIpAddress' => $token->last_ip_address,
            'lastUserAgent' => $token->last_user_agent,
        ];
    }
}
