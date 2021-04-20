<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Content;

use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotFound
{
    public function __invoke(Document $document, Request $request)
    {
        $document->title = 'Not Found';
        $document->payload['errorCode'] = 404;

        return $document;
    }
}
