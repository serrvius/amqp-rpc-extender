<?php

namespace Serrvius\AmqpRpcExtender\Interfaces;

interface AmqpRpcCommandInterface
{

    public static function commandIndex(): string;

    public function setCommandData(array $data): void;

}