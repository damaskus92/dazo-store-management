<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    /** @use HasFactory<\Database\Factories\SaleItemFactory> */
    use HasFactory, HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
        'subtotal',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'integer',
            'subtotal' => 'integer',
        ];
    }
}
