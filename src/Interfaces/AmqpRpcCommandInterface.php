<?php

namespace Serrvius\AmqpRpcExtender\Interfaces;

interface AmqpRpcCommandInterface
{

    public static function executorName(): string;

    public function setCommandData(array $data): void;

}