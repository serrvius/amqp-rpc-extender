<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Trace;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcTraceDataInterface;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcTraceStamp;
use Symfony\Component\Uid\Uuid;

final class AmqpRpcTraceData implements AmqpRpcTraceDataInterface
{
    private ?string $requestId = null;
    private ?Uuid $userId = null;
    private bool $isInitialized = false;

    public function setTraceStamp(AmqpRpcTraceStamp $stamp): void
    {
        if (!$this->isInitialized()) {
            $this->requestId = $stamp->requestId;

            $this->userId = is_string($stamp->userId)
                ? Uuid::fromString($stamp->userId)
                : $stamp->userId;

            $this->isInitialized = true;
        }
    }

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getUserId(): ?Uuid
    {
        return $this->userId;
    }
}