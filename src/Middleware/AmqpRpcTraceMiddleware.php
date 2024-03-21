<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Middleware;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcTraceableInterface;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcTraceableStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;

final class AmqpRpcTraceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AmqpRpcTraceableInterface $traceableInfo
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $traceStamp = $envelope->last(AmqpRpcTraceableStamp::class);
        $isConsumedMessage = $envelope->last(ConsumedByWorkerStamp::class);

        if ($isConsumedMessage && $traceStamp) {
            $this->traceableInfo->setTraceableStamp($traceStamp);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}