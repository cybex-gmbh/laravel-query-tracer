<?php

/**
 * This test is disabled for now, since it relies on tests running in separate processes, which is not supported
 * by PestPHP at this time (see https://github.com/pestphp/pest/issues/270 and
 * https://github.com/pestphp/pest/pull/285).
 *
 * The test needs to run isolated, because it makes the QueryTracerConnectionFactory bind to the mySQL driver.
 * Since this is done using class_alias() behind the scenes, and class aliases persist the complete lifetime
 * of a process, running this test before all other tests will cause most other tests to fail (because it
 * will stay bound to mysql), and running this test after all other tests will cause this test to fail.
 *
 */

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

beforeAll(function () {
    setEnvironment([
                       'QUERY_TRACER_ENABLED' => 'true',
                       'QUERY_TRACER_MODE'    => 'default',
                       'QUERY_TRACER_DRIVER'  => 'mysql',
                   ]);
});

beforeEach(function () {
    setupMySqlConnection();
});


it('overrides the MySqlConnection with a QueryTraceConnection if restricted to the mysql driver', function () {
    expect(DB::connection('mysql_fake'))->toBeInstanceOf(\Cybex\QueryTracer\QueryTraceConnection::class);
});


it('does not override the SqliteConnection with a QueryTraceConnection if restricted to the mysql driver', function () {
    expect(DB::connection('sqlite'))->toBeInstanceOf(\Illuminate\Database\SQLiteConnection::class);
});

function setupMySqlConnection()
{
    Config::set('database.connections.mysql_fake', [
        'driver'   => 'mysql',
        'database' => 'testing',
    ]);
}
