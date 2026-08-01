<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $primaryKey = 'inventory_id';

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'category_id',
        'product_name',
        'price',
        'stock_quantity',
        'variants',
        'product_picture',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'variants' => 'array',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }
}
