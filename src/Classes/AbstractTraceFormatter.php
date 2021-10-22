<?php

namespace Cybex\QueryTracer\Classes;

use Closure;
use Illuminate\Support\Collection;

abstract class AbstractTraceFormatter
{
    /**
     * @var Config
     */
    protected $config;


    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }


    /**
     * Returns the trace in array form as specified in the configuration.
     *
     * @param Collection|null $trace
     * @return array|null
     */
    abstract public function format(?Collection $trace);


    /**
     * Resolves the given closure and stores the result back into the trace Collection to make sure it is only resolved once.
     *
     * @param string $traceKey
     * @param Closure $traceValue
     * @param Collection $trace
     *
     * @return mixed
     */
    protected function resolveClosure(string $traceKey, Closure $traceValue, Collection $trace)
    {
        $result = $traceValue();

        $trace->put($traceKey, $result);

        return $result;
    }
}
