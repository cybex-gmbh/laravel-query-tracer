<?php

use Cybex\QueryTracer\Classes\Config;
use Cybex\QueryTracer\Classes\SourceCodeFormatter;

$codeSnippet = collect(
    [
        '',
        '',
        '/* This is a function */',
        'public function someFunction()',
        '{',
        '    return;',
        '}',
        '',
        '',
    ]
);


it('removes leading empty lines', function () use ($codeSnippet) {
    $codeFormatter = new SourceCodeFormatter(new Config());

    expect($codeFormatter->removeSurroundingEmptyLines($codeSnippet)->first())
        ->toBe('/* This is a function */');
});

it('removes trailing empty lines', function () use ($codeSnippet) {
    $codeFormatter = new SourceCodeFormatter(new Config());

    expect($codeFormatter->removeSurroundingEmptyLines($codeSnippet)->reverse()->first())
        ->toBe('}');
});
