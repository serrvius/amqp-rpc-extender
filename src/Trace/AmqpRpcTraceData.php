<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Trace;

use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcTraceStamp;
use Symfony\Component\Uid\Uuid;

class AmqpRpcTraceData
{
    private ?string $requestId = null;
    private null|string|Uuid $userId = null;
    private bool $isInitialized = false;

    public function setTraceStamp(AmqpRpcTraceStamp $stamp): void
    {
        if (!$this->isInitialized()) {
            $this->requestId = $stamp->requestId;
            $this->userId = $stamp->userId;

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

    public function getUserId(): string|Uuid|null
    {
        return $this->userId;
    }
}