<?php

namespace Serrvius\AmqpRpcExtender\Annotation;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsAmqpRpcQueryExecutor
{

    public function __construct(
        public readonly string $name
    ) {

    }

}