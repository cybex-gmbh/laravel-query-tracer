<?php

beforeAll(function () {
    setEnvironment([
                       'QUERY_TRACER_ENABLED' => 'true',
                       'QUERY_TRACER_MODE'    => 'default',
                   ]);
});


it('has a trace key in the query log if tracer enabled', function () {
    expect(getTraceArrayFromModelQuery())->toBeArray();
});


it('does not have a trace key in the query log if tracer is enabled but logArray disabled', function () {
    config()->set('query-tracer.trace.logArray.enabled', false);
    expect(getTraceArrayFromModelQuery())->toBeNull();
});


it('changes the trace key in the query log falling back to trace on existing keys', function ($key, $expectedKey) {
    config()->set('query-tracer.trace.logArray.key', $key);
    expect(getQueryLogFromModelQuery())->toHaveKey($expectedKey);
})->with([
             ['testKey', 'testKey'],
             ['time', 'trace'],
             ['query', 'trace'],
             ['bindings', 'trace'],
             ['', 'trace'],
             [null, 'trace'],
             [4, 'trace'],
             [new stdClass(), 'trace']
         ]);


it('includes the source in the query log trace', function () {
    config()->set('query-tracer.trace.includeSource', true);
    config()->set('query-tracer.trace.sourceLinesAround', 4);
    config()->set('query-tracer.trace.logArray.values', ['source']);

    expectTraceArray()->toHaveKey('source')->containingPerformQuerySourceCode();
});


it('includes the calling line only if sourceLinesAround is set to 0', function () {
    config()->set('query-tracer.trace.includeSource', true);
    config()->set('query-tracer.trace.sourceLinesAround', 0);
    config()->set('query-tracer.trace.logArray.values', ['source']);

    expectTraceArray()->toHaveKey('source')->containingPerformQuerySourceCode(true);
});


it('includes the source in the query trace', function () {
    config()->set('query-tracer.trace.includeSource', true);
    config()->set('query-tracer.trace.includeSourceLines', 8);
    config()->set('query-tracer.trace.logArray.values', ['source']);
    config()->set('query-tracer.trace.sqlComment.template', '@source');

    expectTraceQuery()->toContainPerformQuerySourceCode();
});


it('only contains the configured log array values', function () {
    $availableValues = getAvailableLogArrayValues();

    foreach ($availableValues as $availableValue) {
        config()->set('query-tracer.trace.logArray.values', $availableValue);

        expectTraceArray()->toHaveCount(1)->toHaveKey($availableValue);
    }
});


it('does not contain a query log trace if no log array values are configured.', function () {
    config()->set('query-tracer.trace.logArray.values', null);

    expect(getQueryLogFromModelQuery())->not->toHaveKey(config('query-tracer.trace.logArray.key'));
    expectTraceArray()->toBeEmpty();
});


it('can enable and disable query log traces at runtime', function () {
    config()->set('query-tracer.trace.logArray.enabled', false);

    expectTraceArray()->toBeEmpty();

    config()->set('query-tracer.trace.logArray.enabled', true);

    expectTraceArray()->not->toBeEmpty();
});


it('locates QueryTracerConnectionFactory as origin if not restricted to non-QueryTrace files', function () {
    config()->set('query-tracer.backtrace.includeFilesContaining', []);
    config()->set('query-tracer.backtrace.excludeFilesContaining', ['framework/src']);

    expectTraceArray()->when(true, function ($trace) {
        return $trace->and(expect($trace->value['file'])->toContain('QueryTracerConnectionFactory.php'));
    });
});


it('locates the TestModel class as origin if restricted to non-QueryTrace files', function () {
    config()->set('query-tracer.backtrace.includeFilesContaining', []);
    config()->set('query-tracer.backtrace.excludeFilesContaining', ['QueryTrace', 'framework/src']);

    expectTraceArray()->when(true, function ($trace) {
        return $trace->and(
            expect($trace->value['file'])->toContain(
                implode(DIRECTORY_SEPARATOR, ['Models', 'TestModel.php'])
            )
        );
    });
});


it('locates TestModel as origin if restricted to that', function () {
    config()->set('query-tracer.backtrace.includeFilesContaining', ['TestModel']);
    config()->set('query-tracer.backtrace.excludeFilesContaining', []);

    expectTraceArray()->when(true, function ($trace) {
        return $trace->and(expect($trace->value['file'])->toContain('TestModel.php'));
    });
});


it('replaces all known trace keys in SQL comment', function () {
    $availableReplacements = array_map(function ($value) {
        return '@' . $value;
    }, array_merge(getAvailableLogArrayValues(), ['separator']));

    config()->set(
        'query-tracer.trace.sqlComment.template',
        implode(
            PHP_EOL,
            $availableReplacements
        )
    );

    expectTraceQuery()->not->toContain(...$availableReplacements);
});

/**
 * Returns a list of all available keys in a log array.
 *
 * @return array
 */
function getAvailableLogArrayValues(): array
{
    config()->set('query-tracer.trace.logArray.values', '*');

    return array_keys(getTraceArrayFromModelQuery() ?? []);
}
