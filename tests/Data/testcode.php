<?php namespace Cybex\QueryTracer;

use Illuminate\Database\Eloquent\Model;

/**
 * This file is used in Source Code Formatter tests. All formatting is intentional, do not edit.
 */
class SampleModel extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected static function booted()
    {
        parent::booted();
    }




    protected static function boot()
    {
        parent::boot();
    }





    function getTestAttribute(): string
    {
        static::pluck('test');
    }
}
