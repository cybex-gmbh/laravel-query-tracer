<?php

beforeAll(function () {
    setEnvironment([
                       'QUERY_TRACER_ENABLED'              => 'true',
                       'QUERY_TRACER_MODE'                 => 'scoped',
                       'APP_ENV'                           => 'production',
                       'QUERY_TRACER_ALLOWED_ENVIRONMENTS' => 'local,production'
                   ]);
});


it('contains tracer comments when running in production if environment is allowed explicitly', function () {
    config()->set('query-tracer.trace.sqlComment.tag', 'QueryTracer');

    expect(getSQLFromModelQuery())->toContain('/*QueryTracer');
});
