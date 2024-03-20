<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Middleware;

use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcTraceStamp;
use Serrvius\AmqpRpcExtender\Trace\AmqpRpcTraceData;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class AmqpRpcTraceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AmqpRpcTraceData $amqpRpcTraceData
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $traceStamp = $envelope->last(AmqpRpcTraceStamp::class);

        if ($traceStamp) {
            $this->amqpRpcTraceData->setTraceStamp($traceStamp);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}