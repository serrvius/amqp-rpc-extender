<?php

namespace Serrvius\AmqpRpcExtender\Interfaces;

interface AmqpRpcQueryInterface
{

    public static function queryIndex(): string;

    public function setQueryData(array $data): void;

}