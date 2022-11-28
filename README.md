# About
The extension of **symfony/amqp-messenger**  that allow to implement CQRS/gRPC (call and wait response) in RabbitMQ Broker,
idea has to used it in microservices architecture

## What it does
The bundle use `ampq://` default transporter of symfony. The CompilerPass of bundle connect those
layers of default amqp-messenger to control the messages that have specific stamp.
The queues type is recommended to be used `direct` for using the `routing_key`

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
      default_serializer: messenger.transport.rpc.symfony_serializer
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
We need to replace de default symfony messenger serializer `messenger.transport.symfony_serializer` with the one that
provide the extension `messenger.transport.rpc.symfony_serializer`, that is needed
to encode and decode the messages in the right way.

---
#### Gateway side
All call initiator messages (from the gateway) classes need to implement the interface:
```
Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcRequestInterface
```
the abstract method `amqpRpcStamp` should return the instance of 
```
Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcStampInterface
```
For command messages (classic async work - don't need the callback) the method:
`amqpRpcStamp` should return: `Serrvius\AmqpRpcExtender\Stamp\AmqpRpcCommandStamp`

For query messages (need response from microservice) the method:
`amqpRpcStamp` should return: `Serrvius\AmqpRpcExtender\Stamp\ Serrvius\AmqpRpcExtender\Stamp\AmqpRpcQueryStamp`
 
## Examples of gateway messages:

Query message:
```php
<?php

namespace App\UsersModule\Query;


use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcQueryStamp;
use Serrvius\AmqpRpcExtender\Interface\AmqpRpcRequestInterface;

class ShowUserQuery  implements AmqpRpcRequestInterface
{

    public function amqpRpcStamp(): AmqpRpcQueryStamp
    {
        return new AmqpRpcQueryStamp('user_queries','show_user');
    }

```

Command message:
```php

namespace App\UsersModule\Command;

use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcCommandStamp;
use Serrvius\AmqpRpcExtender\Interface\AmqpRpcRequestInterface;

class CreateUserCommand  implements AmqpRpcRequestInterface
{
 
    public function amqpRpcStamp(): AmqpRpcCommandStamp
    {
        return new AmqpRpcCommandStamp('user_create','user_create');
    }

```

Those stamps `AmqpRpcQueryStamp` and `AmqpRpcCommandStamp` implement the `AmqpRpcStampInterface`

--------
#### Microservice side
On microservice side we have two ways of handle the message, for correct decoding
the message and map it into object bundle offer annotations or classic interface implementing:

The command need to implement the interface 
```
Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcCommandInterface
```
the methods `commandIndex` should return the name of executor of gateway command
and in method `setCommandData` the bundle will put the input message as array

### Examples of microservice command messages:
```php

<?php

namespace App\Command;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcCommandInterface;

class UpdateUserCommand implements AmqpRpcCommandInterface
{


    public static function commandIndex(): string
    {
        return 'user_update';
    }

    protected $commadData;

    public function setCommandData(array $data): void
    {
        $this->commadData = $data;
    }

```

the second way to define the message is to use the annotation 
```
Serrvius\AmqpRpcExtender\Annotation\AsAmqpRpcCommandExecutor
```
the attribute of it is `name` represent also the `executorName` from gateway

```php
<?php

namespace App\Command;

use Serrvius\AmqpRpcExtender\Annotation\AsAmqpRpcCommandExecutor;

#[AsAmqpRpcCommandExecutor(name: 'user_create')]
class CreateUserCommand
{

    public string $id;

    public string $username;

    public string $password;

    public string $firstName;

    public string $lastName;


}
```
in that way the data will be mapped into object to properties
the handler will be implemented as usual



The query messages like command was two ways of definition, by the interface
```
Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcQueryInterface
```
or annotation 
```
Serrvius\AmqpRpcExtender\Annotation\AsAmqpRpcQueryExecutor
```

### Examples of microservice query messages:

```php

<?php

namespace App\Query;


use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcQueryInterface;

class ShowUserQueryExecutor implements AmqpRpcQueryInterface
{

    public array $data;

    public static function queryIndex(): string
    {
        return "show_user";
    }

    public function setQueryData(array $data): void
    {
        $this->data = $data;
    }

```
the method  `queryIndex` should return the `executorName` from gateway query call
and in method `setQueryData` will receive the input data as array.

On command the handler will be called as usual messenger doing it 

The annotation version of message is:

```php

<?php

namespace App\Query;

use Serrvius\AmqpRpcExtender\Annotation\AsAmqpRpcQueryExecutor;


#[AsAmqpRpcQueryExecutor(name: 'list_user')]
class ListUserQueryExecutor
{

    protected string $userId;


}
```
the `name` of annotation is also the `executorName` of gateway message and the data
will be mapped into properties
 
The handler of query should return the data that to return it on the gateway request which will wainting for it

```php
<?php

namespace App\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ShowUserQueryHandler
{

    public function __invoke(ShowUserQueryExecutor $query)
    {


        //Do the handler work and return response to gateway 
        
        return $resp;
    }

}
```

## Additional

The query request stamp 
```php
Serrvius\AmqpRpcExtender\Stamp\AmqpRpcQueryStamp
```
accept those attributes:
* $queueName - the queue name
* $executorName - the executor name
* $waitingResponseTTL - the time (in seconds) for wainting the response from microservice, default is 10 secons


## Remarks
The inspiring and documentation used for did that work was taken from:

[leberknecht/amqp-rpc-transporter-bundle](https://github.com/leberknecht/amqp-rpc-transporter-bundle)
[Remote procedure call (RPC)](https://www.rabbitmq.com/tutorials/tutorial-six-php.html)
