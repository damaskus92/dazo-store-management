<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, HasUlids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'store_id',
        'name',
        'sku',
        'price',
        'description',
        'is_active',
    ];

    protected $hidden = [];

    /**
     * Get the attributes that should be cast.
     *
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Defines relationships between models..
     *
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
