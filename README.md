# About
The extension of **symfony/amqp-messenger**  that allow to implement CQRS/gRPC (call and wait response) in RabbitMQ Broker,
idea has to used it in microservices architecture

## What it does
This bundle use `ampq://` default transporter of symfony. The CompilerPass of bundle connect those
layers of default amqp-messenger to control the messages that have specific stamp.
The queues type is recommended to be used `direct`

## Requirements
```
 * php: >=8.1
 * ext-amqp
 * symfony: 6.1.*
```

## Installation
```bash
composer require serrvius/amqp-rpc-extender
```

## Usage

```yaml
# GATEWAY -  messenger.yaml
framework:
  framework:
  messenger:
    serializer:
      default_serializer: messenger.transport.rpc.symfony_serializer
    default_bus: command.bus
    buses:
      command.bus: ~
      query.bus: ~

    transports:
      users_commands:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        options:
          exchange:
            name: user_command
            type: direct
          queues:
            user_create:
              binding_keys: [ user_create ]
            user_update:
              binding_keys: [ user_update ]
            user_delete:
              binding_keys: [ user_delete ]
      queries:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        options:
          exchange:
            name: queries
            type: direct
          queues:
            user_queries:
              binding_keys: [ user_queries ]
```

```yaml
# Microservice (let say user)-  messenger.yaml
framework:
  messenger:
    serializer:
      default_serializer: messenger.transport.symfony_serializer
      symfony_serializer:
        format: json
        context: { }
    default_bus: command.bus
    buses:
      command.bus: ~
      query.bus: ~
    transports:
      user_commands:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        options:
          exchange: { name: user_command,  type: direct }
          queues:
            user_create:
              binding_keys: [ user_create, user_update, user_delete ]
      queries:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        options:
          exchange:
            name: queries
            type: direct
          queues:
            user_queries:
              binding_keys: [ user_queries ]
```

All call initiator messages class need to implement the interface:
```
Serrvius\AmqpRpcExtender\Stamp\AmqpRpcStampInterface
```

For command messages (classic async work): `AmqpRpcStampInterface`
the method: `amqpRpcStamp` should return: `Serrvius\AmqpRpcExtender\Stamp\AmqpRpcCommandStamp`

For query messages (need response from microservice): `AmqpRpcStampInterface`
the method: `amqpRpcStamp` should return: `Serrvius\AmqpRpcExtender\Stamp\ Serrvius\AmqpRpcExtender\Stamp\AmqpRpcQueryStamp`

Those stamps hase the attribute `executorName` the name of it will be used on microservice
side to identifier the message normalizer and handler

















```php
    // send
    $rpcCallMessage = new RpcCallMessage();

    $envelope = $this->messageBus->dispatch($rpcCallMessage);
    /** @var ResponseStamp $response */
    $response = $envelope->last(ResponseStamp::class);
    $result = $response->getResult();
```

To set the result from the handler, just return something:
```php
    final public function __invoke(RpcCallMessage $message): void
    {
        [...]
        return 42;
    }
```

## Remarks
This is a work-in-progress, as a first-shot workaround. It would be much more elegant to override the `messenger.transport.amqp.factory` service and add `rpc: true` and `rpc_queue_name` to the messenger config, so we extend the existing transporter instead of bringing in this new one. Also note: in this state, we will always generate a exclusive queue with a random name for the response. This is sub-optimal for heavy loaded queues, see https://www.rabbitmq.com/tutorials/tutorial-six-python.html  