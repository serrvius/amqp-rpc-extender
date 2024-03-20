<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Interfaces;

use Symfony\Component\Uid\Uuid;

interface AmqpRpcTraceDataInterface
{
    public function getRequestId(): ?string;

    public function getUserId(): ?Uuid;
}