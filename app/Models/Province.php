<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $fillable = ['code', 'name'];

    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class, 'province_code', 'code')->orderBy('name');
    }
}
