<?php

namespace Serrvius\AmqpRpcExtender\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class AmqpRpcCommandStamp implements AmqpRpcStampInterface, StampInterface
{

    /**
     * @param  string  $queueName
     */
    public function __construct(public readonly string $queueName, public readonly string $executorName)
    {
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * @return string
     */
    public function getExecutorName(): string
    {
        return $this->executorName;
    }




}