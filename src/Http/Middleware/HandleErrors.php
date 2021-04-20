<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Foundation\ErrorHandling\HttpFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

/**
 * Catch exceptions thrown in a PSR-15 middleware stack and handle them safely.
 *
 * All errors will be rendered using the provided formatter. In addition,
 * unknown errors will be passed on to one or multiple
 * {@see \Flarum\Foundation\ErrorHandling\Reporter} instances.
 */
class HandleErrors implements Middleware
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var HttpFormatter
     */
    protected $formatter;

    /**
     * @var HttpFormatter
     */
    protected $frontendFormatter;

    /**
     * @var \Flarum\Foundation\ErrorHandling\Reporter[]
     */
    protected $reporters;

    public function __construct(Registry $registry, HttpFormatter $formatter, HttpFormatter $frontendFormatter, iterable $reporters)
    {
        $this->registry = $registry;
        $this->formatter = $formatter;
        $this->frontendFormatter = $frontendFormatter;
        $this->reporters = $reporters;
    }

    /**
     * Catch all errors that happen during further middleware execution.
     */
    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            $error = $this->registry->handle($e);

            if ($error->shouldBeReported()) {
                foreach ($this->reporters as $reporter) {
                    $reporter->report($error->getException());
                }
            }

            if ($error->contentClass()) {
                return $this->frontendFormatter->format($error, $request);
            }

            return $this->formatter->format($error, $request);
        }
    }
}
