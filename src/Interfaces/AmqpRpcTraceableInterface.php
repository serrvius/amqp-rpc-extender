<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Interfaces;

use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcTraceableStamp;

interface AmqpRpcTraceableInterface
{
    public function eventId(): string;

    public function userId(): string|int|null;

    public function requestId(): string|int|null;

    public function setTraceableStamp(AmqpRpcTraceableStamp $stamp): void;

    public function getTraceableStamp(): AmqpRpcTraceableStamp;
}