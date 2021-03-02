<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\TokenSerializer;
use Flarum\Http\AccessToken;
use Flarum\Http\UrlGenerator;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListTokensController extends AbstractListController
{
    public $serializer = TokenSerializer::class;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param UrlGenerator $url
     */
    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');

        $offset = $this->extractOffset($request);
        $limit = $this->extractLimit($request);

        $tokens = AccessToken::whereVisibleTo($actor)->skip($offset)->take($limit + 1)->get();

        if ($areMoreResults = ($limit > 0 && $tokens->count() > $limit)) {
            $tokens->pop();
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('tokens.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $areMoreResults
        );

        return $tokens;
    }
}
