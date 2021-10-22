<?php

namespace App\Some\Obscure\Path\To\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static static create(array $extra = [])
 * @method static static query()
 * @method static self find(int $id)
 * @property string|null message
 * @property int id
 */
class TestModel extends Model
{
    protected $fillable = ['*'];


    public function performQuery()
    {
        return static::get();
    }
}
