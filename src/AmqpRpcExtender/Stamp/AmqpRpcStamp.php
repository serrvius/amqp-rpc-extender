<?php

namespace Serrvius\AmqpRpcExtender\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class AmqpRpcStamp implements StampInterface
{


    protected string $replayToQueue;


    /**
     * @param  string  $queueName
     * @param  string|null  $correlationId
     * @param  int  $waitingResponseTTL - seconds
     */
    public function __construct(
        protected readonly string $queueName,
        protected readonly string $procedureName,
        protected ?string $correlationId = null,
        public readonly int $waitingResponseTTL = 10
    ) {
        $this->correlationId = $correlationId ?? md5(microtime(true));
        $this->replayToQueue = $queueName.'_replay_'.md5(microtime(true).$this->getCorrelationId());
    }

    /**
     * @return string
     */
    public function getProcedureName(): string
    {
        return $this->procedureName;
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