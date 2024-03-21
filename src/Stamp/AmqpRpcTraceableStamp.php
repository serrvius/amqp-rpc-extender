<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Stamp;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcStampInterface;
use Symfony\Component\Uid\Uuid;

class AmqpRpcTraceableStamp implements AmqpRpcStampInterface
{
    public function __construct(
        public readonly Uuid|string|int|float $requestId,
        public readonly Uuid|null $userId = null,
        public readonly string|int|null $eventId = null
    ) {
    }
}