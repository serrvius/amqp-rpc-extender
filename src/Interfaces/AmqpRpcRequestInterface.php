<?php

namespace Serrvius\AmqpRpcExtender\Interfaces;

use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcStampInterface;

interface AmqpRpcRequestInterface
{

    public function amqpRpcStamp(): AmqpRpcStampInterface;

}