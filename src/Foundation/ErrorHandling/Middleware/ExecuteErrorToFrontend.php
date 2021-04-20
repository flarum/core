<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling\Middleware;

use Flarum\Http\RouteHandlerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExecuteErrorToFrontend implements MiddlewareInterface
{
    /**
     * @var string
     */
    protected $frontend;

    /**
     * @var RouteHandlerFactory
     */
    protected $handlerFactory;

    public function __construct(string $frontend, RouteHandlerFactory $handlerFactory)
    {
        $this->frontend = $frontend;
        $this->handlerFactory = $handlerFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $error = $request->getAttribute('error');

        $contentClass = $error->contentClass();
        $controller = $this->handlerFactory->toFrontend($this->frontend, new $contentClass);

        return $controller($request, [])->withStatus($error->getStatusCode());
    }
}
