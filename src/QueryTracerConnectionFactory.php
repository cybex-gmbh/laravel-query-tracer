<?php
/** @noinspection PhpUndefinedClassInspection */

namespace Cybex\QueryTracer;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use PDO;

class QueryTracerConnectionFactory extends ConnectionFactory
{
    /**
     * Creates a new database connection.
     *
     * @param string $driver
     * @param Closure|PDO $connection
     * @param string $database
     * @param string $prefix
     * @param array $config
     * @return Connection|QueryTraceConnection
     *
     * @see \Illuminate\Database\Connectors\ConnectionFactory::createConnection()
     *
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        $databaseConnection = parent::createConnection(...func_get_args());

        if ($this->shouldTrace($driver)) {
            $connectionPrepared = true;

            if (!$this->hasActiveQueryTracerConnection()) {
                $connectionPrepared = $this->prepareQueryTracerForConnection($databaseConnection);
            }

            if ($connectionPrepared && $this->usesActiveDriverFor($databaseConnection)) {
                return $this->newQueryTraceConnection($connection, $database, $prefix, $config);
            }
        }

        return $databaseConnection;
    }

    /**
     * Returns the driver name for the application's default connection.
     *
     * @return string
     */
    protected function getDefaultDriver(): string
    {
        return config(sprintf('database.connections.%s.driver', DB::getDefaultConnection()));
    }

    /**
     * Returns true if Query Tracer should attach to the specified driver, false if not.
     *
     * @param string $driver
     * @return bool
     */
    protected function shouldTrace(string $driver): bool
    {
        $restrictToDriver = config('query-tracer.restrictToDriver', $this->getDefaultDriver());

        return $restrictToDriver === '*' || $driver === $restrictToDriver;
    }

    /**
     * Returns true if a QueryTracer connection was already established before.
     *
     * @return bool
     */
    protected function hasActiveQueryTracerConnection(): bool
    {
        return class_exists(__NAMESPACE__ . '\QueryTraceConnection');
    }

    /**
     * Returns true if the given connection uses the driver currently registered with Query Tracer, false if not.
     *
     * @param Connection $databaseConnection
     * @return bool
     */
    protected function usesActiveDriverFor(ConnectionInterface $databaseConnection): bool
    {
        return is_a($databaseConnection, 'Cybex\QueryTracer\QueryTraceConnection');
    }

    /**
     * Creates an alias for the specified databaseConnection class.
     *
     * @param Connection $databaseConnection
     * @return bool
     */
    protected function prepareQueryTracerForConnection(ConnectionInterface $databaseConnection): bool
    {
        return class_alias(get_class($databaseConnection), 'Cybex\QueryTracer\QueryTraceConnection');
    }

    /**
     * Returns a new QueryTraceConnection.
     *
     * @param $connection
     * @param $database
     * @param $prefix
     * @param $config
     * @return QueryTraceConnection
     */
    protected function newQueryTraceConnection($connection, $database, $prefix, $config): QueryTraceConnection
    {
        return new class(...func_get_args()) extends QueryTraceConnection {

            /**
             * Appends the trace to the query before calling the parent logQuery method in Illuminate\Database\Collection.
             * Also appends a key with trace information to the query log, if enabled.
             *
             * @param $query
             * @param $bindings
             * @param null $time
             */
            public function logQuery($query, $bindings, $time = null)
            {
                $queryTracer = app(QueryTrace::class);

                if (config('query-tracer.trace.sqlComment.enabled')) {
                    $query .= $queryTracer->toSqlComment() ?? '';
                }

                parent::logQuery(
                    $query,
                    $bindings,
                    $time
                );

                if ($this->loggingQueries &&
                    config('query-tracer.trace.logArray.enabled') &&
                    ($traceArray = $queryTracer->toArray())
                ) {
                    $this->mergeWithLastLoggedQuery($traceArray);
                }
            }

            /**
             * Merges the given array into the latest entry in the query log.
             *
             * @param array $arrayToMerge
             */
            protected function mergeWithLastLoggedQuery(array $arrayToMerge)
            {
                $this->queryLog[] = $this->mergeTraceIntoLogEntry(
                    Arr::first(array_splice($this->queryLog, -1)),
                    $arrayToMerge
                );
            }

            protected function mergeTraceIntoLogEntry(array $logEntry, array $arrayToMerge): array
            {
                $queryLogKey = config('query-tracer.trace.logArray.key');

                if (!is_string($queryLogKey) || trim($queryLogKey) === '' ||
                    in_array($queryLogKey, array_keys($logEntry))) {
                    $queryLogKey = 'trace';
                }

                return array_merge($logEntry, [$queryLogKey => $arrayToMerge]);
            }
        };
    }
}
