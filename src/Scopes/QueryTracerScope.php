<?php

namespace Cybex\QueryTracer\Scopes;

use Cybex\QueryTracer\QueryTrace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Class QueryTracerScope
 *
 * Implements a global scope that adds debugging data to ALL queries
 * that were called from within the app path.
 *
 * @package App\Scopes
 */
class QueryTracerScope implements Scope
{
    /**
     * Name of the extension to be added to the builder.
     *
     * @var array
     */
    protected $extension = 'QueryTrace';

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->{$this->extension}();
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param Builder $builder
     *
     * @return void
     */
    public function extend(Builder $builder)
    {
        $this->{"add{$this->extension}"}($builder);
    }

    /**
     * Add the debug info to the query
     *
     * @param Builder $builder
     *
     * @return void
     */
    protected function addQueryTrace(Builder $builder)
    {
        $builder->macro($this->extension, function (Builder $builder) {
            return $builder->whereRaw('1 ' . (app(QueryTrace::class)->toSqlComment() ?? ''));
        });
    }
}
