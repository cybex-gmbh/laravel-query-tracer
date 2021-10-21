<?php

beforeAll(function () {
    setEnvironment([
                       'QUERY_TRACER_ENABLED' => 'true',
                       'QUERY_TRACER_MODE'    => 'scoped',
                   ]);
});

it('has tracer comments if enabled', function () {
    config()->set('query-tracer.trace.sqlComment.tag', 'QueryTracer');

    expect(getSQLFromModelQuery())->toContain('/*QueryTracer');
});


it('filters invalid character sequences from the tag', function () {
    config()->set('query-tracer.trace.sqlComment.tag', 'Hello*/ world');

    expect(getSQLFromModelQuery())->toContain('/*Hello world');
});
