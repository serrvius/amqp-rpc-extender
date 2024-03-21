<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Stamp;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcStampInterface;
use Symfony\Component\Uid\Uuid;

class AmqpRpcTraceableStamp implements AmqpRpcStampInterface
{
    public function __construct(
        public readonly string|int|null $eventId,
        public readonly Uuid|string|int|float|null $requestId = null,
        public readonly Uuid|null $userId = null,
    ) {
    }
}