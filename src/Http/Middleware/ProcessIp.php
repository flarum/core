<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface;

class ProcessIp implements Middleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ipAddress = $this->processProxyHeaders($request);
        // If no header set via the proxy headers, use the IP given by the web server
        $ipAddress ?? Arr::get($request->getServerParams(), 'REMOTE_ADDR', '127.0.0.1');

        var_dump($ipAddress);

        return $handler->handle($request->withAttribute('ipAddress', $ipAddress));
    }

    private function processProxyHeaders(ServerRequestInterface $request): ?string
    {
        $ipAddress = null;
        $xForwardedFor = $request->getHeader('X-Forwarded-For');
        if ($xForwardedFor) {
            // Use the first available header
            $xForwardedFor = explode(',', $xForwardedFor[0]);
            // Client IP will always be the first IP listed in header
            $ipAddress = $xForwardedFor[0];
        }
        $forwarded = $request->getHeader('Forwarded');
        if ($forwarded) {
            // IP sets will be split via comma
            $forwarded = explode(';', explode(',', $forwarded[0])[0]);
            // Find the option that starts with "for="
            foreach ($forwarded as $option) {
                if (str_starts_with($option, 'for=')) {
                    // strip out all the extra garbage that might exist and return only the IP
                    $option = str_replace(['for=', '[', ']', '"'], '', $option);
                    $ipAddress = $option;
                }
            }
        }

        return $ipAddress;
    }
}
