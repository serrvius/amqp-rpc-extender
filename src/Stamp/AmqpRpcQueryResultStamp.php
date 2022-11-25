<?php

namespace Serrvius\AmqpRpcExtender\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class AmqpRpcQueryResultStamp implements StampInterface
{

    protected mixed $results;

    public function __construct(mixed $results)
    {
        $this->results = $results;
    }

    /**
     * @param  mixed  $results
     */
    public function setResults(mixed $results): void
    {
        $this->results = $results;
    }


    /**
     * @return mixed
     */
    public function getResults(): mixed
    {
        return $this->results;
    }


}