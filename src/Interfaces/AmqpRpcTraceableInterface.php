<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Interfaces;

use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcTraceableStamp;
use Symfony\Component\Uid\Uuid;

interface AmqpRpcTraceableInterface
{
    public function eventId(): string;

    public function userId(): Uuid|string|int|null;

    public function requestId(): string|int|null;

    public function setTraceableStamp(AmqpRpcTraceableStamp $stamp): void;

    public function getTraceableStamp(): AmqpRpcTraceableStamp;
}