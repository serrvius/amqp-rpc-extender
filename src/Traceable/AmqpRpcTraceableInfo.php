<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Traceable;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcTraceableInterface;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcTraceableStamp;
use Symfony\Component\Uid\Uuid;

final class AmqpRpcTraceableInfo implements AmqpRpcTraceableInterface
{
    private Uuid|string|int|null $userId = null;
    private string|int|null $requestId = null;
    private string|null $eventId = null;
    private null|AmqpRpcTraceableStamp $stamp = null;

    public function eventId(): string
    {
        if (!$this->eventId) {
            $this->eventId = Uuid::v6()->toRfc4122();
        }

        return $this->eventId;
    }

    public function userId(): Uuid|string|int|null
    {
        return $this->userId;
    }

    public function requestId(): string|int|null
    {
        return $this->requestId;
    }

    public function setTraceableStamp(AmqpRpcTraceableStamp $stamp): void
    {
        $this->userId = $stamp->userId;
        $this->requestId = $stamp->requestId;
        $this->eventId = $stamp->eventId;

        $this->stamp = $stamp;
    }

    public function getTraceableStamp(): AmqpRpcTraceableStamp
    {
        if (!$this->stamp) {
            $this->stamp = new AmqpRpcTraceableStamp(
                $this->eventId(),
                $this->requestId(),
                $this->userId(),
            );
        }

        return $this->stamp;
    }
}