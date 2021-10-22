<?php

namespace Cybex\QueryTracer\Classes;

use Closure;
use Illuminate\Support\Collection;

class LogArrayFormatter extends AbstractTraceFormatter
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
    public function format(?Collection $trace): ?array
    {
        if ($trace && ($preserveKeys = collect($this->config->getLogArrayValues()))) {
            if ($preserveKeys->contains('*')) {
                return $trace->toArray();
            }

            return $trace
                ->only($preserveKeys)
                ->map(function ($traceValue, $traceKey) use ($trace) {
                    return $traceValue instanceof Closure ? $this->resolveClosure(
                        $traceKey,
                        $traceValue,
                        $trace
                    ) : $traceValue;
                })
                ->filter()
                ->toArray();
        }

        return null;
    }
}
