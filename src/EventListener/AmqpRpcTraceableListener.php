<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\EventListener;

use Serrvius\AmqpRpcExtender\Enum\TraceableRequestId;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcTraceableInterface;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcTraceableStamp;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Uuid;

class AmqpRpcTraceableListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AmqpRpcTraceableInterface $traceableInfo
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SendMessageToTransportsEvent::class => 'onSendMessageToTransport',
            WorkerMessageReceivedEvent::class => 'onWorkerMessageReceived'
        ];
    }

    public function onSendMessageToTransport(SendMessageToTransportsEvent $event): void
    {
        $envelope = $event->getEnvelope();

        $stamp = $this->getTraceableStamp($envelope);

        if (!$stamp) {
            $stamp = new AmqpRpcTraceableStamp(
                $this->getRequestId(),
                $this->getUserId(),
                $this->traceableInfo->eventId()
            );
        }

        $envelope->with($stamp);
        $this->traceableInfo->setTraceableStamp($stamp);
    }

    public function onWorkerMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        $envelope = $event->getEnvelope();
        $stamp = $this->getTraceableStamp($envelope);
        if ($stamp) {
            $this->traceableInfo->setTraceableStamp($stamp);
        }
    }

    private function getUserId(): Uuid|string|int|null
    {
        return $this->tokenStorage
            ?->getToken()
            ?->getUser()
            ?->getId();
    }

    private function getRequestId(): ?string
    {
        return $this->requestStack
            ?->getCurrentRequest()
            ->headers
            ->get(TraceableRequestId::REQUEST_ID->value);
    }

    private function getTraceableStamp(Envelope $envelope): ?AmqpRpcTraceableStamp
    {
        return $envelope->last(AmqpRpcTraceableStamp::class);
    }
}