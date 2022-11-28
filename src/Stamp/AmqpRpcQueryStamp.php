<?php

namespace Serrvius\AmqpRpcExtender\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcStampInterface;

use Symfony\Component\Uid\Uuid;

class AmqpRpcQueryStamp implements AmqpRpcStampInterface, StampInterface
{


    public string  $replayToQueue;
    public ?string $correlationId;


    /**
     * @param  string  $queueName
     * @param  string|null  $correlationId
     * @param  int  $waitingResponseTTL  - seconds
     */
    public function __construct(
        protected readonly string $queueName,
        protected readonly string $executorName,
        public readonly int $waitingResponseTTL = 10
    ) {
        $this->correlationId = $this->correlationId ?? Uuid::v4();
        $this->replayToQueue = $this->replayToQueue ?? $this->executorName.'_replay_'.Uuid::v4();
    }

    /**
     * @return string
     */
    public function getExecutorName(): string
    {
        return $this->executorName;
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
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



}