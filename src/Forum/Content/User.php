<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Content;

use Flarum\Api\Client;
use Flarum\Api\Controller\ShowUserController;
use Flarum\Frontend\Document;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\UrlGenerator;
use Flarum\User\User as FlarumUser;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class User
{
    /**
     * @var Client
     */
    protected $api;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param Client $api
     * @param UrlGenerator $url
     */
    public function __construct(Client $api, UrlGenerator $url)
    {
        $this->api = $api;
        $this->url = $url;
    }

    public function __invoke(Document $document, Request $request)
    {
        $queryParams = $request->getQueryParams();
        $actor = $request->getAttribute('actor');
        $userId = Arr::get($queryParams, 'username');

        $params = [
            'id' => $userId,
        ];

        $apiDocument = $this->getApiDocument($actor, $params);

        $document->title = $apiDocument->data->attributes->title;
        $document->canonicalUrl = $this->url->to('forum')->route('user', ['username' => $userId]);
        $document->payload['apiDocument'] = $apiDocument;

        return $document;
    }

    /**
     * Get the result of an API request to show a user.
     *
     * @param FlarumUser $actor
     * @param array $params
     * @return object
     * @throws RouteNotFoundException
     */
    protected function getApiDocument(FlarumUser $actor, array $params)
    {
        $response = $this->api->send(ShowUserController::class, $actor, $params);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 404) {
            throw new RouteNotFoundException;
        }

        return json_decode($response->getBody());
    }
}
