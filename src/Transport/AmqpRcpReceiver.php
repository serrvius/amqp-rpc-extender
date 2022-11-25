<?php

namespace Serrvius\AmqpRpcExtender\Transport;

use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceiver;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpRcpReceiver extends AmqpReceiver
{


    private Connection           $connection;
    private ?SerializerInterface $serializer;

    public function __construct(Connection $connection, SerializerInterface $serializer = null)
    {
        parent::__construct($connection, $serializer);
        $this->connection = $connection;
        $this->serializer = $serializer;
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
                'body' => false === $body ? '' : $body, // workaround https://github.com/pdezwart/php-amqp/issues/351
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

}