<?php
declare(strict_types=1);

namespace Easydb\Typo3Integration\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RequestHandlerInterface
{
    public function canHandleRequest(ServerRequestInterface $request): bool;

    public function getPriority(): int;

    public function handleRequest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
