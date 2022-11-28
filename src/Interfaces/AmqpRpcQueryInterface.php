<?php

namespace Serrvius\AmqpRpcExtender\Interfaces;

interface AmqpRpcQueryInterface
{

    public static function executorName(): string;

    public function setQueryData(array $data): void;

}