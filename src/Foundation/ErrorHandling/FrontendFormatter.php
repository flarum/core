<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Laminas\Stratigility\MiddlewarePipeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * This formatter will route errors to the SPA frontend.
 */
class FrontendFormatter implements HttpFormatter
{
    /**
     * @var MiddlewarePipeInterface
     */
    protected $pipe;

    public function __construct(MiddlewarePipeInterface $pipe)
    {
        $this->pipe = $pipe;
    }

    public function format(HandledError $error, Request $request): Response
    {
        return $this->pipe->handle($request->withAttribute('error', $error));
    }
}
