<?php

namespace Serrvius\AmqpRpcExtender\Stamp;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcStampInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

class AmqpRpcCommandStamp implements StampInterface, AmqpRpcStampInterface
{

    /**
     * @param string $routingKey
     * @param string|null $executorName - if is not defined then will use the routingKey executor name
     * @param int|null $priority
     */
    public function __construct(public readonly string $routingKey, public readonly ?string $executorName = null, public readonly ?int $priority = null)
    {
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }

    /**
     * @return string
     */
    public function getExecutorName(): string
    {
        return $this->executorName??$this->getRoutingKey();
    }

    public function getPriority(): ?int {
        return $this->priority;
    }

}