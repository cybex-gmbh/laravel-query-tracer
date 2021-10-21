<?php

namespace Cybex\QueryTracer;

use Cybex\QueryTracer\Classes\Config;
use Cybex\QueryTracer\Classes\StackTrace;
use Illuminate\Support\Collection;

class QueryTrace
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StackTrace
     */
    protected $stackTrace;

    /**
     * @var
     */
    protected $trace;


    /**
     * @param Config $config
     * @param StackTrace $stackTrace
     */
    public function __construct(Config $config, StackTrace $stackTrace)
    {
        $this->config     = $config;
        $this->stackTrace = $stackTrace;
        $this->trace      = $this->stackTrace->getTrace();
    }


    /**
     * Returns the given trace in log array format.
     *
     * @return array
     */
    public function toArray(): ?array
    {
        return app('trace.formatter.array')->format($this->trace);
    }


    /**
     * Returns the given trace in SQL comment format.
     *
     * @return string
     */
    public function toSqlComment(): ?string
    {
        return app('trace.formatter.sql')->format($this->trace);
    }


    /**
     * Picks the most useful entry from the debug backtrace and returns the data in a Collection.
     *
     * @return Collection|null
     */
    public function getTrace(): ?Collection
    {
        return $this->trace;
    }
}
