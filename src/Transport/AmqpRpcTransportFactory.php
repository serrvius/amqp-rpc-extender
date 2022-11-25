<?php

namespace Serrvius\AmqpRpcExtender\Transport;

use Serrvius\AmqpRpcExtender\Serializer\AmqpRpcMessageSerializer;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AmqpRpcTransportFactory implements TransportFactoryInterface
{

    public function __construct(
        protected readonly ?AmqpRpcMessageSerializer $amqpRpcMessageSerializer = null
    ) {
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        unset($options['transport_name']);

        return new AmqpRpcTransport(Connection::fromDsn($dsn, $options), $serializer, $this->amqpRpcMessageSerializer);
    }

    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'amqp://') || str_starts_with($dsn, 'amqps://');
    }

}