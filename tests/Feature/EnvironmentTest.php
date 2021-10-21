<?php

beforeAll(function () {
    setEnvironment([
                       'QUERY_TRACER_ENABLED' => 'true',
                       'QUERY_TRACER_MODE'    => 'scoped',
                       'APP_ENV'              => 'production',
                   ]);
});


it('does not contain tracer comments when running in production', function () {
    config()->set('query-tracer.trace.sqlComment.tag', 'QueryTracer');

    expect(getSQLFromModelQuery())->not->toContain('/*QueryTracer');
});
