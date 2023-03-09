<?php

namespace Serrvius\AmqpRpcExtender\Stamp;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcStampInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Uid\Uuid;

class AmqpRpcQueryStamp implements AmqpRpcStampInterface, StampInterface
{


    public string  $replayToQueue;
    public ?string $correlationId;


    /**
     * @param string $routingKey
     * @param string|null $executorName - if is not defined then will use the routingKey executor name
     * @param int $waitingResponseTTL - seconds
     * @param int|null $priority - from 1-255
     */
    public function __construct(
        protected readonly string $routingKey,
        protected readonly ?string $executorName = null,
        public readonly int $waitingResponseTTL = 10,
        public readonly ?int $priority = null
    ) {
        $this->correlationId = $this->correlationId ?? Uuid::v4();
        $this->replayToQueue = $this->replayToQueue ?? $this->executorName.'_replay_'.Uuid::v4();
    }

    /**
     * @return string
     */
    public function getExecutorName(): string
    {
        return $this->executorName??$this->getRoutingKey();
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }

    public function getReplayToQueue(): string
    {
        return $this->replayToQueue;
    }

    /**
     * @return string|null
     */
    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function getPriority(): ?int {
        return $this->priority;
    }


}