<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Uid\Uuid;

class AmqpRpcTraceStamp implements StampInterface
{
    public function __construct(
        public readonly Uuid|string|int|float $requestId,
        public readonly Uuid|string|int|null $userId = null,
    ) {
    }
}