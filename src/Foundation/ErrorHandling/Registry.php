<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Flarum\Foundation\KnownError;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Flarum's central registry of known error types.
 *
 * It knows how to deal with errors raised both within Flarum's core and outside
 * of it, map them to error "types" and how to determine appropriate HTTP status
 * codes for them.
 */
class Registry
{
    private $statusMap;
    private $classMap;
    private $handlerMap;
    private $contentMap;

    public function __construct(array $statusMap, array $classMap, array $handlerMap, array $contentMap)
    {
        $this->statusMap = $statusMap;
        $this->classMap = $classMap;
        $this->handlerMap = $handlerMap;
        $this->contentMap = $contentMap;
    }

    /**
     * Map exceptions to handled errors.
     *
     * This can map internal ({@see \Flarum\Foundation\KnownError}) as well as
     * external exceptions (any classes inheriting from \Throwable) to instances
     * of {@see \Flarum\Foundation\ErrorHandling\HandledError}.
     *
     * Even for unknown exceptions, a generic fallback will always be returned.
     *
     * @param Throwable $error
     * @return HandledError
     */
    public function handle(Throwable $error): HandledError
    {
        return $this->handleKnownTypes($error)
            ?? $this->handleCustomTypes($error)
            ?? HandledError::unknown($error);
    }

    private function handleKnownTypes(Throwable $error): ?HandledError
    {
        $errorType = null;

        $errorClass = get_class($error);
        if ($error instanceof KnownError) {
            $errorType = $error->getType();
        } else {
            if (isset($this->classMap[$errorClass])) {
                $errorType = $this->classMap[$errorClass];
            }
        }

        $errorContent = Arr::get($this->contentMap, $errorClass);

        if ($errorType) {
            return new HandledError(
                $error,
                $errorType,
                $this->statusMap[$errorType] ?? 500,
                $errorContent
            );
        }

        return null;
    }

    private function handleCustomTypes(Throwable $error): ?HandledError
    {
        $errorClass = get_class($error);

        if (isset($this->handlerMap[$errorClass])) {
            $handler = new $this->handlerMap[$errorClass];

            return $handler->handle($error);
        }

        return null;
    }
}
