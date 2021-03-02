<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\AccessToken;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class DeleteTokenController extends AbstractDeleteController
{
    /**
     * Delete the resource.
     *
     * @param ServerRequestInterface $request
     */
    protected function delete(ServerRequestInterface $request)
    {
        $token = AccessToken::findOrFail(Arr::get($request->getQueryParams(), 'id'));

        $actor = $request->getAttribute('actor');

        if ($actor->cannot('delete', $token)) {
            // If we throw an unauthorized exception, it would allow brute-forcing tokens without rate limiting
            throw new ModelNotFoundException();
        }

        $token->delete();
    }
}
