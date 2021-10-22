<?php

beforeAll(function () {
    setEnvironment([
                       'QUERY_TRACER_ENABLED' => 'false',
                       'QUERY_TRACER_MODE'    => 'scoped',
                   ]);
});


it('does not have tracer comments when disabled', function () {
    config()->set('query-tracer.trace.sqlComment.tag', 'QueryTracer');

    expect(getSQLFromModelQuery())->not->toContain('/*QueryTracer');
});
