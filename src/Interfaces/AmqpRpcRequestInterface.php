<?php

namespace Serrvius\AmqpRpcExtender\Interfaces;


interface AmqpRpcRequestInterface
{

    public function amqpRpcStamp(): AmqpRpcStampInterface;

}