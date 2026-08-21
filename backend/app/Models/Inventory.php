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
        'description',
        'price',
        'stock_quantity',
        'reserved_quantity',
        'variants',
        'product_picture',
        'status',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (empty($this->product_picture)) {
            return null;
        }

        if (str_starts_with($this->product_picture, 'seed-images/')) {
            return asset($this->product_picture);
        }

        return asset('storage/' . $this->product_picture);
    }

    public function getAvailableQuantityAttribute()
    {
        return $this->stock_quantity - $this->reserved_quantity;
    }

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
