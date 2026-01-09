<?php

namespace App\Models;

use App\Enums\StoreLevel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory, HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'parent_id',
        'name',
        'level',
        'address',
        'phone',
    ];

    protected $hidden = [];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'level' => StoreLevel::class,
        ];
    }

    /**
     * Defines relationships between models..
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Store::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
