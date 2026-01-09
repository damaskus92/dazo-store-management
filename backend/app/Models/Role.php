<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory, HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['name'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Defines relationships between models..
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
