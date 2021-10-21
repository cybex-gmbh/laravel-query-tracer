<?php

use App\Some\Obscure\Path\To\Models\TestModel;
use Cybex\QueryTracer\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

uses(TestCase::class)->in(__DIR__);

expect()->extend('toContainPerformQuerySourceCode', function () {
    return $this
        ->toContain('public function performQuery()')
        ->toContain('return static::get();');
});

expect()->extend('containingPerformQuerySourceCode', function (bool $callOnly = false) {
    return $this->and(
        expect(is_array($this->value) ? ($this->value['source'] ?? '') : $this->value)
            ->toContain('return static::get();')
            ->when(!$callOnly, function ($source) {
                return $source->toContain('public function performQuery()');
            })
    );
});

function expectTraceArray(): \Pest\Expectation
{
    return expect(getTraceArrayFromModelQuery() ?? []);
}

function expectTraceQuery(): \Pest\Expectation
{
    return expect(getSQLFromModelQuery() ?? '');
}

/**
 * Returns an instance of TestModel.
 *
 * @return Model
 */
function getTestModel(): Model
{
    return TestModel::create([]);
}

/**
 * Performs a query on an instance of the Test Model and returns the query log entry.
 *
 * @return array
 */
function getQueryLogFromModelQuery(): array
{
    $model = getTestModel();

    DB::enableQueryLog();

    $model->performQuery();

    DB::disableQueryLog();

    return Arr::last(DB::getQueryLog(), null, []);
}

function getSQLFromModelQuery(): string
{
    $logArray = getQueryLogFromModelQuery();

    return $logArray['query'] ?? '';
}

function getTraceArrayFromModelQuery(): ?array
{
    return Arr::get(getQueryLogFromModelQuery(), config('query-tracer.trace.logArray.key'));
}


function setEnvironment(array $environmentSettings)
{
    resetEnvironment();
    putEnvironment($environmentSettings);
}


function putEnvironment(array $environmentSettings)
{
    foreach ($environmentSettings as $environmentSettingKey => $environmentSettingValue) {
        putenv(implode('=', [$environmentSettingKey, $environmentSettingValue]));
    }
}

function resetEnvironment()
{
    putEnvironment([
                       'QUERY_TRACER_ENABLED'              => 'true',
                       'QUERY_TRACER_MODE'                 => 'default',
                       'APP_ENV'                           => 'testing',
                       'QUERY_TRACER_ALLOWED_ENVIRONMENTS' => 'testing',
                       'QUERY_TRACER_DRIVER'               => 'sqlite',
                   ]);
}
