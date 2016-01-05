<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Core\User;
use Flarum\Http\Exception\RouteNotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;

class DiscussionController extends ClientController
{
    /**
     * {@inheritdoc}
     */
    public function render(Request $request)
    {
        $view = parent::render($request);

        $queryParams = $request->getQueryParams();
        $page = max(1, array_get($queryParams, 'page'));

        $params = [
            'id'   => (int) array_get($queryParams, 'id'),
            'page' => [
                'near'   => array_get($queryParams, 'near'),
                'offset' => ($page - 1) * 20,
                'limit'  => 20
            ]
        ];

        $document = $this->preload($request->getAttribute('actor'), $params);

        $getResource = function ($link) use ($document) {
            return array_first($document->included, function ($key, $value) use ($link) {
                return $value->type === $link->type && $value->id === $link->id;
            });
        };

        $url = function ($newQueryParams) use ($queryParams, $document) {
            $newQueryParams = array_merge($queryParams, $newQueryParams);
            $queryString = http_build_query($newQueryParams);

            return app('Flarum\Forum\UrlGenerator')
                ->toRoute('discussion', ['id' => $document->data->id]).
                ($queryString ? '?'.$queryString : '');
        };

        $posts = [];

        foreach ($document->included as $resource) {
            if ($resource->type === 'posts' && isset($resource->relationships->discussion) && isset($resource->attributes->contentHtml)) {
                $posts[] = $resource;
            }
        }

        $view->setTitle($document->data->attributes->title);
        $view->setDocument($document);
        $view->setContent(app('view')->make('flarum.forum::discussion', compact('document', 'page', 'getResource', 'posts', 'url')));

        return $view;
    }

    /**
     * Get the result of an API request to show a discussion.
     *
     * @param User $actor
     * @param array $params
     * @return object
     * @throws RouteNotFoundException
     */
    protected function preload(User $actor, array $params)
    {
        $response = $this->api->send('Flarum\Api\Controller\ShowDiscussionController', $actor, $params);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 404) {
            throw new RouteNotFoundException;
        }

        return json_decode($response->getBody());
    }
}
