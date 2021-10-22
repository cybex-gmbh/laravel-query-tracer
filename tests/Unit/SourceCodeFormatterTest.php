<?php

use Cybex\QueryTracer\Classes\Config;
use Cybex\QueryTracer\Classes\SourceCodeFormatter;
use Illuminate\Support\Collection;

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

beforeEach(function () {
    $this->codeFormatter = new SourceCodeFormatter(new Config());
});


it('removes leading empty lines', function () use ($codeSnippet) {
    expect($this->codeFormatter->removeSurroundingEmptyLines($codeSnippet)->first())
        ->toBe('/* This is a function */');
});


it('removes trailing empty lines', function () use ($codeSnippet) {
    expect($this->codeFormatter->removeSurroundingEmptyLines($codeSnippet)->reverse()->first())
        ->toBe('}');
});


it('gets code starting at line 1 without other code on top', function () {
    expect(getSourceAtLine($this, 1))
        ->first()
        ->toBe('* 0001  <?php namespace Cybex\QueryTracer;');
});


it('gets code ending with the last line without further code', function () {
    expect(getSourceAtLine($this, 36))
        ->reverse()
        ->first()
        ->toBe('* 0036  }');
});


it('does not return source code if line is out of range', function () {
    expect($this->codeFormatter->getSourceAt($this->getDataDir('testcode.php'), 158))
        ->toBeNull();
});


it('does not return source code if line number is negative', function () {
    expect($this->codeFormatter->getSourceAt($this->getDataDir('testcode.php'), -14))
        ->toBeNull();
});


it('removes leading and trailing empty lines', function () {
    expect(getSourceAtLine($this, 25))
        ->toHaveCount(4);
});


function getSourceAtLine($instance, int $line): Collection
{
    return collect(
        explode(
            PHP_EOL,
            $instance->codeFormatter->getSourceAt($instance->getDataDir('testcode.php'), $line)
        )
    );
}
