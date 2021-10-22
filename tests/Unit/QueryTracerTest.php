<?php

beforeAll(function () {
    setEnvironment([
                       'QUERY_TRACER_ENABLED' => 'true',
                   ]);
});


it('resolves closures from the trace array', function () {
    $trace = collect([
                         'file' => function () {
                             return 'it works!';
                         }
                     ]);

    $arrayTrace = app('trace.formatter.array')->format($trace);

    expect($arrayTrace)
        ->toBeArray()
        ->toHaveKey('file', 'it works!');

    expect($trace)
        ->toHaveKey('file', 'it works!');
});
