<?php

namespace Serrvius\AmqpRpcExtender\Transport;

use Serrvius\AmqpRpcExtender\Serializer\AmqpRpcMessageSerializer;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcQueryResultStamp;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcQueryStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceiver;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpRcpReceiver extends AmqpReceiver
{


    private Connection           $connection;
    private ?SerializerInterface $serializer;
    private AmqpRpcTransport     $amqpRpcTransport;

    public function __construct(
        Connection $connection,
        SerializerInterface $serializer = null,
        AmqpRpcMessageSerializer $amqpRpcMessageSerializer = null,

    ) {

        $this->connection = $connection;
        $this->serializer = $amqpRpcMessageSerializer ?? $serializer;


        parent::__construct($connection, $serializer);
    }

    public function addTransport(AmqpRpcTransport $amqpRpcTransport): void
    {
        $this->amqpRpcTransport = $amqpRpcTransport;
    }

    public function get(): iterable
    {
        yield from $this->getFromQueues($this->connection->getQueueNames());
    }

    public function getFromQueues(array $queueNames): iterable
    {
        foreach ($queueNames as $queueName) {
            yield from $this->getEnvelope($queueName);
        }
    }

    private function getEnvelope(string $queueName): iterable
    {
        try {
            $amqpEnvelope = $this->connection->get($queueName);
        } catch (\AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        if (null === $amqpEnvelope) {
            return;
        }

        $body = $amqpEnvelope->getBody();

        try {
            $envelope = $this->serializer->decode([
                'body'    => false === $body ? '' : $body, // workaround https://github.com/pdezwart/php-amqp/issues/351
                'headers' => $amqpEnvelope->getHeaders(),
            ]);
        } catch (MessageDecodingFailedException $exception) {
            // invalid message of some type
            $this->rejectAmqpEnvelope($amqpEnvelope, $queueName);

            throw $exception;
        }

        yield $envelope->with(new AmqpReceivedStamp($amqpEnvelope, $queueName));
    }

    private function rejectAmqpEnvelope(\AMQPEnvelope $amqpEnvelope, string $queueName): void
    {
        try {
            $this->connection->nack($amqpEnvelope, $queueName, \AMQP_NOPARAM);
        } catch (\AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function ack(Envelope $envelope): void
    {
        try {
            $stamp = $this->findAmqpStamp($envelope);

            $this->connection->ack(
                $stamp->getAmqpEnvelope(),
                $stamp->getQueueName()
            );

            if (($amqpRpcQueryStamp = $envelope->last(AmqpRpcQueryStamp::class)) && ($handledStamp = $envelope->last(HandledStamp::class))) {

                $responseEnvelop = new Envelope(
                    new \stdClass(),
                    [
                        $envelope->last(BusNameStamp::class),
                        new AmqpRpcQueryResultStamp($handledStamp->getResult()),
                        new AmqpStamp(
                            $amqpRpcQueryStamp->getReplayToQueue(),
                            AMQP_NOPARAM,
                            [
                                'headers'        => [
                                    'source_type' => $stamp->getAmqpEnvelope()->getHeader('type')
                                ],
                                'correlation_id' => $amqpRpcQueryStamp->getCorrelationId(),
                            ]
                        )
                    ]
                );
                $this->amqpRpcTransport->send($responseEnvelop);

            }
        } catch (\AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    private function findAmqpStamp(Envelope $envelope): AmqpReceivedStamp
    {
        $amqpReceivedStamp = $envelope->last(AmqpReceivedStamp::class);
        if (null === $amqpReceivedStamp) {
            throw new LogicException('No "AmqpReceivedStamp" stamp found on the Envelope.');
        }

        return $amqpReceivedStamp;
    }


}