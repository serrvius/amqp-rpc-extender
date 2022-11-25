<?php

namespace Serrvius\AmqpRpcExtender\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class AmqpRpcResultStamp implements StampInterface
{


    public function __construct(public readonly array $results)
    {

    }

}