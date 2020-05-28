<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use Carbon\Carbon;
use Flarum\Api\ApiKey;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;

abstract class AuthenticatedTestCase extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function genKey(int $user_id = null): ApiKey
    {
        return ApiKey::unguarded(function () use ($user_id) {
            return ApiKey::query()->firstOrCreate([
                'key'        => Str::random(),
                'user_id'    => $user_id,
                'created_at' => Carbon::now()
            ]);
        });
    }

    /**
     * Build an authenticated HTTP request that can be passed through middleware.
     *
     * This method simplifies building HTTP request for use in our HTTP-level
     * integration tests. It provides options for all features repeatedly being
     * used in those tests.
     *
     * @param string $method
     * @param string $path
     * @param array $options
     *   An array of optional request properties.
     *   Currently supported:
     *   - "json" should point to a JSON-serializable object that will be
     *     serialized and used as request body. The corresponding Content-Type
     *     header will be set automatically.
     *   - "cookiesFrom" should hold a response object from a previous HTTP
     *     interaction. All cookies returned from the server in that response
     *     (via the "Set-Cookie" header) will be copied to the cookie params of
     *     the new request.
     * @param int $userId Which user should be emulated? User ID 1 will return a
     * user with admin perms unless this has been modified in your test case.
     * @return ServerRequestInterface
     */
    protected function authenticatedRequest(string $method, string $path, array $options = [], int $userId = 1): ServerRequestInterface
    {
        $request = $this->request($method, $path, $options);

        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
        ]);

        if (! isset($this->key)) {
            $this->key = $this->genKey();
        }

        return $request->withAddedHeader('Authorization', "Token {$this->key->key}; userId=$userId");
    }
}
