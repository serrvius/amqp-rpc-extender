<?php

declare(strict_types=1);

namespace Serrvius\AmqpRpcExtender\Enum;

enum TraceableRequestId: string
{
    case REQUEST_ID = 'X-Request-Id';
}
