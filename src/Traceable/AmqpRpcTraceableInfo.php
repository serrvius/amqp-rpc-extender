<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Traceable;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcTraceableInterface;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcTraceableStamp;
use Symfony\Component\Uid\Uuid;

final class AmqpRpcTraceableInfo implements AmqpRpcTraceableInterface
{
    private string|int|null $userId = null;
    private string|int|null $requestId = null;
    private string|null $eventId = null;
    private bool $isUserIdUuid = false;
    private null|AmqpRpcTraceableStamp $stamp = null;

    public function eventId(): string
    {
        if (!$this->eventId) {
            $this->eventId = Uuid::v6()->toRfc4122();
        }

        return $this->eventId;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function isUserIdUuid(): bool
    {
        return $this->isUserIdUuid;
    }

    public function setTraceableStamp(AmqpRpcTraceableStamp $stamp): void
    {
        $userId = $stamp->userId;
        if ($userId instanceof Uuid) {
            $this->isUserIdUuid = true;
            $userId = $userId->toRfc4122();
        }

        $this->userId = $userId;
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